<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\SetList;
use Rector\Set\ValueObject\DowngradeLevelSetList; // PHP Downgrades
use Rector\Set\ValueObject\LevelSetList; // PHP Upgrades
use Rector\CodeQuality\Rector\Array_\CallableThisArrayToAnonymousFunctionRector;
use Rector\Arguments\Rector\MethodCall\RemoveMethodCallParamRector;
use Rector\Arguments\Rector\ClassMethod\ReplaceArgumentDefaultValueRector;
use Rector\CodingStyle\Rector\Closure\StaticClosureRector;
use Rector\CodingStyle\Rector\Encapsed\EncapsedStringsToSprintfRector;
use Rector\Php74\Rector\Closure\ClosureToArrowFunctionRector;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->autoloadPaths([
        __DIR__ . '/vendor/squizlabs/php_codesniffer/autoload.php',
        __DIR__ . '/vendor/php-stubs/wordpress-stubs/wordpress-stubs.php',
        __DIR__ . '/vendor/php-stubs/php-stubs/acf-pro-stubs/acf-pro-stubs.php',
    ]);

    $rectorConfig->paths([
        __DIR__ . '/',
    ]);

    $rectorConfig->skip([
        __DIR__ . '/vendor',
        __DIR__ . '/node_modules',
    ]);

    $rectorConfig->sets([
        DowngradeLevelSetList::DOWN_TO_PHP_74,
        LevelSetList::UP_TO_PHP_74,
        SetList::PHP_74,
        SetList::CODING_STYLE,
        SetList::CODE_QUALITY,
        SetList::DEAD_CODE,
        SetList::EARLY_RETURN,
        SetList::INSTANCEOF,
        SetList::NAMING,
        SetList::TYPE_DECLARATION
    ]);

    $rectorConfig->skip([
        CallableThisArrayToAnonymousFunctionRector::class,
        StaticClosureRector::class,
        ClosureToArrowFunctionRector::class,
        EncapsedStringsToSprintfRector::class
    ]);

    $rectorConfig->rule(RemoveMethodCallParamRector::class);
    $rectorConfig->rule(ReplaceArgumentDefaultValueRector::class);

};
