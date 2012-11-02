<?php

namespace Rabus\Bundle\Twitter\SignInBundle\Service;

class TwitterApiGateway
{
    const TWITTER_URL_OAUTH_AUTHENTICATE = 'https://api.twitter.com/oauth/authenticate';
    const TWITTER_URL_OAUTH_REQUEST_TOKEN = 'https://api.twitter.com/oauth/request_token';
    const TWITTER_URL_OAUTH_ACCESS_TOKEN = 'https://api.twitter.com/oauth/access_token';

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
                self::TWITTER_URL_OAUTH_REQUEST_TOKEN,
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
}
