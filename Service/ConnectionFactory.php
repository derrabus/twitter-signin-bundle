<?php

namespace Rabus\Bundle\Twitter\SignInBundle\Service;

use Guzzle\Http\Client;
use Guzzle\Log\PsrLogAdapter;
use Guzzle\Plugin\Log\LogPlugin;
use Guzzle\Plugin\Oauth\OauthPlugin;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;

class ConnectionFactory implements LoggerAwareInterface
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
     * @var LoggerInterface
     */
    private $logger;

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
     * Sets a logger instance on the object
     *
     * @param LoggerInterface $logger
     * @return null
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
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

        if (null !== $this->logger) {
            $client->addSubscriber(
                new LogPlugin(
                    new PsrLogAdapter($this->logger)
                )
            );
        }

        return $client;
    }
} 
