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
        if (!\defined('TYPO3')) {
            if (!class_exists(Bootstrap::class)) {
                throw new \RuntimeException('TYPO3 is not autoloadable. Add the project\'s vendor/autoload.php to "bootstrapFiles".');
            }

            SystemEnvironmentBuilder::run(0, SystemEnvironmentBuilder::REQUESTTYPE_CLI);
            Bootstrap::init($this->findClassLoader());
        }

        // Whoever booted TYPO3: it falls back to the failsafe container when config/system/settings.php
        // is missing. That container knows the system extensions only, so neither ext_localconf.php nor
        // the Fluid configuration of the project's extensions would be loaded.
        $container = GeneralUtility::getContainer();
        if ($container instanceof FailsafeContainer) {
            throw new \RuntimeException('TYPO3 could not be booted completely because config/system/settings.php is missing. FluidStan needs a configured project; a minimal settings.php with SYS/encryptionKey is enough, no database is used.');
        }

        $this->container = $container;
    }

    /**
     * The global namespaces as TYPO3 assembles them for its own ViewHelperResolver, in TYPO3's order:
     * every Configuration/Fluid/Namespaces.php (TYPO3 14.1), $GLOBALS['TYPO3_CONF_VARS']['SYS']['fluid']['namespaces']
     * set in ext_localconf.php files and the result of the ModifyNamespacesEvent. Fluid resolves a
     * ViewHelper from the last entry of a namespace backwards, so the order matters.
     *
     * @return array<string, list<string>|null>
     */
    public function getNamespaces(): array
    {
        $namespaces = [];
        foreach ($this->container()->get(ViewHelperResolverFactoryInterface::class)->create()->getNamespaces() as $identifier => $entries) {
            if ($entries === null) {
                // An ignored namespace: Fluid leaves its tags untouched
                $namespaces[$identifier] = null;
                continue;
            }

            $list = [];
            foreach ($entries as $entry) {
                if (\is_string($entry)) {
                    $list[] = $entry;
                }
            }

            $namespaces[$identifier] = $list;
        }

        // TYPO3 always registers "f"; keep Fluid's defaults should a setup have removed it. A setup that
        // ignores it on purpose (null) keeps that.
        if (!\array_key_exists('f', $namespaces)) {
            $namespaces['f'] = ['TYPO3Fluid\\Fluid\\ViewHelpers', 'TYPO3\\CMS\\Fluid\\ViewHelpers'];
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
