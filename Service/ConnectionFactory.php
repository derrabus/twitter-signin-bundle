<?php

namespace Rabus\Bundle\Twitter\SignInBundle\Service;

use Guzzle\Http\Client;
use Guzzle\Plugin\Oauth\OauthPlugin;

class ConnectionFactory
{
    /**
     * @var string
     */
    private $key;

    /**
     * @var string
     */
    private $secret;

    /**
     * @param string $key
     * @param string $secret
     */
    public function __construct($key, $secret)
    {
        $this->key = $key;
        $this->secret = $secret;
    }

    /**
     * @param string $token
     * @param string $secret
     * @return Client
     */
    public function getOAuthConnection($token = null, $secret = null)
    {
        $client = new Client('https://api.twitter.com');

        $params = array(
            'consumer_key'    => $this->key,
            'consumer_secret' => $this->secret
        );

        if ($token !== null && $secret !== null) {
            $params['token'] = $token;
            $params['token_secret'] = $secret;
        }

        $oauth = new OauthPlugin($params);
        $client->addSubscriber($oauth);

        return $client;
    }
} 
