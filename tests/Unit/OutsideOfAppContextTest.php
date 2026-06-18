<?php

namespace OwenIt\Auditing\Tests\Unit;

use Illuminate\Support\Facades\App;
use OwenIt\Auditing\Tests\Models\Article;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;

class OutsideOfAppContextTest extends TestCase
{
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_can_create_new_model(): void
    {
        $this->assertNull(App::getFacadeRoot());

        $article = new Article();
        $this->assertInstanceOf(Article::class, $article);
        $this->assertNull(Article::getEventDispatcher());
    }
}
