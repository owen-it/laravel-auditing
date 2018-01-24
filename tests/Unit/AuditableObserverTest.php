<?php
/**
 * This file is part of the Laravel Auditing package.
 *
 * @author     Antério Vieira <anteriovieira@gmail.com>
 * @author     Quetzy Garcia  <quetzyg@altek.org>
 * @author     Raphael França <raphaelfrancabsb@gmail.com>
 * @copyright  2015-2018
 *
 * For the full copyright and license information,
 * please view the LICENSE.md file that was distributed
 * with this source code.
 */

namespace OwenIt\Auditing\Tests;

use OwenIt\Auditing\AuditableObserver;
use OwenIt\Auditing\Tests\Models\Article;

class AuditableObserverTest extends AuditingTestCase
{
    /**
     * @group AuditableObserver::retrieved
     * @group AuditableObserver::created
     * @group AuditableObserver::updated
     * @group AuditableObserver::deleted
     * @group AuditableObserver::restoring
     * @group AuditableObserver::restored
     * @test
     *
     * @dataProvider auditableObserverTestProvider
     *
     * @param string $eventMethod
     * @param bool   $expectedBefore
     * @param bool   $expectedAfter
     */
    public function itExecutesTheAuditorSuccessfully(string $eventMethod, bool $expectedBefore, bool $expectedAfter)
    {
        $observer = new AuditableObserver();
        $model = factory(Article::class)->create();

        $this->assertSame($expectedBefore, $observer::$restoring);

        call_user_func([$observer, $eventMethod], $model);

        $this->assertSame($expectedAfter, $observer::$restoring);
    }

    /**
     * @return array
     */
    public function auditableObserverTestProvider()
    {
        return [
            [
                'retrieved',
                false,
                false,
            ],
            [
                'created',
                false,
                false,
            ],
            [
                'updated',
                false,
                false,
            ],
            [
                'deleted',
                false,
                false,
            ],
            [
                'restoring',
                false,
                true,
            ],
            [
                'restored',
                true,
                false,
            ],
        ];
    }
}
