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

use Rhubarb\Crown\Logging\Log;
use Rhubarb\Leaf\Presenters\Forms\Form;
use Rhubarb\Leaf\Presenters\MessagePresenterTrait;
use Rhubarb\Scaffolds\Authentication\User;
use Rhubarb\Stem\Exceptions\RecordNotFoundException;
use Rhubarb\Stem\Filters\Equals;
use Unislim\WebApp\Model\Members\Member;
use Unislim\WebApp\Model\Notification\Notification;

class ConfirmResetPasswordPresenter extends Form
{
    use MessagePresenterTrait;

    protected function createView()
    {
        return new ConfirmResetPasswordView();
    }

    protected function confirmPasswordReset()
    {
        if($this->NewPassword == $this->ConfirmNewPassword) {
            try {
                $resetHash = $this->ItemIdentifier;

                $user = User::fromPasswordResetHash($resetHash);
                $user->setNewPassword($this->NewPassword);
                $user->save();

                Log::debug("Password reset for user `" . $user->Username . "`", "MVP");

                $this->activateMessage("PasswordReset");

                $this->additionalConfirmationInstructions($user);

            } catch (RecordNotFoundException $ex) {
                $this->activateMessage("UserNotRecognised");
            }
        } else {
            $this->activateMessage("PasswordsDontMatch");
        }
    }

    protected function configureView()
    {
        parent::configureView();

        $this->view->attachEventHandler("ConfirmPasswordReset", function () {
            $this->confirmPasswordReset();
        });
    }

    /**
     * @param User|null $user
     */
    protected function additionalConfirmationInstructions($user = null)
    {
    }
}