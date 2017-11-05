<?php
/**
 * This file is part of the Laravel Auditing package.
 *
 * @author     Antério Vieira <anteriovieira@gmail.com>
 * @author     Quetzy Garcia  <quetzyg@altek.org>
 * @author     Raphael França <raphaelfrancabsb@gmail.com>
 * @copyright  2015-2017
 *
 * For the full copyright and license information,
 * please view the LICENSE.md file that was distributed
 * with this source code.
 */

namespace OwenIt\Auditing\Tests\Functional;

use Illuminate\Support\Facades\App;
use OwenIt\Auditing\Models\Audit;
use OwenIt\Auditing\Tests\AuditingTestCase;
use OwenIt\Auditing\Tests\Models\User;

class AuditingTest extends AuditingTestCase
{
    /**
     * @test
     */
    public function it_will_not_audit_models_when_running_from_the_console()
    {
        $this->app['config']->set('audit.console', false);

        factory(User::class)->create();

        $this->assertEquals(1, User::query()->count());
        $this->assertEquals(0, Audit::query()->count());
    }

    /**
     * @test
     */
    public function it_will_audit_models_when_running_from_the_console()
    {
        $this->app['config']->set('audit.console', true);

        factory(User::class)->create();

        $this->assertEquals(1, User::query()->count());
        $this->assertEquals(1, Audit::query()->count());
    }

    /**
     * @test
     */
    public function it_will_always_audit_models_when_not_running_from_the_console()
    {
        App::shouldReceive('runningInConsole')
            ->andReturn(false);

        $this->app['config']->set('audit.console', false);

        factory(User::class)->create();

        $this->assertEquals(1, User::query()->count());
        $this->assertEquals(1, Audit::query()->count());
    }
}