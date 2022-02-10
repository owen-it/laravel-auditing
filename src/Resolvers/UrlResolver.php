<?php

namespace OwenIt\Auditing\Resolvers;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Request;
use OwenIt\Auditing\Contracts\Auditable;

class UrlResolver implements \OwenIt\Auditing\Contracts\Resolver
{
    /**
     * @return string
     */
    public static function resolve(Auditable $auditable): string
    {
        if (App::runningInConsole()) {
            return 'console';
        }

        return Request::fullUrl();
    }
}
