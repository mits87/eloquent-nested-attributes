<?php

require __DIR__.'/vendor/autoload.php';

return (new MattAllan\LaravelCodeStyle\Config())
    ->setFinder(
        PhpCsFixer\Finder::create()->in('./')
    )
    ->setRules([
        '@Laravel'       => true,
        '@Laravel:risky' => true,
        'concat_space'   => [ 'spacing' => 'one' ],
    ])
    ->setRiskyAllowed(true);
