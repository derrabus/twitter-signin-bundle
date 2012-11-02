<?php

namespace Rabus\Bundle\Twitter\SignInBundle\Controller;

use Rabus\Bundle\Twitter\SignInBundle\Exception\CallbackException;
use Rabus\Bundle\Twitter\SignInBundle\Service\TwitterApiGateway;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\DependencyInjection\ContainerAware;

class TwitterSignInController extends ContainerAware
{
    const SESSION_REQUEST_TOKEN = 'rabus_twitter_request_token';
    const SESSION_ACCESS_TOKEN = 'rabus_twitter_access_token';
    const SESSION_REFERER = 'rabus_twitter_referer';

    /**
     * @param Request $request
     * @return Response
     */
    public function authenticateAction(Request $request)
    {
        $twitter = new TwitterApiGateway($this->getOAuthClient());
        $token = $this->fetchRequestToken($twitter);
        $this->container->get('session')
            ->set(self::SESSION_REQUEST_TOKEN, $token);
        $this->container->get('session')
            ->set(self::SESSION_REFERER, $request->headers->get('Referer', null, true));

        return new RedirectResponse(
            $twitter->generateAuthRedirectUrl($token)
        );
    }

    /**
     * @return \OAuth
     */
    private function getOAuthClient()
    {
        return $this->container->get('oauth');
    }

    /**
     * @param TwitterApiGateway $twitter
     * @return array
     */
    private function fetchRequestToken(TwitterApiGateway $twitter)
    {
        return $twitter->getRequestToken(
            $this->container->get('router')->generate('rabus_twitter_signin_callback', array(), true)
        );
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function callbackAction(Request $request)
    {
        $requestToken = $this->container->get('session')->get(self::SESSION_REQUEST_TOKEN);
        $this->container->get('session')->remove(self::SESSION_REQUEST_TOKEN);
        $oauth_token = $request->get('oauth_token');
        $oauth_verifier = $request->get('oauth_verifier');

        $this->validateCallbackTokens($requestToken, $oauth_token, $oauth_verifier);

        $twitter = new TwitterApiGateway($this->getOAuthClient());
        $accessToken = $twitter->getAccessToken($requestToken, $oauth_verifier);
        $this->container->get('session')->set(self::SESSION_ACCESS_TOKEN, $accessToken);

        $response = new RedirectResponse($this->container->get('session')->get(self::SESSION_REFERER));
        $this->container->get('session')->remove(self::SESSION_REFERER);

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
