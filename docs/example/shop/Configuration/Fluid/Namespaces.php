<?php

declare(strict_types=1);

// TYPO3 14 registers the namespaces of every extension globally from this file; FluidStan picks them up.
return [
    'shop' => [
        \Vendor\Shop\Components\ComponentCollection::class,
    ],
];
