<?php

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symplify\SymfonyStaticDumper\ValueObject\SymfonyStaticDumperConfig;

return static function (ContainerConfigurator $containerConfigurator): void {
    $containerConfigurator->import(SymfonyStaticDumperConfig::FILE_PATH);
};
