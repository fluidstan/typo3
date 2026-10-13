<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;

return RectorConfig::configure()
    ->withPaths([__DIR__ . '/src', __DIR__ . '/tests', __DIR__ . '/docs/example/shop/Classes'])
    // Extbase models: properties are hydrated by reflection and must stay protected
    ->withSkip([__DIR__ . '/docs/example/shop/Classes/Domain/Model'])
    ->withCache(__DIR__ . '/.cache/rector')
    ->withPhpSets(php82: true)
    ->withPreparedSets(
        deadCode: true,
        codeQuality: true,
        codingStyle: true,
        typeDeclarations: true,
        privatization: true,
        earlyReturn: true,
        phpunitCodeQuality: true,
    )
    ->withAttributesSets(phpunit: true)
    ->withImportNames(importShortClasses: false, removeUnusedImports: true);
