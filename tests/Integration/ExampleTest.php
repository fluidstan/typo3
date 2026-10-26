<?php

declare(strict_types=1);

namespace FluidStan\TYPO3\Tests\Integration;

use FluidStan\Analyser;
use FluidStan\Configuration\ConfigurationLoader;
use FluidStan\Diagnostics\Diagnostic;
use FluidStan\TYPO3\Typo3Environment;
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Fluid\Core\ViewHelper\ViewHelperResolverDelegateRegistry;
use TYPO3Fluid\Fluid\Core\ViewHelper\ViewHelperResolverDelegateInterface;
use Vendor\Shop\Components\ComponentCollection;

/**
 * Boots TYPO3 and runs the whole pipeline, including PHPStan, on the example extension.
 */
final class ExampleTest extends TestCase
{
    private int $outputBufferLevel = 0;

    protected function setUp(): void
    {
        $this->outputBufferLevel = ob_get_level();
    }

    protected function tearDown(): void
    {
        // Bootstrap::init() installs TYPO3's error and exception handlers and starts an output buffer
        while (ob_get_level() > $this->outputBufferLevel) {
            ob_end_clean();
        }

        restore_error_handler();
        restore_exception_handler();
    }

    public function testExampleExtensionReportsExpectedErrors(): void
    {
        $root = \dirname(__DIR__, 2) . '/docs/example';
        $configuration = (new ConfigurationLoader())->load($root . '/fluidstan.neon', $root)
            ->withTmpDir(sys_get_temp_dir() . '/fluidstan-typo3-test-' . getmypid());
        $autoload = \dirname(__DIR__, 2) . '/vendor/autoload.php';
        $result = (new Analyser($configuration, $autoload, \dirname($autoload) . '/bin/phpstan'))->analyse();

        $actual = array_map(
            static fn(Diagnostic $d): string => \sprintf('%s:%s %s', basename($d->file), $d->line, $d->identifier),
            $result->diagnostics->all(),
        );
        // Namespaces.php, ComponentCollections.php and the delegate registry all arrived with TYPO3 14.1
        $declarativeCollections = class_exists(ViewHelperResolverDelegateRegistry::class);
        $this->assertSame(array_merge([
            'ProductTeaser.html:10 fluidstan.property.unresolvable',
            'ProductTeaser.html:11 fluidstan.output.object',
            'ProductTeaser.html:13 fluidstan.argument.type',
            'ProductTeaser.html:14 fluidstan.argument.type',
            'ProductTeaser.html:16 fluidstan.property.unresolvable',
            'ProductTeaser.html:18 variable.undefined',
        ], $declarativeCollections ? [
            // The "ui" collection comes from Configuration/Fluid/ComponentCollections.php; its variables
            // are provided by a PSR-14 event at runtime, so an unknown variable cannot be reported
            'ProductTeaser.html:20 fluidstan.argument.type',
            'Badge.html:5 fluidstan.property.unresolvable',
        ] : [
            // TYPO3 13 has no declarative collections: "ui" is not a namespace, Badge is a component without a collection
            'ProductTeaser.html:20 fluid.parser',
            'ProductTeaser.html:20 fluidstan.output.object',
            'Badge.html:5 fluidstan.property.unresolvable',
            'Badge.html:5 variable.undefined',
        ]), $actual);
        $this->assertSame(3, $result->templateCount);

        // TYPO3 is booted by the analysis above; the environment reads the global namespaces from it
        $environment = new Typo3Environment();
        $environment->boot($configuration);

        $namespaces = $environment->getNamespaces();
        $this->assertSame(['TYPO3Fluid\\Fluid\\ViewHelpers', 'TYPO3\\CMS\\Fluid\\ViewHelpers'], $namespaces['f']);
        $delegates = $environment->getResolverDelegates();
        if ($declarativeCollections) {
            // Registered globally by the extension's Configuration/Fluid/Namespaces.php (TYPO3 14.1)
            $this->assertSame([ComponentCollection::class], $namespaces['shop'] ?? null);
            $this->assertSame(['Vendor\\Shop\\Elements'], $namespaces['ui'] ?? null);
            // Built by TYPO3 from Configuration/Fluid/ComponentCollections.php
            $this->assertInstanceOf(ViewHelperResolverDelegateInterface::class, $delegates['Vendor\\Shop\\Elements'] ?? null);
        } else {
            $this->assertArrayNotHasKey('shop', $namespaces);
            $this->assertSame([], $delegates);
        }
    }
}
