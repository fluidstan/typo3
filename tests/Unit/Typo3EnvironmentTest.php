<?php

declare(strict_types=1);

namespace FluidStan\TYPO3\Tests\Unit;

use FluidStan\TYPO3\Typo3Environment;
use PHPUnit\Framework\TestCase;

final class Typo3EnvironmentTest extends TestCase
{
    public function testNamespacesNeedABootedTypo3(): void
    {
        $this->expectException(\LogicException::class);
        (new Typo3Environment())->getNamespaces();
    }

    public function testOnlyExtPathsAreResolved(): void
    {
        $this->assertNull((new Typo3Environment())->resolvePath('/absolute/path'));
        $this->assertNull((new Typo3Environment())->resolvePath('relative/path'));
    }
}
