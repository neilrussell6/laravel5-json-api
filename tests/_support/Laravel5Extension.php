<?php

class Laravel5Extension extends \Codeception\Extension
{
    public static $events = [
        'test.before' => 'beforeTest',
    ];

    public function beforeTest(\Codeception\Event\TestEvent $e) {
        $laravel5 = $this->getModule('Laravel5');
        $app = $laravel5->getApplication();
        $app->make('Illuminate\Database\Eloquent\Factory')->load(__DIR__ . '/../../src-testing/database/factories');
    }
}