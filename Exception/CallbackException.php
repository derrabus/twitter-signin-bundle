<?php

namespace Rabus\Bundle\Twitter\SignInBundle\Exception;

use Symfony\Component\HttpKernel\Exception\HttpException;

class CallbackException extends HttpException
{
    /**
     * @param string $message
     * @param int $code
     * @param \Exception $previous
     */
    public function __construct($message, $code = 0, \Exception $previous = null) {
        parent::__construct(400, $message, $previous, array(), $code);
    }
}
