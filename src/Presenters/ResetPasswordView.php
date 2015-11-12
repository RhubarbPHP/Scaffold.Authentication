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

namespace Rhubarb\Scaffolds\Authentication\Presenters;

use Gcd\Hub\Settings\Feedback;
use Rhubarb\Leaf\Presenters\Controls\Buttons\Button;
use Rhubarb\Leaf\Presenters\Controls\Text\TextBox\TextBox;
use Rhubarb\Leaf\Views\HtmlView;
use Rhubarb\Leaf\Views\MessageViewTrait;

class ResetPasswordView extends HtmlView
{
    use MessageViewTrait;

    public $usernameColumnName = "Username";
    public $usernameNotFound = false;

    public function createPresenters()
    {
        parent::createPresenters();

        $this->addPresenters(
            new TextBox($this->usernameColumnName, 30),
            new Button("ResetPassword", "Continue", function () {
                $this->raiseEvent("ResetPassword");
            })
        );
    }

    public function printViewContent()
    {
        $this->printFieldset("Resetting your password",
            "<div class='c-form__help'>
				<p>Initiating a password reset will send an email to the email address associated with the username
                containing a link to reset your password.</p>
				<p>Clicking on the link within 24 hours will allow you to enter a new password for your account.</p>
			</div>",
            [
                $this->usernameColumnName,
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
        return
            [
                "Sent" => function () {
                    return "<p>Thank you, a password reset invitation has been sent by the email address associated
with the username `" . $this->getData($this->usernameColumnName) . "`</p>
<p>When the email arrives it should contain a link which will let you supply a new password.</p>
<p>You have 24 hours to complete the reset before the invitation will expire.";
                }
            ];
    }
}