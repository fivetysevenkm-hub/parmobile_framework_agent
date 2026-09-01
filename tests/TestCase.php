<?php

namespace Tests;

use App\Support\SchemaCache;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        SchemaCache::flush();
    }
}
