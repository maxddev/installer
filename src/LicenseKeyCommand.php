<?php

namespace Helpflow\Installer;

use Symfony\Component\Process\Process;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Exception\ProcessFailedException;

class LicenseKeyCommand extends Command
{
    protected $path;
    protected $type;
    protected $progressBar;

    protected function configure()
    {
        $this
            ->setName('license-key')
            ->setDescription('Set your Helpflow License Key')
            ->addArgument('key', InputArgument::REQUIRED, 'Your Helpflow License Key from SellMyGit');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (! is_dir(HF_INSTALLER . DS . 'storage')) {
            mkdir(HF_INSTALLER . DS . 'storage');
        }

        file_put_contents(
            HF_INSTALLER . DS . 'storage' . DS . 'license.key',
            $input->getArgument('key')
        );

        $output->writeln('License key set');
    }
}
