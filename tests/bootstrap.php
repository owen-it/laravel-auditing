<?php

require __DIR__.'/../vendor/autoload.php';

if (class_exists('Illuminate\Foundation\Testing\Assert')) {
    class_alias('Illuminate\Foundation\Testing\Assert', 'Illuminate\Testing\Assert');
}

if (class_exists('\Illuminate\Foundation\Testing\PendingCommand')) {
    class_alias('Illuminate\Foundation\Testing\PendingCommand', 'Illuminate\Testing\PendingCommand');
}
