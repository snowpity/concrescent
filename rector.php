<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\TypeDeclaration\Rector\ClassMethod\AddVoidReturnTypeWhereNoReturnRector;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/cm2',
    ])
    ->withPhpSets(php84: true)
    ->withRules([
        AddVoidReturnTypeWhereNoReturnRector::class,
    ]);
