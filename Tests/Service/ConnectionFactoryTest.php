<?php

namespace Rabus\Bundle\Twitter\SignInBundle\Tests\Service;

use Rabus\Bundle\Twitter\SignInBundle\Service\ConnectionFactory;

class ConnectionFactoryTest extends \PHPUnit_Framework_TestCase
{
    public function testGetOauthConnection()
    {
        $factory = new ConnectionFactory('foo', 'bar');
        $client = $factory->getOAuthConnection();

        $this->assertInstanceOf('Guzzle\\Http\\Client', $client);
    }

    public function testGetOauthConnectionWithToken()
    {
        $factory = new ConnectionFactory('foo', 'bar');
        $client = $factory->getOAuthConnection('foobar', 'barfoo');

        $this->assertInstanceOf('Guzzle\\Http\\Client', $client);
    }
} 
