<?php

namespace Tests;

use Mockery;
use OwenIt\Auditing\CustomAuditMessage;

class CustomAuditMessageTest extends AbstractTestCase
{
    public function testItResolveCustomMessage()
    {
        $messages = [
            'ip_address'   => 'Registered from the address {ip_address}',
            'author'       => '{user.name|An anonymous user} {type} a post',
            'username'     => 'The user name has been modified',
            'my_title'     => 'The title was defined as "{new.title||customTitleMessage}"',
            'published'    => 'Post published at {new.published_at}',
        ];

        $auditable = Mockery::mock('Illuminate\Database\Eloquent\Model');
        $auditable->shouldReceive('customTitleMessage')->once()
                  ->andReturn('My Laracast');

        $auditing = new AuditingModelTest($auditable);

        $result['ip_address'] = $auditing->resolveCustomMessage($messages['ip_address']);
        $result['author'] = $auditing->resolveCustomMessage($messages['author']);
        $result['username'] = $auditing->resolveCustomMessage($messages['username']);
        $result['my_title'] = $auditing->resolveCustomMessage($messages['my_title']);
        $result['published'] = $auditing->resolveCustomMessage($messages['published']);

        $this->assertEquals('Registered from the address ::1', $result['ip_address']);
        $this->assertEquals('An anonymous user created a post', $result['author']);
        $this->assertEquals('The user name has been modified', $result['username']);
        $this->assertEquals('The title was defined as "My Laracast"', $result['my_title']);
        $this->assertEquals(null, $result['published']);
    }
}

class AuditingModelTest
{
    use CustomAuditMessage;

    protected $ip_address = '::1';

    protected $type = 'created';

    protected $auditable = null;

    public function __construct($auditable)
    {
        $this->auditable = $auditable;
    }
}
