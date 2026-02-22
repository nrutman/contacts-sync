<?php

$finder = new PhpCsFixer\Finder()->in(__DIR__)->exclude("var");

return new PhpCsFixer\Config()
    ->setRiskyAllowed(false)
    ->setRules([
        "@Symfony" => true,
        "@PSR12" => true,
        "array_syntax" => ["syntax" => "short"],
        "phpdoc_align" => ["align" => "left"],
        "yoda_style" => [
            "equal" => false,
            "identical" => false,
            "less_and_greater" => false,
        ],
        "phpdoc_to_comment" => false,
    ])
    ->setFinder($finder);
