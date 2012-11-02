<?php

namespace Rabus\Bundle\Twitter\SignInBundle\Controller;

use Rabus\Bundle\Twitter\SignInBundle\Exception\CallbackException;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\DependencyInjection\ContainerAware;

class TwitterSignInController extends ContainerAware
{
    const SESSION_REQUEST_TOKEN = 'rabus_twitter_request_token';
    const SESSION_ACCESS_TOKEN = 'rabus_twitter_access_token';
    const SESSION_REFERER = 'rabus_twitter_referer';
    const TWITTER_URL_OAUTH_AUTHENTICATE = 'https://api.twitter.com/oauth/authenticate';
    const TWITTER_URL_OAUTH_REQUEST_TOKEN = 'https://api.twitter.com/oauth/request_token';
    const TWITTER_URL_OAUTH_ACCESS_TOKEN = 'https://api.twitter.com/oauth/access_token';

    /**
     * @param Request $request
     * @return Response
     */
    public function authenticateAction(Request $request)
    {
        $oauth = $this->getOAuthClient();
        $token = $this->fetchRequestToken($oauth);
        $this->container->get('session')
            ->set(self::SESSION_REQUEST_TOKEN, $token);
        $this->container->get('session')
            ->set(self::SESSION_REFERER, $request->headers->get('Referer', null, true));

        return new RedirectResponse(
            self::TWITTER_URL_OAUTH_AUTHENTICATE . '?oauth_token=' . urlencode($token['oauth_token'])
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
     * @param \OAuth $oauth
     * @return array
     * @throws \RuntimeException
     */
    private function fetchRequestToken(\OAuth $oauth)
    {
        $token = $oauth->getRequestToken(
            self::TWITTER_URL_OAUTH_REQUEST_TOKEN,
            $this->container->get('router')->generate('rabus_twitter_signin_callback', array(), true)
        );

        if (!$token) {
            throw new \RuntimeException('Fetching OAuth request token failed.');
        }

        return $token;
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

        $oauth = $this->getOAuthClient();
        $accessToken = $this->fetchAccessToken($oauth, $requestToken, $oauth_verifier);
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

    /**
     * @param \OAuth $oauth
     * @param array $requestToken
     * @param string $oauth_verifier
     * @return array
     * @throws \RuntimeException
     */
    private function fetchAccessToken(\OAuth $oauth, $requestToken, $oauth_verifier)
    {
        $oauth->setToken($requestToken['oauth_token'], $requestToken['oauth_token_secret']);
        $accessToken = $oauth->getAccessToken(self::TWITTER_URL_OAUTH_ACCESS_TOKEN, null, $oauth_verifier);

        if (!$accessToken) {
            throw new \RuntimeException('Fetching OAuth access token failed.');
        }

        return $accessToken;
    }
}
