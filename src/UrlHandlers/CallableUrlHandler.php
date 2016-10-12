<?php

namespace Rhubarb\Scaffolds\Authentication\UrlHandlers;

use Rhubarb\Crown\UrlHandlers\UrlHandler;

class CallableUrlHandler extends UrlHandler
{
    private $callable;

    /**
     * CallableUrlHandler constructor.
     * @param callable $callable
     * @param array $children
     */
    public function __construct($callable, $children = [])
    {
        parent::__construct($children);

        $this->callable = $callable;
    }

    public function generateResponseForRequest($request = null)
    {
        $method = $this->callable;
        $object = $method();

        return $object->generateResponse($request);
    }
}
