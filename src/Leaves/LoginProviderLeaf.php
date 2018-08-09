<?php

namespace Rhubarb\Scaffolds\Authentication\Leaves;

use Rhubarb\Leaf\Leaves\Leaf;
use Rhubarb\Scaffolds\Authentication\LoginProviders\LoginProvider;

abstract class LoginProviderLeaf extends Leaf
{
    /**
     * @var LoginProvider
     */
    private $loginProvider;

    public function __construct(LoginProvider $loginProvider, $initialiseModelBeforeView = null)
    {
        $this->loginProvider = $loginProvider;
        if (!is_callable($initialiseModelBeforeView)) {
            $initialiseModelBeforeView = null;
        }
        
        parent::__construct(null, $initialiseModelBeforeView);
    }

    /**
     * Returns the login provider for this presenter.
     *
     * @return LoginProvider
     */
    protected function getLoginProvider()
    {
        return $this->loginProvider;
    }
}