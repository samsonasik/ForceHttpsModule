<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Php81\Rector\Array_\FirstClassCallableRector;

return RectorConfig::configure()
    ->withPhpSets(php82: true)
    ->withPreparedSets(deadCode: true, codeQuality: true, naming: true, typeDeclarations: true, codingStyle: true)
    ->withImportNames(removeUnusedImports: true)
    ->withPaths([
        __DIR__ . '/config',
        __DIR__ . '/src',
        __DIR__ . '/spec',
        __DIR__ . '/rector.php'
    ])
    ->withRootFiles()
    ->withSkip([
        FirstClassCallableRector::class,
    ]);
