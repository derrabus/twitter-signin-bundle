<?php

namespace Rabus\Bundle\Twitter\SignInBundle\Service;

use Guzzle\Http\Exception\RequestException;
use Guzzle\Http\Message\Response;

class TwitterApiGateway
{
    const ENDPOINT_OAUTH_AUTHENTICATE = 'https://api.twitter.com/oauth/authenticate';
    const ENDPOINT_OAUTH_REQUEST_TOKEN = 'https://api.twitter.com/oauth/request_token';
    const ENDPOINT_OAUTH_ACCESS_TOKEN = 'https://api.twitter.com/oauth/access_token';

    /**
     * @var ConnectionFactory
     */
    private $connectionFactory;

    /**
     * @param ConnectionFactory $connectionFactory
     */
    public function __construct(ConnectionFactory $connectionFactory)
    {
        $this->connectionFactory = $connectionFactory;
    }

    /**
     * @param array $requestToken
     * @param string $oauthVerifier
     * @return array
     * @throws TwitterApiException
     */
    public function getAccessToken(array $requestToken, $oauthVerifier)
    {
        $client = $this->connectionFactory
            ->getOAuthConnection($requestToken['oauth_token'], $requestToken['oauth_token_secret']);
        $request = $client->post('oauth/access_token');
        $request->addHeader('oauth_verifier', $oauthVerifier);

        try {
            $response = $request->send();
        } catch (RequestException $e) {
            throw new TwitterApiException('Fetching OAuth access token failed', 0, $e);
        }

        $token = $this->extractTokenFromResponse($response);
        if (!$token) {
            throw new TwitterApiException('Fetching OAuth access token failed.');
        }

        return $token;
    }

    /**
     * @param string $callbackUrl
     * @return array
     * @throws TwitterApiException
     */
    public function getRequestToken($callbackUrl = null)
    {
        $client = $this->connectionFactory->getOAuthConnection();
        $request = $client->post('oauth/request_token');
        if ($callbackUrl) {
            $request->addHeader('oauth_callback', $callbackUrl);
        }

        try {
            $response = $request->send();
        } catch (RequestException $e) {
            throw new TwitterApiException('Fetching OAuth request token failed.', 0, $e);
        }

        $token = $this->extractTokenFromResponse($response);
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

    /**
     * @param Response $response
     * @return array
     */
    private function extractTokenFromResponse(Response $response)
    {
        parse_str(trim($response->getBody(true)), $token);

        return $token;
    }
}
