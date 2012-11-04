<?php

namespace Rabus\Bundle\Twitter\SignInBundle\Tests\Controller;

use Rabus\Bundle\Twitter\SignInBundle\Controller\TwitterSignInController;
use Rabus\Bundle\Twitter\SignInBundle\Service\TwitterApiGateway;

use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Routing\Router;

class TwitterLoginControllerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var TwitterSignInController
     */
    private $controller;

    /**
     * @var TwitterApiGateway|\PHPUnit_Framework_MockObject_MockObject
     */
    private $twitterMock;

    /**
     * @var SessionInterface
     */
    private $session;

    /**
     * @var Router|\PHPUnit_Framework_MockObject_MockObject
     */
    private $routerMock;

    protected function setUp()
    {
        parent::setUp();

        $this->controller = new TwitterSignInController();

        $this->twitterMock = $this->getMockBuilder('Rabus\Bundle\Twitter\SignInBundle\Service\TwitterApiGateway')
            ->disableOriginalConstructor()
            ->getMock();
        $this->session = new Session(new MockArraySessionStorage());
        $this->routerMock = $this->getMockBuilder('\Symfony\Component\Routing\Router')
            ->disableOriginalConstructor()
            ->getMock();

        $container = new Container();
        $container->set('twitter-api-gateway', $this->twitterMock);
        $container->set('session', $this->session);
        $container->set('router', $this->routerMock);

        $this->controller->setContainer($container);
    }

    public function testRegularSignInFlowWithoutForwardUri()
    {
        $requestToken = array('oauth_token' => 'foo', 'oauth_token_secret' => 'bar');
        $redirectUrl = 'https://api.twitter.com/oauth/authenticate?oauth_token=foo';
        $callbackUrl = 'http://localhost/callback';
        $referer = '/foobar';

        $this->twitterMock->expects($this->once())
            ->method('getRequestToken')
            ->with($callbackUrl)
            ->will($this->returnValue($requestToken));
        $this->twitterMock->expects($this->once())
            ->method('generateAuthRedirectUrl')
            ->with($requestToken)
            ->will($this->returnValue($redirectUrl));
        $this->routerMock->expects($this->once())
            ->method('generate')
            ->with('rabus_twitter_signin_callback', array(), true)
            ->will($this->returnValue($callbackUrl));

        $request = Request::create('http://localhost/authenticate');
        $request->headers->add(array('Referer' => $referer));

        $response = $this->controller->authenticateAction($request);

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertTrue($response->isRedirect($redirectUrl));
        $this->assertEquals($requestToken, $this->session->get('rabus_twitter_request_token'));
        $this->assertEquals($referer, $this->session->get('rabus_twitter_forward_uri'));
    }

    public function testRegularSignInFlowWithForwardUri()
    {
        $requestToken = array('oauth_token' => 'foo', 'oauth_token_secret' => 'bar');
        $redirectUrl = 'https://api.twitter.com/oauth/authenticate?oauth_token=foo';
        $callbackUrl = 'http://localhost/callback';
        $referer = '/foobar';
        $forwardUri = '/forward_me';

        $this->twitterMock->expects($this->once())
            ->method('getRequestToken')
            ->with($callbackUrl)
            ->will($this->returnValue($requestToken));
        $this->twitterMock->expects($this->once())
            ->method('generateAuthRedirectUrl')
            ->with($requestToken)
            ->will($this->returnValue($redirectUrl));
        $this->routerMock->expects($this->once())
            ->method('generate')
            ->with('rabus_twitter_signin_callback', array(), true)
            ->will($this->returnValue($callbackUrl));

        $request = Request::create('http://localhost/authenticate', 'GET', array('forward_uri' => $forwardUri));
        $request->headers->add(array('Referer' => $referer));

        $response = $this->controller->authenticateAction($request);

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertTrue($response->isRedirect($redirectUrl));
        $this->assertEquals($requestToken, $this->session->get('rabus_twitter_request_token'));
        $this->assertEquals($forwardUri, $this->session->get('rabus_twitter_forward_uri'));
    }

    public function testRegularCallbackFlow()
    {
        $requestToken = array('oauth_token' => 'foo', 'oauth_token_secret' => 'bar');
        $this->session->set('rabus_twitter_request_token', $requestToken);
        $this->session->set('rabus_twitter_forward_uri', '/foobar');
        $this->twitterMock
            ->expects($this->once())
            ->method('getAccessToken')
            ->with($requestToken, 'barfoo')
            ->will($this->returnValue(array('bar' => 'foo')));

        $request = Request::create('http://localhost/callback', 'GET', array('oauth_token' => 'foo', 'oauth_verifier' => 'barfoo'));
        $response = $this->controller->callbackAction($request);

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertTrue($response->isRedirect('/foobar'));
        $this->assertEquals(array('bar' => 'foo'), $this->session->get('rabus_twitter_access_token'));
        $this->assertNull($this->session->get('rabus_twitter_request_token'));
        $this->assertNull($this->session->get('rabus_twitter_forward_uri'));
    }
}
