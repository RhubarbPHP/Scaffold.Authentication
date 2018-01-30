<?php

namespace Rhubarb\Scaffolds\Authentication\Leaves;

use Rhubarb\Leaf\Controls\Common\Buttons\Button;

class AccountOnboardingView extends ConfirmResetPasswordView
{
    protected function createSubLeaves()
    {
        parent::createSubLeaves();

        $this->registerSubLeaf(
            new Button("ActivateAccount", "Activate Account", function () {
                $this->model->confirmPasswordResetEvent->raise();
            })
        );
    }

    protected function printViewContent()
    {
        $messages = $this->getMessages();

        if (isset($messages[$this->model->message])) {
            $closure = $messages[$this->model->message];

            if (is_callable($closure)) {
                print $closure();
            } else {
                print $closure;
            }

            return;
        }

        $this->layoutItemsWithContainer($this->getTitle(),
            "<p class='c-form__help'>{$this->getTitleParagraph()}</p>",
            [
                "Create your password" => "newPassword",
                "Confirm your password" => "confirmNewPassword",
                "" => "ActivateAccount"
            ]
        );
    }

    protected function getTitle()
    {
        return "Create your account";
    }

    protected function getTitleParagraph()
    {
        return "Create your account by setting your password.";
    }

    protected function getMessages()
    {
        $messages = [];

        $messages['PasswordReset'] = <<<PasswordReset
<p class="c-alert">Thanks, your account has now been activated. If you still have difficulties logging in you
should contact us for assistance. We will never ask you for your password, but we should
be able to reset it for you.</p>
PasswordReset;

        $messages["PasswordsDontMatch"] = <<<PasswordsDontMatch
<p class="c-alert c-alert--error">Sorry, the password entries you made do not match.
Please enter your password again</p>
PasswordsDontMatch;

        $messages["PasswordEmpty"] = <<<PasswordEmpty
<p class="c-alert c-alert--error">Password and Confirm Password fields cannot be empty</p>
PasswordEmpty;

        $messages["UserNotRecognised"] = <<<PasswordsDontMatch
<p class="c-alert c-alert--error">Sorry, the user account you are attempting to reset has not been recognised.
Please ask for a new invitation</p>
PasswordsDontMatch;

        $messages['HashInvalid'] = <<<HashInvalid
<p class="c-alert c-alert--error">Sorry, your activation link has expired or is not recognised.
Please ask for a new invitation</p>
HashInvalid;

        return $messages;
    }
}