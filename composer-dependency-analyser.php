<?php

declare(strict_types=1);

use ShipMonk\ComposerDependencyAnalyser\Config\Configuration;
use ShipMonk\ComposerDependencyAnalyser\Config\ErrorType;

return (new Configuration())
    // Only its ViewHelper namespace is referenced, as a string, for the "f" Fluid namespace
    ->ignoreErrorsOnPackage('typo3/cms-fluid', [ErrorType::UNUSED_DEPENDENCY]);
