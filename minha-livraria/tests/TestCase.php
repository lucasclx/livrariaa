<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

trait CreatesApplication
{
    public function createApplication()
    {
        $app = require __DIR__.'/../bootstrap/app.php';
        $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

        return $app;
    }
}

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;
}
