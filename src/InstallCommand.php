<?php

namespace Helpflow\Installer;

use Symfony\Component\Process\Process;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Process\Exception\ProcessFailedException;

class InstallCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('install')
            ->setDescription('Install a new version of Helpflow')
            ->addArgument('path', InputArgument::REQUIRED, 'The absolute path to the root laravel directory');
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
        $path = rtrim($input->getArgument('path'), '/');

        // git checkout helpflow
        $process = new Process('git checkout git@github.com:mikebarlow/helpflow.git ' . $path . '/helpflow');
        $process
            ->setTimeout(null)
            ->run(function ($type, $line) use ($output) {
                $output->write($line);
            });

        // add to composer file
        $composerJson = json_decode(
            file_get_contents($path . '/composer.json'),
            true
        );
        if (! empty($composerJson['repositories'])) {
            $composerJson['repositories'][] = [
                'type' => 'path',
                'url' => './helpflow',
            ];
        } else {
            $composerJson['repositories'] = [[
                'type' => 'path',
                'url' => './helpflow',
            ]];
        }

        // composer require helpflow
        $composerJson['require']['helpflow/helpflow'] = '1.0.*';

        // composer require admin type
        if ($type === 'Laravel Spark') {
            $composerJson['require']['helpflow/spark-admin'] = '1.0.*';
        }

        // save composer
        file_put_contents($path . '/composer.json', $composerJson);

        // add service provider
        $appConfig = file_get_contents($path . '/config/app.php');

        $appConfig = str_replace(
            '        App\\Providers\\AppServiceProvider::class,',
            "        Helpflow\Helpflow\Providers\HelpflowServiceProvider::class,\n        App\Providers\AppServiceProvider::class,",
            $appConfig
        );

        if ($type === 'Laravel Spark') {
            $adminProvider = 'Helpflow\SparkAdmin\Providers\HelpflowSparkAdminServiceProvider::class';
        }

        $appConfig = str_replace(
            '        Helpflow\Helpflow\Providers\HelpflowServiceProvider::class,',
            "        Helpflow\Helpflow\Providers\HelpflowServiceProvider::class,\n        " . $adminProvider . ",",
            $appConfig
        );

        file_put_contents($path . '/config/app.php', $appConfig);

        // migrate
        $process = new Process('php ' . $path . '/artisan migrate');
        $process
            ->setTimeout(null)
            ->run(function ($type, $line) use ($output) {
                $output->write($line);
            });

        // publish helpflow tag


        // if type is laravel spark, message to add admins to the developers section for kiosk access
    }
}
