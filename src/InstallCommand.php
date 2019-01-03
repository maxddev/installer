<?php

namespace Helpflow\Installer;

use Symfony\Component\Process\Process;
use Helpflow\Installer\Service\SellMyGit;
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
            ->setDescription('Install a new version of Helpflow');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $helper = $this->getHelper('question');
        $question = new ChoiceQuestion(
            'Please select your integration type (defaults to Laravel Spark)',
            ['Laravel Spark'],
            0
        );
        $this->type = $helper->ask($input, $output, $question);
        $this->path = rtrim(getcwd(), '/');
        $this->progressBar = new ProgressBar($output, 7);

        // download helpflow
        $this->downloadCode($output);

        // add to composer file
        $this->setupComposerFile($output);

        // composer install
        $this->composerInstall($output);

        // add Helpflow User Trait
        $this->addHelpflowUserTrait($output);

        // add service provider
        $this->addServiceProviders($output);

        // migrate
        $this->runMigrations($output);

        // publish helpflow tag
        $this->vendorPublish($output);

        $completeMsg = [
            '<info>Helpflow installation completed successfully!</info>',
            '<comment>==================</comment>',
            '<comment>Next Steps</comment>',
            '<comment>==================</comment>',
            '<comment>Add a link to your help desk from your application.</comment>',
            '<comment>Define your support staff via the helpflow-staff config.</comment>',
            '<comment>See the readmes for more information.</comment>',
        ];

        $output->writeln($completeMsg);
    }

    public function downloadCode($output)
    {
        $output->writeln([
            '<comment>Download Code</comment>',
            '<comment>==================</comment>'
        ]);

        $sellMyGit = new SellMyGit;

        $result = $sellMyGit->getFile(
            $this->path,
            $this->getLicenseKey()
        );

        if (! $result) {
            $output->writeln([
                '<fg=red>Helpflow Installation stopped [' . $sellMyGit->getErrorMsg() . ']</>'
            ]);
            die();
        }

        $hfFolder = $this->path . DS . 'helpflow';

        $process = new Process('unzip ' . $this->path . DS . $sellMyGit->getFilename() . ' && mv -f ' . $sellMyGit->getUnzippedName() . ' ' . $hfFolder);
        $process
            ->setTimeout(null)
            ->run(function ($type, $line) use ($output) {
                //
            });

        unlink($this->path . DS . $sellMyGit->getFilename());

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

    public function addHelpflowUserTrait($output)
    {
        $output->writeln([
            '<comment>Add Helpflow User Trait</comment>',
            '<comment>==============================</comment>'
        ]);

        if (! file_exists($this->path . '/app/User.php')) {
            $output->writeln([
                '<fg=red>Could not find the User Model, please add the "Helpflow\Helpflow\HelpflowUser" trait to your User model</>'
            ]);

            return;
        }

        $userModel = file_get_contents($this->path . '/app/User.php');

        if ($this->type === 'Laravel Spark') {
            $userModel = str_replace(
                "class User extends SparkUser\n{\n",
                "class User extends SparkUser\n{\n    use \Helpflow\Helpflow\HelpflowUser;\n",
                $userModel
            );
        }

        file_put_contents(
            $this->path . '/app/User.php',
            $userModel
        );

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
            $adminProvider = 'Helpflow\SparkAdmin\Providers\HelpflowSparkServiceProvider::class';
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

    /**
     * @return string
     */
    protected function getLicenseKey()
    {
        return file_get_contents(
            HF_INSTALLER . DS . 'storage' . DS . 'license.key'
        );
    }
}
