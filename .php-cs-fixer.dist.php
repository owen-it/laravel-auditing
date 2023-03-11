<?php

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__ . '/src')
    ->name('*.php');

$config = new PhpCsFixer\Config();
return $config->setRules([
    '@PSR12'       => true,
    'strict_param' => true,
    'array_syntax' => ['syntax' => 'short'],
])
    ->setFinder($finder);