<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\DeadCode\Rector\ClassMethod\RemoveUnusedPublicMethodParameterRector;

// Formatting is owned by ECS, so coding-style sets are intentionally left off here
// to avoid the two tools fighting over the same fixes.
return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ])
    ->withRootFiles()
    ->withPhpSets()
    ->withPreparedSets(
        deadCode: true,
        codeQuality: true,
        typeDeclarations: true,
        privatization: true,
        instanceOf: true,
        earlyReturn: true,
    )
    ->withImportNames(removeUnusedImports: true)
    ->withSkip([
        // Public signatures are dictated by the framework (Messenger handlers,
        // controllers, listeners, interface impls); dropping a "unused" param
        // here breaks message routing and contracts. Private params stay pruned.
        RemoveUnusedPublicMethodParameterRector::class,
    ]);
