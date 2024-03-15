<?php

namespace OwenIt\Auditing\Resolvers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use OwenIt\Auditing\Contracts\Auditable;

class UserResolver implements \OwenIt\Auditing\Contracts\UserResolver
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
            \config('auth.defaults.guard')
        ]);

        foreach ($guards as $guard) {
            try {
                $authenticated = Auth::guard($guard)->check();
            } catch (\Exception $exception) {
                continue;
            }

            if (true === $authenticated) {
                return Auth::guard($guard)->user();
            }
        }

        return null;
    }
}
