<?php

namespace Helpflow\Installer;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CreateUserCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('helpflow:install')
            ->setDescription('Install a new version of Helpflow')
            ->addArgument('type', InputArgument::REQUIRED, 'The type of install; "laravel", "spark"');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $installType = $input->getArgument('type');


    }
}
