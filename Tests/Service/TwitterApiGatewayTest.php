<?php

namespace Rabus\Bundle\Twitter\SignInBundle\Tests\Service;

use Rabus\Bundle\Twitter\SignInBundle\Service\TwitterApiGateway;

class TwitterApiGatewayTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \OAuth|\PHPUnit_Framework_MockObject_MockObject
     */
    private $oauthMock;

    /**
     * @var TwitterApiGateway
     */
    private $twitter;

    protected function setUp()
    {
        parent::setUp();

        $this->oauthMock = $this->getMockBuilder('OAuth')
            ->disableOriginalConstructor()
            ->getMock();

        $this->twitter = new TwitterApiGateway($this->oauthMock);
    }

    public function testGetRequestToken()
    {
        $callbackUrl = 'http://www.exaple.com/twitter/callback';
        $requestToken = array(
            'oauth_token' => 'foo',
            'oauth_token_secret' => 'bar'
        );

        $this->oauthMock->expects($this->once())
            ->method('getRequestToken')
            ->with('https://api.twitter.com/oauth/request_token', $callbackUrl)
            ->will($this->returnValue($requestToken));

        $this->assertEquals($requestToken, $this->twitter->getRequestToken($callbackUrl));
    }

    /**
     * @expectedException \Rabus\Bundle\Twitter\SignInBundle\Service\TwitterApiException
     */
    public function testGetRequestTokenThrowsException()
    {
        $this->oauthMock->expects($this->any())
            ->method($this->anything())
            ->will($this->throwException(new \OAuthException()));

        $this->twitter->getRequestToken('http://www.exaple.com/twitter/callback');
    }
}
