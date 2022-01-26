<?php

namespace OwenIt\Auditing\Resolvers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use OwenIt\Auditing\Contracts\Resolver;

class UserResolver implements Resolver
{
    /**
     * {@inheritdoc}
     */
    public static function resolve()
    {
        $guards = Config::get('audit.user.guards', [
            'web',
            'api',
        ]);

        foreach ($guards as $guard) {
            if (Auth::guard($guard)->check()) {
                return Auth::guard($guard)->user();
            }
        }
    }
}
