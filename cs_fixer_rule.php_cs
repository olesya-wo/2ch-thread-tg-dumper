<?php

$finder = PhpCsFixer\Finder::create()->in(__DIR__);

$config = new PhpCsFixer\Config();

return $config->setRules([
    '@PhpCsFixer' => true,
    'array_syntax' => ['syntax' => 'short'],
    'list_syntax' => ['syntax' => 'short'],
    'increment_style' => ['style' => 'post'],
    'braces' => ['position_after_functions_and_oop_constructs' => 'same'],
    'mb_str_functions' => true,
    'array_push' => true,
    'yoda_style' => ['equal' => false, 'identical' => false, 'less_and_greater' => false],
    'no_homoglyph_names' => true,
    'blank_line_before_statement' => ['statements' => []],
    'declare_strict_types' => true
])
    ->setFinder($finder)
;
