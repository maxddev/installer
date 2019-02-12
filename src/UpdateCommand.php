<?php

namespace Helpflow\Installer;

use Symfony\Component\Process\Process;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Process\Exception\ProcessFailedException;

class UpdateCommand extends Command
{
    protected $path;
    protected $type;
    protected $progressBar;

    protected function configure()
    {
        $this
            ->setName('update')
            ->setDescription('Update to the latest version of Helpflow');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $helper = $this->getHelper('question');
        $question = new ChoiceQuestion(
            'Please select your integration type (defaults to Generic)',
            ['Generic', 'Spark'],
            0
        );
        $this->type = $helper->ask($input, $output, $question);
        $this->path = rtrim(getcwd(), '/');
        $this->progressBar = new ProgressBar($output, 1);

        // composer update
        $this->composerUpdate($output);

        $completeMsg = [
            '<info>Helpflow updated successfully!</info>',
            '<info>Please check the changelog for any asset changes which may need to be published</info>'
        ];

        $output->writeln($completeMsg);
    }

    public function composerUpdate($output)
    {
        if ($this->type === 'Spark') {
            $adminPackage = 'helpflow/spark-admin';
        } elseif ($this->type === 'Generic') {
            $adminPackage = 'helpflow/generic';
        }

        $output->writeln([
            '<comment>Running composer update</comment>',
            '<comment>=======================</comment>'
        ]);
        $process = new Process('cd ' . $this->path . ' && composer update helpflow/helpflow ' . $adminPackage);
        $process
            ->setTimeout(null)
            ->run(function ($type, $line) use ($output) {
                $output->write($line);
            });

        $this->progressBar->advance();
        $output->writeln('');
    }
}
