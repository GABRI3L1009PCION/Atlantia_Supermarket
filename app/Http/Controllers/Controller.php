<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

/**
 * Controlador base de la aplicacion.
 */
abstract class Controller
{
    use AuthorizesRequests;
}
