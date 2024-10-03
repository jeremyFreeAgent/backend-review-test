<?php

$finder = (new PhpCsFixer\Finder())
    ->in(__DIR__)
    ->exclude([
        'var',
        'vendor',
        'vendor-bin',
    ])
    ->notPath([
        'config/preload.php',
        'public/index.php',
        'tests/bootstrap.php',
    ])
;

return (new PhpCsFixer\Config())
    ->setParallelConfig(PhpCsFixer\Runner\Parallel\ParallelConfigFactory::detect())
    ->setRiskyAllowed(true)
    ->setRules([
        '@PER-CS2.0' => true,
        '@PHP82Migration' => true,
        '@Symfony' => true,
        'final_class' => true,
        'yoda_style' => false,
        'no_trailing_whitespace' => true,
        'declare_strict_types' => true,
    ])
    ->setFinder($finder)
;
