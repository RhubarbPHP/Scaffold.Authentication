<?php

namespace Rhubarb\Scaffolds\Authentication\Leaves;

use Rhubarb\Crown\Events\Event;
use Rhubarb\Leaf\Leaves\LeafModel;

class ConfirmResetPasswordModel extends LeafModel
{
    /**
     * @var string
     */
    public $message;

    /**
     * @var Event
     */
    public $confirmPasswordResetEvent;

    /**
     * @var string
     */
    public $newPassword;

    /**
     * @var string
     */
    public $confirmNewPassword;

    public function __construct()
    {
        $this->confirmPasswordResetEvent = new Event();
    }
}