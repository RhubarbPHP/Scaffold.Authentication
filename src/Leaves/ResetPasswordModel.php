<?php

namespace Rhubarb\Scaffolds\Authentication\Leaves;

use Rhubarb\Crown\Events\Event;
use Rhubarb\Leaf\Leaves\LeafModel;

class ResetPasswordModel extends LeafModel
{
    public $username;

    public $resetPasswordEvent;

    public $usernameNotFound = false;

    public $sent = false;

    public function __construct()
    {
        $this->resetPasswordEvent = new Event();
    }
}