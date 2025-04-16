<?php

namespace OwenIt\Auditing\Resolvers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use OwenIt\Auditing\Contracts\UserResolver as Resolver;

class UserResolver implements Resolver
{
    /**
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public static function resolve()
    {
        $guardsConfig = Config::get('auth.guards');
        $guards = Config::get('audit.user.guards', [
            Config::get('auth.defaults.guard'),
        ]);

        foreach ($guards as $guard) {
            if (($guardsConfig[$guard]['driver'] ?? null) === 'sanctum') {
                if ($user = Auth::user()) {
                    return $user;
                }

                continue;
            }

            try {
                if ($user = Auth::guard($guard)->user()) {
                    return $user;
                }
            } catch (\Exception $exception) {
                continue;
            }
        }

        return null;
    }
}
