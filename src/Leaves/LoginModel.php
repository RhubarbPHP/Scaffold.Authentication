<?php

namespace Rhubarb\Scaffolds\Authentication\Leaves;

use Rhubarb\Crown\Events\Event;
use Rhubarb\Leaf\Leaves\LeafModel;

class LoginModel extends LeafModel
{
    /**
     * @var string
     */
    public $redirectUrl;

    public $username;

    public $password;

    public $rememberMe;

    public $identityColumnName;

    public $failed = false;

    public $disabled = false;

    /**
     * Raised when the user attempts the login.
     *
     * @var Event
     */
    public $attemptLoginEvent;

    public function __construct()
    {
        parent::__construct();
        
        $this->attemptLoginEvent = new Event();
    }


    protected function getExposableModelProperties()
    {
        $list = parent::getExposableModelProperties();
        $list[] = "RedirectUrl";

        return $list;
    }
}