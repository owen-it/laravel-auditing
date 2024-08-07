<?php

namespace OwenIt\Auditing\Tests\Unit;

use OwenIt\Auditing\Tests\Models\Article;
use PHPUnit\Framework\TestCase;

class OutsideOfAppContextTest extends TestCase
{
    public function test_can_create_new_model(): void
    {
        $article = new Article();
        $this->assertInstanceOf(Article::class, $article);
    }
}
