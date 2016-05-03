<?php

namespace Rhubarb\Scaffolds\Authentication\Leaves;

use Rhubarb\Leaf\Leaves\Forms\Form;
use Rhubarb\Leaf\Leaves\Leaf;
use Rhubarb\Leaf\Leaves\LeafModel;
use Rhubarb\Leaf\Leaves\MessagePresenterTrait;
use Rhubarb\Scaffolds\Authentication\Emails\ResetPasswordInvitationEmail;
use Rhubarb\Scaffolds\Authentication\User;
use Rhubarb\Stem\Exceptions\RecordNotFoundException;

/**
 * A presenter that allows a user to reset their password.
 */
class ResetPassword extends Leaf
{
    protected $resetPasswordInvitationEmailClassName;
    protected $usernameNotFound = false;

    /**
     * @var ResetPasswordModel
     */
    protected $model;

    public function __construct($resetPasswordInvitationEmailClassName = '\Rhubarb\Scaffolds\Authentication\Emails\ResetPasswordInvitationEmail')
    {
        parent::__construct();

        $this->resetPasswordInvitationEmailClassName = $resetPasswordInvitationEmailClassName;
    }

    protected function initiateResetPassword()
    {
        try {
            $user = User::fromUsername($this->model->username);
            $user->generatePasswordResetHash();

            $resetPasswordEmailClass = $this->resetPasswordInvitationEmailClassName;

            /**
             * @var ResetPasswordInvitationEmail $resetPasswordEmail
             */
            $resetPasswordEmail = new $resetPasswordEmailClass($user);
            $resetPasswordEmail->addRecipient($user->Email, $user->FullName);
            $resetPasswordEmail->send();

            $this->model->sent = true;

        } catch (RecordNotFoundException $er) {
            $this->model->usernameNotFound = true;
        }
    }

    /**
     * Returns the name of the standard view used for this leaf.
     *
     * @return string
     */
    protected function getViewClass()
    {
        return ResetPasswordView::class;
    }

    /**
     * Should return a class that derives from LeafModel
     *
     * @return LeafModel
     */
    protected function createModel()
    {
        $model = new ResetPasswordModel();
        $model->resetPasswordEvent->attachHandler(function(){
            $this->initiateResetPassword();
        });

        return $model;
    }
}
