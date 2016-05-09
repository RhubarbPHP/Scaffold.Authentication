<?php

namespace Rhubarb\Scaffolds\Authentication\Leaves;

use Rhubarb\Crown\DependencyInjection\Container;
use Rhubarb\Crown\Sendables\Email\EmailProvider;
use Rhubarb\Leaf\Leaves\Leaf;
use Rhubarb\Leaf\Leaves\LeafModel;
use Rhubarb\Scaffolds\Authentication\Emails\ResetPasswordInvitationEmail;
use Rhubarb\Scaffolds\Authentication\User;
use Rhubarb\Stem\Exceptions\RecordNotFoundException;

/**
 * A presenter that allows a user to reset their password.
 */
class ResetPassword extends Leaf
{
    protected $usernameNotFound = false;

    /**
     * @var ResetPasswordModel
     */
    protected $model;

    protected function initiateResetPassword()
    {
        try {
            $user = User::fromUsername($this->model->username);
            $user->generatePasswordResetHash();

            /**
             * @var ResetPasswordInvitationEmail $resetPasswordEmail
             */
            $resetPasswordEmail = Container::instance(ResetPasswordInvitationEmail::class, $user);
            EmailProvider::selectProviderAndSend($resetPasswordEmail);

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
