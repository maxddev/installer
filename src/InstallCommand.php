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

class InstallCommand extends Command
{
    protected $path;
    protected $type;
    protected $progressBar;

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
        $this->type = $helper->ask($input, $output, $question);
        $this->path = rtrim($input->getArgument('path'), '/');
        $this->progressBar = new ProgressBar($output, 6);

        // git clone helpflow
        $this->cloneRepo($output);

        // add to composer file
        $this->setupComposerFile($output);

        // composer install
        $this->composerInstall($output);

        // add service provider
        $this->addServiceProviders($output);

        // migrate
        $this->runMigrations($output);

        // publish helpflow tag
        $this->vendorPublish($output);

        $completeMsg = [
            '<info>Helpflow installation completed successfully!</info>',
        ];

        if ($this->type === 'Laravel Spark') {
            $completeMsg[] = '<info>As you are using Laravel Spark, don\'t forget to add the links within the Kiosk to Helpflow. See the readme for more information.</info>';
        }

        $output->writeln($completeMsg);
    }

    public function cloneRepo($output)
    {
        $output->writeln([
            '<comment>Starting Git clone</comment>',
            '<comment>==================</comment>'
        ]);
        $process = new Process('git clone git@github.com:mikebarlow/helpflow.git ' . $this->path . '/helpflow');
        $process
            ->setTimeout(null)
            ->run(function ($type, $line) use ($output) {
                $output->write($line);
            });

        $this->progressBar->advance();
        $output->writeln('');
    }

    public function setupComposerFile($output)
    {
        $output->writeln([
            '<comment>Starting Composer.json updates</comment>',
            '<comment>==============================</comment>'
        ]);
        $composerJson = json_decode(
            file_get_contents($this->path . '/composer.json'),
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
        $composerJson['require']['helpflow/helpflow'] = '*@dev';

        // composer require admin type
        if ($this->type === 'Laravel Spark') {
            $composerJson['require']['helpflow/spark-admin'] = '*@dev';
        }

        // save composer
        file_put_contents(
            $this->path . '/composer.json',
            json_encode($composerJson, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)
        );

        $this->progressBar->advance();
        $output->writeln('');
    }

    public function composerInstall($output)
    {
        $output->writeln([
            '<comment>Running composer update</comment>',
            '<comment>=======================</comment>'
        ]);
        $process = new Process('cd ' . $this->path . ' && composer update');
        $process
            ->setTimeout(null)
            ->run(function ($type, $line) use ($output) {
                $output->write($line);
            });

        $this->progressBar->advance();
        $output->writeln('');
    }

    public function addServiceProviders($output)
    {
        $output->writeln([
            '<comment>Add Helpflow Service Providers</comment>',
            '<comment>==============================</comment>'
        ]);
        $appConfig = file_get_contents($this->path . '/config/app.php');

        $appConfig = str_replace(
            '        App\\Providers\\AppServiceProvider::class,',
            "        Helpflow\Helpflow\Providers\HelpflowServiceProvider::class,\n        App\Providers\AppServiceProvider::class,",
            $appConfig
        );

        if ($this->type === 'Laravel Spark') {
            $adminProvider = 'Helpflow\SparkAdmin\Providers\HelpflowSparkAdminServiceProvider::class';
        }

        $appConfig = str_replace(
            '        Helpflow\Helpflow\Providers\HelpflowServiceProvider::class,',
            "        Helpflow\Helpflow\Providers\HelpflowServiceProvider::class,\n        " . $adminProvider . ",",
            $appConfig
        );

        file_put_contents(
            $this->path . '/config/app.php',
            $appConfig
        );

        $this->progressBar->advance();
        $output->writeln('');
    }

    public function runMigrations($output)
    {
        $output->writeln([
            '<comment>Starting DB Migrate</comment>',
            '<comment>===================</comment>'
        ]);
        $process = new Process('php ' . $this->path . '/artisan migrate');
        $process
            ->setTimeout(null)
            ->run(function ($type, $line) use ($output) {
                $output->write($line);
            });

        $this->progressBar->advance();
        $output->writeln('');
    }

    public function vendorPublish($output)
    {
        $output->writeln([
            '<comment>Starting asset publishing</comment>',
            '<comment>=========================</comment>'
        ]);
        $process = new Process('php ' . $this->path . '/artisan vendor:publish --tag=helpflow');
        $process
            ->setTimeout(null)
            ->run(function ($type, $line) use ($output) {
                $output->write($line);
            });

        $this->progressBar->advance();
        $output->writeln('');
    }
}
