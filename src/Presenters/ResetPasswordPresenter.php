<?php

namespace Rhubarb\Scaffolds\Authentication\Presenters;

use Rhubarb\Leaf\Presenters\Forms\Form;
use Rhubarb\Leaf\Presenters\MessagePresenterTrait;
use Rhubarb\Scaffolds\Authentication\Emails\ResetPasswordInvitationEmail;
use Rhubarb\Scaffolds\Authentication\User;

/**
 * A presenter that allows a user to reset their password.
 */
class ResetPasswordPresenter extends Form
{
    use MessagePresenterTrait;

    protected $usernameColumnName = "";
    protected $resetPasswordInvitationEmailClassName;

    public function __construct($usernameColumnName = "Username", $resetPasswordInvitationEmailClassName = '\Rhubarb\Scaffolds\Authentication\Emails\ResetPasswordInvitationEmail')
    {
        parent::__construct();

        $this->usernameColumnName = $usernameColumnName;
        $this->resetPasswordInvitationEmailClassName = $resetPasswordInvitationEmailClassName;
    }

    public function createView()
    {
        return new ResetPasswordView();
    }

    protected function applyModelToView()
    {
        parent::applyModelToView();

        $this->view->usernameColumnName = $this->usernameColumnName;
    }

    protected function initiateResetPassword()
    {
        $user = User::fromUsername($this->model->Username);
        $user->generatePasswordResetHash();

        $resetPasswordEmailClass = $this->resetPasswordInvitationEmailClassName;

        /**
         * @var ResetPasswordInvitationEmail $resetPasswordEmail
         */
        $resetPasswordEmail = new $resetPasswordEmailClass($user);
        $resetPasswordEmail->addRecipient($user->Email, $user->FullName);
        $resetPasswordEmail->send();
    }

    protected function configureView()
    {
        parent::configureView();

        $this->view->attachEventHandler("ResetPassword", function () {
            $this->initiateResetPassword();

            $this->activateMessage("Sent");
        });
    }
}