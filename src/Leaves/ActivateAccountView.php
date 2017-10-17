<?php


namespace Rhubarb\Scaffolds\Authentication\Leaves;


class ActivateAccountView extends ConfirmResetPasswordView
{
    protected function createSubLeaves()
    {
        parent::createSubLeaves();

        $this->leaves['ResetPassword']->setName('ActivateAccount');
    }

    protected function getTitle()
    {
        return "Activate your account";
    }

    protected function getTitleParagraph()
    {
        return "Activate your account by setting your password.";
    }

    protected function getMessages()
    {
        parent::getMessages();

        $messages = [];
        $messages["PasswordReset"] = <<<PasswordReset
<p class="c-alert">Thanks, your account has now been activated. If you still have difficulties logging in you
should contact us for assistance. We will never ask you for your password, but we should
be able to reset it for you.</p>
PasswordReset;

        $messages["HashInvalid"] = <<<HashInvalid
<p class="c-alert c-alert--error">Sorry, your activation link has expired or is not recognised.
Please ask for a new invitation</p>
HashInvalid;

        return $messages;
    }
}