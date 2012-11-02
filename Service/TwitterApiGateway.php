<?php

namespace Rabus\Bundle\Twitter\SignInBundle\Service;

class TwitterApiGateway
{
    const ENDPOINT_OAUTH_AUTHENTICATE = 'https://api.twitter.com/oauth/authenticate';
    const ENDPOINT_OAUTH_REQUEST_TOKEN = 'https://api.twitter.com/oauth/request_token';
    const ENDPOINT_OAUTH_ACCESS_TOKEN = 'https://api.twitter.com/oauth/access_token';

    /**
     * @var \OAuth
     */
    private $oauth;

    /**
     * @param \OAuth $oauth
     */
    public function __construct(\OAuth $oauth)
    {
        $this->oauth = $oauth;
    }

    /**
     * @param array $requestToken
     * @param string $oauthVerifier
     * @return array
     * @throws TwitterApiException
     */
    public function getAccessToken(array $requestToken, $oauthVerifier)
    {
        try {
            $this->oauth->setToken($requestToken['oauth_token'], $requestToken['oauth_token_secret']);

            return $this->oauth->getAccessToken(
                self::ENDPOINT_OAUTH_ACCESS_TOKEN,
                null,
                $oauthVerifier
            );
        } catch (\OAuthException $e) {
            throw new TwitterApiException('Fetching OAuth access token failed', 0, $e);
        }
    }

    /**
     * @param string $callbackUrl
     * @return array
     * @throws TwitterApiException
     */
    public function getRequestToken($callbackUrl = null)
    {
        try {
            $token = $this->oauth->getRequestToken(
                self::ENDPOINT_OAUTH_REQUEST_TOKEN,
                $callbackUrl
            );
        } catch (\OAuthException $e) {
            throw new TwitterApiException('Fetching OAuth request token failed.', 0, $e);
        }

        if (!$token) {
            throw new TwitterApiException('Fetching OAuth request token failed.');
        }

        return $token;
    }

    /**
     * @param array $requestToken
     * @return string
     */
    public function generateAuthRedirectUrl(array $requestToken)
    {
        return self::ENDPOINT_OAUTH_AUTHENTICATE . '?oauth_token=' . urlencode($requestToken['oauth_token']);
    }
}
