<?php

declare(strict_types=1);

namespace FluidStan\TYPO3\Tests\Unit;

use FluidStan\TYPO3\Typo3Environment;
use PHPUnit\Framework\Attributes\BackupGlobals;
use PHPUnit\Framework\TestCase;

#[BackupGlobals(true)]
final class Typo3EnvironmentTest extends TestCase
{
    public function testNamespacesMergeTypo3DefaultsWithConfiguredOnes(): void
    {
        $GLOBALS['TYPO3_CONF_VARS'] = ['SYS' => ['fluid' => ['namespaces' => [
            'f' => ['My\\Overrides\\ViewHelpers'],
            'my' => 'My\\Site\\ViewHelpers',
        ]]]];

        $this->assertSame([
            'f' => ['TYPO3Fluid\\Fluid\\ViewHelpers', 'TYPO3\\CMS\\Fluid\\ViewHelpers', 'My\\Overrides\\ViewHelpers'],
            'my' => ['My\\Site\\ViewHelpers'],
        ], (new Typo3Environment())->getNamespaces());
    }

    public function testOnlyExtPathsAreResolved(): void
    {
        $this->assertNull((new Typo3Environment())->resolvePath('/absolute/path'));
        $this->assertNull((new Typo3Environment())->resolvePath('relative/path'));
    }
}
