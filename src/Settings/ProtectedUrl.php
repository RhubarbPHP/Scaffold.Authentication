<?php

namespace Rhubarb\Scaffolds\Authentication\Settings;

use Rhubarb\Scaffolds\Authentication\Leaves\ConfirmResetPassword;
use Rhubarb\Scaffolds\Authentication\Leaves\Login;
use Rhubarb\Scaffolds\Authentication\Leaves\Logout;
use Rhubarb\Scaffolds\Authentication\Leaves\ResetPassword;

class ProtectedUrl
{
    public $urlToProtect;
    public $loginProviderClassName;
    public $loginUrl;

    public $loginLeafClassName = Login::class;

    public $logoutChildUrl = 'logout';
    public $logoutLeafClassName = Logout::class;

    public $resetChildUrl = 'reset/';
    public $resetPasswordLeafClassName = ResetPassword::class;
    public $confirmResetPasswordLeafClassName = ConfirmResetPassword::class;

    public function __construct($urlToProtect, $loginProviderClassName, $loginUrl)
    {
        $this->urlToProtect = $urlToProtect;
        $this->loginProviderClassName = $loginProviderClassName;
        $this->loginUrl = $loginUrl;
    }
}
