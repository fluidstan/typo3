# Changelog

## 0.3.0

Requires `fluidstan/fluidstan` 0.3, which detects the variables ViewHelpers assign, adds a strict mode
and a baseline. All three work in TYPO3 projects without a change to this package; TYPO3's own
ViewHelpers are detected too, `f:link.typolink`'s `partsAs` for instance.

### Changed

- The README describes detected ViewHelper assignments, strict mode and the baseline, and no longer
  lists ViewHelper instantiation through the dependency injection container as planned: argument
  definitions are read without running constructors, which has been sufficient.
- The example extension runs with `strict: true`, and its test expects the nullable path it reports.

## 0.2.0

Requires `fluidstan/fluidstan` 0.2. TYPO3 is booted like a CLI command now, so FluidStan sees exactly
the Fluid configuration TYPO3 sees.

### Changed

- **Full boot instead of failsafe mode.** The failsafe container knew the system extensions only:
  neither `ext_localconf.php` nor `Configuration/Fluid/Namespaces.php` of the project's extensions
  were loaded, so globally registered namespaces had to be repeated in `fluidstan.neon`
  ([#3](https://github.com/fluidstan/typo3/issues/3)). TYPO3 is now booted like a CLI command, without
  a database connection, and the namespaces come from TYPO3's own `ViewHelperResolverFactory`: every
  `Namespaces.php` (TYPO3 14), `TYPO3_CONF_VARS` and the `ModifyNamespacesEvent`, in TYPO3's order.
  A project without `config/system/settings.php` is reported instead of analysed with incomplete
  namespaces; a minimal `settings.php` with `SYS/encryptionKey` is enough.
- `phpstan.level` is gone from the configuration example; the core package rejects it.

### Added

- **Component collections configured in TYPO3 14** through `Configuration/Fluid/ComponentCollections.php`,
  without a PHP class, and collection classes registered through dependency injection
  ([#4](https://github.com/fluidstan/typo3/issues/4)). Their variables come from the
  `ProvideStaticVariablesToComponentEvent` at runtime and are `mixed` in the analysis.
- Everything FluidStan 0.2 brings: variables a collection class provides through
  `getAdditionalVariables()`, Fluid's variable scoping, and narrowing through `f:if`.
- An integration test boots TYPO3 and analyses the example extension, on TYPO3 13.4 and 14.2.

### Requirements

- `fluidstan/fluidstan` ^0.2
- TYPO3 13.4 or 14.2 and newer. 14.1 is not supported: it has the declarative collections but not
  yet the delegate registry that hands them to the resolver.
- A TYPO3 project with `config/system/settings.php`; the package root is such a project (`typo3/minimal`)
  for the example and the test suite.

## 0.1.0

First release of the TYPO3 integration for [FluidStan](https://github.com/fluidstan/fluidstan),
the static analyser for Fluid templates and components powered by PHPStan.

### What it does

- **Boots TYPO3 in failsafe mode**, without a database, so the package manager, extension paths and
  `settings.php` are available while templates are parsed.
- **Registers TYPO3's Fluid namespaces**: `f` includes `TYPO3\CMS\Fluid\ViewHelpers`, and every
  namespace from `$GLOBALS['TYPO3_CONF_VARS']['SYS']['fluid']['namespaces']` is picked up.
- **Resolves `EXT:` paths** in the configuration.
- **Reads ViewHelper argument definitions without dependency injection**: ViewHelpers that resolve
  services in their constructor, such as `f:image`, are instantiated without it.

### Requirements

- PHP 8.2 or newer, Composer mode
- TYPO3 13.4 or 14
- `fluidstan/fluidstan` ^0.1 (installed automatically)

### Getting started

```bash
composer require --dev fluidstan/typo3
```

`fluidstan.neon` next to `composer.json`:

```neon
parameters:
    environment: FluidStan\TYPO3\Typo3Environment
    bootstrapFiles:
        - vendor/autoload.php
    paths:
        - EXT:my_site/Resources/Private/Components
    namespaces:
        my: My\Site\Components\ComponentCollection
    tmpDir: var/cache/fluidstan
```

Then run `vendor/bin/fluidstan` where TYPO3 runs, for example inside the PHP container.

### What gets checked

Components typed through `f:argument` are analysed against your PHP classes, including Extbase
models and `ObjectStorage` relations. FluidStan reports unresolvable property paths, values that
cannot be rendered such as an unformatted `DateTimeImmutable`, loops over non-iterables, wrong
ViewHelper and component argument types, undefined variables, unknown slots and parser errors.
See the README for a screenshot of the analysis of the bundled example extension.

### Known limitations

- Variables of plain templates, partials and layouts are `mixed` unless declared with a `@var`
  annotation; `{settings}`, `{data}`, `{page}` and friends are not typed yet.
- Partials and layouts are not resolved through TYPO3's template paths yet.
- ViewHelpers are not instantiated through the DI container.

These are the next items on the roadmap.
