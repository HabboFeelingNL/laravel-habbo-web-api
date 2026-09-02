<?php

namespace HabboFeeling\HabboWebApi\Tests;

use HabboFeeling\HabboWebApi\HabboApiServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use Spatie\LaravelData\LaravelDataServiceProvider;

abstract class TestCase extends Orchestra
{
    /**
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            LaravelDataServiceProvider::class,
            HabboApiServiceProvider::class,
        ];
    }
}
