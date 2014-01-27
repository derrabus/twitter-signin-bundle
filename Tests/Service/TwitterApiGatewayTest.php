<?php

namespace Rabus\Bundle\Twitter\SignInBundle\Tests\Service;

use Guzzle\Http\Client;
use Guzzle\Http\Exception\CurlException;
use Guzzle\Plugin\Mock\MockPlugin;
use Rabus\Bundle\Twitter\SignInBundle\Service\ConnectionFactory;
use Rabus\Bundle\Twitter\SignInBundle\Service\TwitterApiGateway;

class TwitterApiGatewayTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ConnectionFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $factoryMock;

    /**
     * @var MockPlugin
     */
    private $mockPlugin;

    protected function setUp()
    {
        parent::setUp();

        $this->factoryMock = $this->getMockBuilder('Rabus\\Bundle\\Twitter\\SignInBundle\\Service\\ConnectionFactory')
            ->disableOriginalConstructor()
            ->getMock();

        $this->mockPlugin = new MockPlugin;
    }

    /**
     * @return TwitterApiGateway
     */
    private function bootstrapGateway()
    {
        return new TwitterApiGateway($this->factoryMock);
    }

    /**
     * @return Client
     */
    private function bootstrapClient()
    {
        $client = new Client('https://api.example.com');
        $client->addSubscriber($this->mockPlugin);

        return $client;
    }

    public function testGetAccessToken()
    {
        $requestToken = $this->generateTokenPair();
        $oauthVerifier = 'foobar';

        $this->factoryMock->expects($this->once())
            ->method('getOAuthConnection')
            ->with($requestToken['oauth_token'], $requestToken['oauth_token_secret'])
            ->will($this->returnValue($this->bootstrapClient()));

        $this->mockPlugin->addResponse(__DIR__ . '/fixtures/access_token.txt');

        $accessToken = $this->bootstrapGateway()->getAccessToken($requestToken, $oauthVerifier);

        $this->assertEquals('12-foo', $accessToken['oauth_token']);
        $this->assertEquals('foobar', $accessToken['oauth_token_secret']);

        $receivedRequests = $this->mockPlugin->getReceivedRequests();
        $this->assertCount(1, $receivedRequests);
        $this->assertEquals($oauthVerifier, $receivedRequests[0]->getHeader('oauth_verifier'));
    }

    /**
     * @expectedException \Rabus\Bundle\Twitter\SignInBundle\Service\TwitterApiException
     */
    public function testGetAccessTokenThrowsException()
    {
        $this->factoryMock->expects($this->any())
            ->method('getOAuthConnection')
            ->will($this->returnValue($this->bootstrapClient()));

        $this->mockPlugin->addException(new CurlException());

        $this->bootstrapGateway()->getAccessToken($this->generateTokenPair(), 'foobar');
    }

    public function testGetRequestToken()
    {
        $callbackUrl = 'http://www.example.com/twitter/callback';

        $this->factoryMock->expects($this->once())
            ->method('getOAuthConnection')
            ->with()
            ->will($this->returnValue($this->bootstrapClient()));

        $this->mockPlugin->addResponse(__DIR__ . '/fixtures/request_token.txt');

        $requestToken = $this->bootstrapGateway()->getRequestToken($callbackUrl);

        $this->assertEquals('foo', $requestToken['oauth_token']);
        $this->assertEquals('bar', $requestToken['oauth_token_secret']);

        $receivedRequests = $this->mockPlugin->getReceivedRequests();
        $this->assertCount(1, $receivedRequests);
        $this->assertEquals($callbackUrl, $receivedRequests[0]->getHeader('oauth_callback'));
    }

    /**
     * @expectedException \Rabus\Bundle\Twitter\SignInBundle\Service\TwitterApiException
     */
    public function testGetRequestTokenThrowsException()
    {
        $this->factoryMock->expects($this->any())
            ->method('getOAuthConnection')
            ->will($this->returnValue($this->bootstrapClient()));

        $this->mockPlugin->addException(new CurlException());

        $this->bootstrapGateway()->getRequestToken('http://www.example.com/twitter/callback');
    }

    public function testGenerateAuthRedirectUrl()
    {
        $this->assertEquals(
            'https://api.twitter.com/oauth/authenticate?oauth_token=foo',
            $this->bootstrapGateway()->generateAuthRedirectUrl($this->generateTokenPair())
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
