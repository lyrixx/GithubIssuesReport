<?php

namespace Lyrixx\GithubIssueReport;

use Lyrixx\GithubIssueReport\Command as Commands;
use Lyrixx\GithubIssueReport\Helper\GithubHelper;
use Symfony\Component\Console\Application as BaseApplication;
use Symfony\Component\Console\Input\InputInterface;

class Application extends BaseApplication
{
    public function __construct()
    {
        parent::__construct('GithubIssueReport', '0.0.1');

        $this->add(new Commands\ReportBuilderCommand());
    }

    /**
     * Overridden so that the application doesn't expect the command
     * name to be the first argument.
     */
    public function getDefinition()
    {
        $inputDefinition = parent::getDefinition();
        $inputDefinition->setArguments();

        return $inputDefinition;
    }

    protected function getCommandName(InputInterface $input)
    {
        return 'build-report';
    }

    protected function getDefaultHelperSet()
    {
        $defaultHelpers = parent::getDefaultHelperSet();

        $defaultHelpers->set(new GithubHelper($this->getName()));

        return $defaultHelpers;
    }
}
