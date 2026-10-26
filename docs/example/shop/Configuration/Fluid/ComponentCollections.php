<?php

declare(strict_types=1);

// TYPO3 14: a component collection defined by configuration alone, without a PHP class.
// The key is the namespace string templates import; Namespaces.php registers it globally as "ui".
return [
    'Vendor\\Shop\\Elements' => [
        'templatePaths' => [
            10 => 'EXT:shop/Resources/Private/Elements',
        ],
    ],
];
