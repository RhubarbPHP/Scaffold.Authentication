<?php

/*
 *	Copyright 2015 RhubarbPHP
 *
 *  Licensed under the Apache License, Version 2.0 (the "License");
 *  you may not use this file except in compliance with the License.
 *  You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 *  Unless required by applicable law or agreed to in writing, software
 *  distributed under the License is distributed on an "AS IS" BASIS,
 *  WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 *  See the License for the specific language governing permissions and
 *  limitations under the License.
 */

namespace Rhubarb\Scaffolds\Authentication\Leaves;

use Rhubarb\Leaf\Controls\Common\Buttons\Button;
use Rhubarb\Leaf\Controls\Common\Text\PasswordTextBox;
use Rhubarb\Leaf\Views\View;

class ConfirmResetPasswordView extends View
{
    /**
     * @var ConfirmResetPasswordModel
     */
    protected $model;

    protected function createSubLeaves()
    {
        parent::createSubLeaves();

        $this->registerSubLeaf(
            new PasswordTextBox("newPassword"),
            new PasswordTextBox("confirmNewPassword"),
            new Button("ResetPassword", "Reset Password", function () {
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

        $this->layoutItemsWithContainer("Resetting your password",
            "<p class='c-form__help'>Complete your password reset by entering a new password.</p>",
            [
                "Enter new password" => "newPassword",
                "Enter again to confirm" => "confirmNewPassword",
                "" => "ResetPassword"
            ]
        );
    }

    /**
     * Should return an array of key value pairs storing message texts against an arbitrary tracking code.
     *
     * @return string[]
     */
    protected function getMessages()
    {
        $messages = [];
        $messages["PasswordReset"] = <<<PasswordReset
<p class="c-alert">Thanks, your password has now been reset. If you still have difficulties logging in you
should contact us for assistance. We will never ask you for your password, but we should
be able to reset it for you.</p>
PasswordReset;

        $messages["PasswordsDontMatch"] = <<<PasswordsDontMatch
<p class="c-alert c-alert--error">Sorry, the password entries you made do not match.
Please reset your password again.</p>
PasswordsDontMatch;

        $messages["PasswordEmpty"] = <<<PasswordEmpty
<p class="c-alert c-alert--error">Password and Confirm Password fields cannot be empty</p>
PasswordEmpty;


        $messages["UserNotRecognised"] = <<<PasswordsDontMatch
<p class="c-alert c-alert--error">Sorry, the user account you are attempting to reset has not been recognised.
Please click the 'forgot my password' link again.</p>
PasswordsDontMatch;

        $messages["HashInvalid"] = <<<PasswordsDontMatch
<p class="c-alert c-alert--error">Sorry, the reset link has expired or is not recognised.
Please click the 'forgot my password' link again.</p>
PasswordsDontMatch;

        return $messages;
    }
}
