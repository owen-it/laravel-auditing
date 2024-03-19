<?php

namespace OwenIt\Auditing\Tests\Models;

class ArticleExcludes extends Article
{
    protected $table = 'articles';

    public $auditExclude = ['title'];
}
