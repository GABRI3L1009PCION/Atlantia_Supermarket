<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

/**
 * Caso base para pruebas Laravel de Atlantia.
 */
abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;
}
