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

    public function generateAuthRedirectUrl($requestToken)
    {
        return self::ENDPOINT_OAUTH_AUTHENTICATE . '?oauth_token=' . urlencode($requestToken['oauth_token']);
    }
}
