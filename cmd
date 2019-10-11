#!/usr/bin/php
<?php
if (php_sapi_name() !== 'cli') {
    exit;
}
require __DIR__ . '/vendor/autoload.php';

use App\Command\WorkingDaysDeadlineController;

$app = new Lib\App();
$app->registerController('deadline', new WorkingDaysDeadlineController($app));
$app->registerCommand('help', function (array $argv) use ($app) {
    $app->getPrinter()->display("usage: deadline [ limit ] [ date ]");
});
$app->runCommand($argv);