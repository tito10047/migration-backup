<?php

use Tito10047\MigrationBackup\Tests\App\Kernel;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;

// needed to avoid encoding issues when running tests on different platforms
setlocale(LC_ALL, 'en_US.UTF-8');

// needed to avoid failed tests when other timezones than UTC are configured for PHP
date_default_timezone_set('UTC');

require dirname(__DIR__) . '/vendor/autoload.php';

(new Dotenv())->bootEnv(dirname(__DIR__) . '/.env');
$kernel = new Kernel(null);
(new Symfony\Component\Filesystem\Filesystem())->remove($kernel->getCacheDir());

$application = new Application($kernel);
$application->setAutoExit(false);
$application->setCatchExceptions(false);

$runCommand = function (string $name, array $options = []) use ($application) {
    $input = new ArrayInput(array_merge(['command' => $name], $options));
    $input->setInteractive(false);
    $application->run($input);
};

$runCommand('doctrine:database:drop', [
    '--force' => 1,
    '--no-interaction' => true
]);
$runCommand('doctrine:database:create', [
    '--no-interaction' => true
]);

$kernel->shutdown();