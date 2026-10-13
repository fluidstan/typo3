<?php

declare(strict_types=1);

namespace FluidStan\TYPO3;

use Composer\Autoload\ClassLoader;
use FluidStan\Configuration\Configuration;
use FluidStan\Environment\EnvironmentInterface;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Boots TYPO3 in failsafe mode (no database) so that extension paths, the package manager and
 * the Fluid namespaces registered in $GLOBALS['TYPO3_CONF_VARS']['SYS']['fluid']['namespaces']
 * are available during analysis.
 *
 * fluidstan.neon:
 *
 *   parameters:
 *       environment: FluidStan\TYPO3\Typo3Environment
 *       bootstrapFiles:
 *           - vendor/autoload.php
 *       paths:
 *           - EXT:my_site/Resources/Private/Components
 */
final class Typo3Environment implements EnvironmentInterface
{
    public function boot(Configuration $configuration): void
    {
        if (\defined('TYPO3')) {
            return;
        }

        if (!class_exists(Bootstrap::class)) {
            throw new \RuntimeException('TYPO3 is not autoloadable. Add the project\'s vendor/autoload.php to "bootstrapFiles".');
        }

        $classLoader = $this->findClassLoader();
        SystemEnvironmentBuilder::run(0, SystemEnvironmentBuilder::REQUESTTYPE_CLI);
        Bootstrap::init($classLoader, true);
    }

    public function getNamespaces(): array
    {
        $namespaces = [
            'f' => ['TYPO3Fluid\\Fluid\\ViewHelpers', 'TYPO3\\CMS\\Fluid\\ViewHelpers'],
        ];
        foreach ($this->configuredNamespaces() as $identifier => $phpNamespaces) {
            $list = [];
            foreach (\is_array($phpNamespaces) ? $phpNamespaces : [$phpNamespaces] as $phpNamespace) {
                if (\is_string($phpNamespace)) {
                    $list[] = $phpNamespace;
                }
            }

            $namespaces[(string) $identifier] = array_merge($namespaces[(string) $identifier] ?? [], $list);
        }

        return $namespaces;
    }

    public function resolvePath(string $path): ?string
    {
        if (!str_starts_with($path, 'EXT:')) {
            return null;
        }

        $resolved = GeneralUtility::getFileAbsFileName($path);
        return $resolved === '' ? null : $resolved;
    }

    public function getPhpStanBootstrapFiles(): array
    {
        return [];
    }

    /**
     * $GLOBALS['TYPO3_CONF_VARS']['SYS']['fluid']['namespaces'], if TYPO3 is booted and it is set
     *
     * @return array<array-key, mixed>
     */
    private function configuredNamespaces(): array
    {
        $value = $GLOBALS['TYPO3_CONF_VARS'] ?? null;
        foreach (['SYS', 'fluid', 'namespaces'] as $key) {
            if (!\is_array($value) || !\array_key_exists($key, $value)) {
                return [];
            }

            $value = $value[$key];
        }

        return \is_array($value) ? $value : [];
    }

    private function findClassLoader(): ClassLoader
    {
        foreach (ClassLoader::getRegisteredLoaders() as $vendorDir => $loader) {
            if (is_dir($vendorDir . '/typo3/cms-core')) {
                return $loader;
            }
        }

        throw new \RuntimeException('No Composer class loader with typo3/cms-core found.');
    }
}
