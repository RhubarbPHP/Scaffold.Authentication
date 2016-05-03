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
use Rhubarb\Leaf\Controls\Common\Text\TextBox;
use Rhubarb\Leaf\Views\View;

class ResetPasswordView extends View
{
    /**
     * @var ResetPasswordModel
     */
    protected $model;

    public function createSubLeaves()
    {
        parent::createSubLeaves();

        $this->registerSubLeaf(
            new TextBox("username", 30),
            new Button("ResetPassword", "Continue", function () {
                $this->model->resetPasswordEvent->raise();
            })
        );
    }

    public function printViewContent()
    {
        if ($this->model->usernameNotFound){
            print "<p>Sorry the user `" . $this->model->username . "` couldn't be found.</p>
<p>Please check your typing and try again.</p>";
        }

        if ($this->model->sent){
            print "<p>Thank you, a password reset invitation has been sent by the email address associated
with the username `" . $this->model->username . "`</p>
<p>When the email arrives it should contain a link which will let you supply a new password.</p>
<p>You have 24 hours to complete the reset before the invitation will expire.";
        }

        $this->layoutItemsWithContainer("Resetting your password",
            "<div class='c-form__help'>
				<p>Initiating a password reset will send an email to the email address associated with the username
                containing a link to reset your password.</p>
				<p>Clicking on the link within 24 hours will allow you to enter a new password for your account.</p>
			</div>",
            [
                "username",
                "" => "ResetPassword"
            ]
        );
    }
}