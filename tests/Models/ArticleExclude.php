<?php

namespace OwenIt\Auditing\Tests\Models;

use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Tests\Models\Article;

class ArticleExclude extends Article implements Auditable
{
    protected $table = 'articles';

    public static $shouldCreateEmptyAudits = false;

    public function shouldCreateEmptyAudits(): bool
    {
        return ArticleExclude::$shouldCreateEmptyAudits;
    }
    
    protected $auditExclude = [
        'published_at'
    ];
}