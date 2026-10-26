<?php

declare(strict_types=1);

use ShipMonk\ComposerDependencyAnalyser\Config\Configuration;
use ShipMonk\ComposerDependencyAnalyser\Config\ErrorType;

return (new Configuration())
    // The environment returns Fluid's resolver delegates; the type appears in PHPDoc only, which the analyser does not count
    ->ignoreErrorsOnPackage('typo3fluid/fluid', [ErrorType::PROD_DEPENDENCY_ONLY_IN_DEV]);
