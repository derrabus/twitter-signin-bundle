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

    public function testGetAccessToken()
    {
        $requestToken = $this->generateTokenPair();
        $oauthVerifier = 'foobar';

        $this->oauthMock->expects($this->at(0))
            ->method('setToken')
            ->with($requestToken['oauth_token'], $requestToken['oauth_token_secret']);
        $this->oauthMock->expects($this->at(1))
            ->method('getAccessToken')
            ->with('https://api.twitter.com/oauth/access_token', null, $oauthVerifier)
            ->will($this->returnValue($requestToken));

        $this->assertEquals(
            $requestToken,
            $this->twitter->getAccessToken($requestToken, $oauthVerifier)
        );
    }

    /**
     * @expectedException \Rabus\Bundle\Twitter\SignInBundle\Service\TwitterApiException
     */
    public function testGetAccessTokenThrowsException()
    {
        $this->oauthMock->expects($this->any())
            ->method($this->anything())
            ->will($this->throwException(new \OAuthException()));

        $this->twitter->getAccessToken($this->generateTokenPair(), 'foobar');
    }

    public function testGetRequestToken()
    {
        $callbackUrl = 'http://www.example.com/twitter/callback';
        $requestToken = $this->generateTokenPair();

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

        $this->twitter->getRequestToken('http://www.example.com/twitter/callback');
    }

    public function testGenerateAuthRedirectUrl()
    {
        $this->assertEquals(
            'https://api.twitter.com/oauth/authenticate?oauth_token=foo',
            $this->twitter->generateAuthRedirectUrl($this->generateTokenPair())
        );
    }

    /**
     * @return array
     */
    private function generateTokenPair()
    {
        return array(
            'oauth_token' => 'foo',
            'oauth_token_secret' => 'bar'
        );
    }
}
