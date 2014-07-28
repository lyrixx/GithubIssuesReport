<?php

namespace Lyrixx\GithubIssueReport\Command;

use Github\Client as Github;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ReportBuilderCommand extends Command
{
    private $github;

    public function configure()
    {
        $this
            ->setName('build-report')
            ->addArgument('repositories', InputArgument::IS_ARRAY, 'list of repositories: symfony/symfony')
        ;
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->github = $this->getGithubClient($input, $output);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('HackDay');
        $output->writeln('=======');

        $issues = array();
        foreach ($input->getArgument('repositories') as $repository) {
            $issues = $this->getIssues($repository);
            if (!$issues) {
                continue;
            }
            $output->writeln('');
            $output->writeln($repository);
            $output->writeln(str_repeat('-', strlen($repository)));
            $output->writeln('');
            $output->writeln('| Issue | labels | ETA');
            $output->writeln('|:------|:-------|:---');

            foreach ($issues as $issue) {
                $output->writeln(sprintf('[%s](%s) | %s | %s', $this->getTitle($issue), $issue['url'], $this->getLabelsAsString($issue), $this->getETA($issue)));
            }
        }
    }

    private function getIssues($rawRepository)
    {
        $repositoryInfo = explode('/', $rawRepository);

        if (2 !== count($repositoryInfo)) {
            throw new \InvalidArgumentException(sprintf('The repository "%s" is not valid', $rawRepository));
        }

        list($username, $repository) = $repositoryInfo;

        return $this->github->api('issues')->all($username, $repository, array(
            'per_page' => 100,
            'labels' => 'hackday',
        ));
    }

    private function getLabelsAsString(array $issue)
    {
        $labels = '';
        foreach ($issue['labels'] as $label) {
            $label = $label['name'];
            if ('hackday' === $label) {
                continue;
            }

            if (in_array(strtolower($label), array('s', 'm', 'l', 'xl', 'xxl'))) {
                continue;
            }

            $labels .= $label. ' ';
        }

        return trim($labels);
    }

    private function getETA(array $issue)
    {
        if (preg_match('/^eta\s*[:=]*\s*(.*)$/i', $issue['body'], $matches)) {
            return $matches[1];
        }

        foreach ($issue['labels'] as $label) {
            $label = $label['name'];
            if (in_array(strtolower($label), array('s', 'm', 'l', 'xl', 'xxl'))) {
                return $label;
            }
        }

        return 'n-a';
    }

    private function getTitle(array $issue)
    {
        return htmlentities($issue['title']);
    }

    private function getGithubClient(InputInterface $input, OutputInterface $output)
    {
        $credentials = $this->getHelperSet()->get('github')->getCredentials($input, $output);

        $github = new Github();
        $github->authenticate($credentials['token'], Github::AUTH_HTTP_TOKEN);

        return $github;
    }
}
