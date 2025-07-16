<?php

$config = new PhpCsFixer\Config();
$config
    ->setCacheFile(__DIR__ . '/.php-cs-fixer.cache')
    ->setRules(
        [
            '@PSR12' => true,
            'array_syntax' => ['syntax' => 'short'],
            'trailing_comma_in_multiline' => true,
            'yoda_style' => true,
        ]
    )
    ->setFinder(
        PhpCsFixer\Finder::create()
            ->in(
                [
                    __DIR__ . '/src/',
                ]
            )
    );

return $config;
