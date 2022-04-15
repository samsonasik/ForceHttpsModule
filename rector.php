<?php

declare(strict_types=1);

use Rector\CodeQuality\Rector\Array_\CallableThisArrayToAnonymousFunctionRector;
use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->sets([
        LevelSetList::UP_TO_PHP_74,
        SetList::CODE_QUALITY,
        SetList::NAMING,
        SetList::TYPE_DECLARATION,
        SetList::TYPE_DECLARATION_STRICT,
    ]);

    $rectorConfig->paths([__DIR__ . '/config', __DIR__ . '/src', __DIR__ . '/spec', __DIR__ . '/rector.php']);
    $rectorConfig->importNames();
    $rectorConfig->skip([
        CallableThisArrayToAnonymousFunctionRector::class,
    ]);
};
