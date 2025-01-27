<?php

namespace OwenIt\Auditing\Resolvers;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Request;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Contracts\Resolver;

class UrlResolver implements Resolver
{
    public static function resolve(Auditable $auditable): string
    {
        if (! empty($auditable->preloadedResolverData['url'] ?? null)) {
            return $auditable->preloadedResolverData['url'] ?? '';
        }

        if (App::runningInConsole()) {
            return self::resolveCommandLine();
        }

        return Request::fullUrl();
    }

    public static function resolveCommandLine(): string
    {
        $command = Request::server('argv', null);
        if (is_array($command)) {
            return implode(' ', $command);
        }

        return 'console';
    }
}
