<?php

namespace Rabus\Bundle\Twitter\SignInBundle\Tests\Controller;

use Rabus\Bundle\Twitter\SignInBundle\Controller\TwitterSignInController;

use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Router;

class TwitterLoginControllerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var TwitterSignInController
     */
    private $controller;

    /**
     * @var \OAuth|\PHPUnit_Framework_MockObject_MockObject
     */
    private $oauthMock;

    /**
     * @var SessionInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $sessionMock;

    /**
     * @var Router|\PHPUnit_Framework_MockObject_MockObject
     */
    private $routerMock;

    protected function setUp()
    {
        parent::setUp();

        $this->controller = new TwitterSignInController();

        $this->oauthMock = $this->getMockBuilder('OAuth')
            ->disableOriginalConstructor()
            ->getMock();

        $this->sessionMock = $this->getMock('\Symfony\Component\HttpFoundation\Session\SessionInterface');

        $this->routerMock = $this->getMockBuilder('\Symfony\Component\Routing\Router')
            ->disableOriginalConstructor()
            ->getMock();

        $container = new Container();
        $container->set('oauth', $this->oauthMock);
        $container->set('session', $this->sessionMock);
        $container->set('router', $this->routerMock);

        $this->controller->setContainer($container);
    }

    public function testRegularLoginFlow()
    {
        $requestToken = array('oauth_token' => 'foo', 'oauth_token_secret' => 'bar');
        $this->oauthMock->expects($this->once())
            ->method('getRequestToken')
            ->with('https://api.twitter.com/oauth/request_token', 'http://localhost/callback')
            ->will($this->returnValue($requestToken));
        $this->sessionMock->expects($this->at(0))
            ->method('set')
            ->with('rabus_twitter_request_token', $requestToken);
        $this->sessionMock->expects($this->at(1))
            ->method('set')
            ->with('rabus_twitter_referer', '/foobar');
        $this->routerMock->expects($this->once())
            ->method('generate')
            ->with('rabus_twitter_signin_callback', array(), true)
            ->will($this->returnValue('http://localhost/callback'));

        $request = Request::create('http://localhost/login');
        $request->headers->add(array('Referer' => '/foobar'));

        $response = $this->controller->authenticateAction($request);

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertTrue(
            $response->isRedirect('https://api.twitter.com/oauth/authenticate?oauth_token=foo')
        );
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testInvalidOAuthTokenOnLoginAction()
    {
        $this->oauthMock->expects($this->any())
            ->method('getRequestToken')
            ->will($this->returnValue(null));

        $request = Request::create('http://localhost/login');
        $request->headers->add(array('Referer' => '/foobar'));

        $this->controller->authenticateAction($request);
    }

    public function testRegularCallbackFlow()
    {
        $requestToken = array('oauth_token' => 'foo', 'oauth_token_secret' => 'bar');
        $this->sessionMock->expects($this->at(0))
            ->method('get')
            ->with('rabus_twitter_request_token')
            ->will($this->returnValue($requestToken));
        $this->sessionMock->expects($this->at(1))
            ->method('remove')
            ->with('rabus_twitter_request_token');
        $this->sessionMock->expects($this->at(2))
            ->method('set')
            ->with('rabus_twitter_access_token', array('bar' => 'foo'));
        $this->sessionMock->expects($this->at(3))
            ->method('get')
            ->with('rabus_twitter_referer')
            ->will($this->returnValue('/foobar'));
        $this->sessionMock->expects($this->at(4))
            ->method('remove')
            ->with('rabus_twitter_referer');
        $this->oauthMock
            ->expects($this->once())
            ->method('setToken')
            ->with('foo', 'bar');
        $this->oauthMock
            ->expects($this->once())
            ->method('getAccessToken')
            ->with('https://api.twitter.com/oauth/access_token', null, 'barfoo')
            ->will($this->returnValue(array('bar' => 'foo')));

        $request = Request::create('http://localhost/callback', 'GET', array('oauth_token' => 'foo', 'oauth_verifier' => 'barfoo'));
        $response = $this->controller->callbackAction($request);

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertTrue($response->isRedirect('/foobar'));
    }
}
