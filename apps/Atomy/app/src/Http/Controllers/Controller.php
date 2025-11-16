<?php

declare(strict_types=1);

namespace Nexus\Atomy\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

abstract class Controller
{
    use AuthorizesRequests;
}
