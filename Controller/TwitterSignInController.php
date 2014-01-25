<?php

namespace Rabus\Bundle\Twitter\SignInBundle\Controller;

use Rabus\Bundle\Twitter\SignInBundle\Exception\CallbackException;
use Rabus\Bundle\Twitter\SignInBundle\Service\TwitterApiGateway;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class TwitterSignInController
{
    const SESSION_REQUEST_TOKEN = 'rabus_twitter_request_token';
    const SESSION_ACCESS_TOKEN = 'rabus_twitter_access_token';
    const SESSION_FORWARD_URI = 'rabus_twitter_forward_uri';

    /**
     * @var TwitterApiGateway
     */
    private $twitter;

    /**
     * @var UrlGeneratorInterface
     */
    private $router;

    /**
     * @param TwitterApiGateway $twitter
     * @param UrlGeneratorInterface $router
     */
    public function __construct(TwitterApiGateway $twitter, UrlGeneratorInterface $router)
    {
        $this->router = $router;
        $this->twitter = $twitter;
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function authenticateAction(Request $request)
    {
        $token = $this->fetchRequestToken($this->twitter);
        $request->getSession()
            ->set(self::SESSION_REQUEST_TOKEN, $token);
        $request->getSession()->set(
            self::SESSION_FORWARD_URI,
            is_null($request->get('forward_uri'))
                ? $request->headers->get('Referer', null, true)
                : $request->get('forward_uri')
        );

        return new RedirectResponse(
            $this->twitter->generateAuthRedirectUrl($token)
        );
    }

    /**
     * @param TwitterApiGateway $twitter
     * @return array
     */
    private function fetchRequestToken(TwitterApiGateway $twitter)
    {
        return $twitter->getRequestToken(
            $this->router->generate('rabus_twitter_signin_callback', array(), true)
        );
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function callbackAction(Request $request)
    {
        $requestToken = $request->getSession()->get(self::SESSION_REQUEST_TOKEN);
        $request->getSession()->remove(self::SESSION_REQUEST_TOKEN);
        $oauth_token = $request->get('oauth_token');
        $oauth_verifier = $request->get('oauth_verifier');

        $this->validateCallbackTokens($requestToken, $oauth_token, $oauth_verifier);

        $accessToken = $this->twitter->getAccessToken($requestToken, $oauth_verifier);
        $request->getSession()->set(self::SESSION_ACCESS_TOKEN, $accessToken);

        $response = new RedirectResponse($request->getSession()->get(self::SESSION_FORWARD_URI));
        $request->getSession()->remove(self::SESSION_FORWARD_URI);

        return $response;
    }

    /**
     * @param array $requestToken
     * @param string $oauth_token
     * @param string $oauth_verifier
     * @throws CallbackException
     */
    private function validateCallbackTokens($requestToken, $oauth_token, $oauth_verifier)
    {
        if (!$requestToken) {
            throw new CallbackException('No request token found in session.');
        }
        if (is_null($oauth_token) || is_null($oauth_verifier)) {
            throw new CallbackException('Invalid callback parameters.');
        }
        if ($requestToken['oauth_token'] != $oauth_token) {
            throw new CallbackException('Request tokens do not match.');
        }
    }
}
