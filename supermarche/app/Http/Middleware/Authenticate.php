<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    protected function redirectTo($request)
    {
        // Ne pas rediriger pour les requêtes API
        if ($request->expectsJson()) {
            return null;
        }

        // Si ce n'est pas une requête API, redirigez vers une page existante
        return route('home'); // Assurez-vous que cette route existe, sinon remplacez-la
    }
}
