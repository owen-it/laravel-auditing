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
        // supports https://github.com/404labfr/laravel-impersonate
        if (app()->bound('impersonate')) {
            /** @var \Lab404\Impersonate\Services\ImpersonateManager */
            $impersonate = app('impersonate');
            if ($impersonate->isImpersonating()) {
                return $impersonate->findUserById($impersonate->getImpersonatorId());
            }
        }

        $guards = Config::get('audit.user.guards', [
            Config::get('auth.defaults.guard'),
        ]);

        foreach ($guards as $guard) {
            try {
                $authenticated = Auth::guard($guard)->check();
            } catch (\Exception $exception) {
                continue;
            }

            if ($authenticated === true) {
                return Auth::guard($guard)->user();
            }
        }

        return null;
    }
}
