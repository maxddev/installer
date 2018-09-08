<?php

namespace Helpflow\Installer;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;

class CreateUserCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('helpflow:install')
            ->setDescription('Install a new version of Helpflow');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $helper = $this->getHelper('question');
        $question = new ChoiceQuestion(
            'Please select your system (defaults to Laravel Spark)',
            ['Laravel Spark'],
            0
        );

        $type = $helper->ask($input, $output, $question);



    }
}
