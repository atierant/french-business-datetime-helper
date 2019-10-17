#!/usr/bin/php
<?php
if (php_sapi_name() !== 'cli') {
    exit;
}
require __DIR__ . '/vendor/autoload.php';

use App\Command\WorkingDaysDeadlineController;
use App\Command\AssignInvoiceSendingStatusController;

$app = new Lib\App();
$app->registerController('deadline', new WorkingDaysDeadlineController($app));
$app->registerController('invoice', new AssignInvoiceSendingStatusController($app));
$app->registerCommand('help', function (array $argv) use ($app) {
    $app->getPrinter()->display("usage: deadline [ limit ] [ date ]");
    $app->getPrinter()->display("usage: invoice");
});
$app->runCommand($argv);