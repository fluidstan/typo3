<?php

declare(strict_types=1);

namespace FluidStan\TYPO3;

use Composer\Autoload\ClassLoader;
use FluidStan\Configuration\Configuration;
use FluidStan\Environment\EnvironmentInterface;
use Psr\Container\ContainerInterface;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\DependencyInjection\FailsafeContainer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\Core\ViewHelper\ViewHelperResolverFactoryInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\ViewHelperResolverDelegateInterface;

/**
 * Boots TYPO3 the way a CLI command does, without opening a database connection, so that the
 * dependency injection container, extension paths and the global Fluid namespaces are available
 * during analysis.
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
    private ?ContainerInterface $container = null;

    public function boot(Configuration $configuration): void
    {
        if (\defined('TYPO3')) {
            $this->container = GeneralUtility::getContainer();
            return;
        }

        if (!class_exists(Bootstrap::class)) {
            throw new \RuntimeException('TYPO3 is not autoloadable. Add the project\'s vendor/autoload.php to "bootstrapFiles".');
        }

        $classLoader = $this->findClassLoader();
        SystemEnvironmentBuilder::run(0, SystemEnvironmentBuilder::REQUESTTYPE_CLI);
        $this->container = Bootstrap::init($classLoader);
        if ($this->container instanceof FailsafeContainer) {
            // TYPO3 falls back to the failsafe container when config/system/settings.php is missing. That
            // container knows the system extensions only: neither ext_localconf.php nor the Fluid
            // configuration of the project's extensions would be loaded.
            throw new \RuntimeException('TYPO3 could not be booted completely because config/system/settings.php is missing. FluidStan needs a configured project; a minimal settings.php with SYS/encryptionKey is enough, no database is used.');
        }
    }

    /**
     * The global namespaces as TYPO3 assembles them for its own ViewHelperResolver: every
     * Configuration/Fluid/Namespaces.php (TYPO3 14), $GLOBALS['TYPO3_CONF_VARS']['SYS']['fluid']['namespaces']
     * set in ext_localconf.php files and the result of the ModifyNamespacesEvent.
     */
    public function getNamespaces(): array
    {
        $namespaces = [
            'f' => ['TYPO3Fluid\\Fluid\\ViewHelpers', 'TYPO3\\CMS\\Fluid\\ViewHelpers'],
        ];
        foreach ($this->container()->get(ViewHelperResolverFactoryInterface::class)->create()->getNamespaces() as $identifier => $phpNamespaces) {
            $list = $namespaces[$identifier] ?? [];
            foreach (\is_array($phpNamespaces) ? $phpNamespaces : [$phpNamespaces] as $phpNamespace) {
                // Ignored namespaces (null) have nothing to analyse; delegates are registered by class name
                if (\is_string($phpNamespace) && !\in_array($phpNamespace, $list, true)) {
                    $list[] = $phpNamespace;
                }
            }

            $namespaces[$identifier] = $list;
        }

        return $namespaces;
    }

    /**
     * @return array<string, ViewHelperResolverDelegateInterface>
     */
    public function getResolverDelegates(): array
    {
        return [];
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

    private function container(): ContainerInterface
    {
        if (!$this->container instanceof ContainerInterface) {
            throw new \LogicException('TYPO3 is not booted; boot() must be called first.');
        }

        return $this->container;
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
