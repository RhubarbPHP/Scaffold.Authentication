<?php

namespace Rhubarb\Scaffolds\Authentication\Presenters;

use Rhubarb\Leaf\Presenters\Forms\Form;
use Rhubarb\Leaf\Presenters\MessagePresenterTrait;
use Rhubarb\Scaffolds\Authentication\Emails\ResetPasswordInvitationEmail;
use Rhubarb\Scaffolds\Authentication\User;
use Rhubarb\Stem\Exceptions\RecordNotFoundException;

/**
 * A presenter that allows a user to reset their password.
 */
class ResetPasswordPresenter extends Form
{
    use MessagePresenterTrait;

    protected $usernameColumnName = "";
    protected $resetPasswordInvitationEmailClassName;
    protected $usernameNotFound = false;

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
        $this->view->usernameNotFound = $this->usernameNotFound;
    }

    protected function initiateResetPassword()
    {
        try {
            $user = User::fromUsername($this->model->Username);
            $user->generatePasswordResetHash();

            $resetPasswordEmailClass = $this->resetPasswordInvitationEmailClassName;

            /**
             * @var ResetPasswordInvitationEmail $resetPasswordEmail
             */
            $resetPasswordEmail = new $resetPasswordEmailClass($user);
            $resetPasswordEmail->addRecipient($user->Email, $user->FullName);
            $resetPasswordEmail->send();
        } catch (RecordNotFoundException $er) {
            $this->usernameNotFound = true;
        }
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
