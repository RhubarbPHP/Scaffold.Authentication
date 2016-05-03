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

use Rhubarb\Crown\Logging\Log;
use Rhubarb\Leaf\Leaves\Leaf;
use Rhubarb\Leaf\Leaves\LeafModel;
use Rhubarb\Scaffolds\Authentication\User;
use Rhubarb\Stem\Exceptions\RecordNotFoundException;

class ConfirmResetPassword extends Leaf
{
    /**
     * @var ConfirmResetPasswordModel
     */
    protected $model;
    
    protected $user;

    /**
     * @return bool
     * @throws \Exception
     * @throws \Rhubarb\Stem\Exceptions\ModelConsistencyValidationException
     */
    protected function confirmPasswordReset()
    {
        if ($this->model->newPassword == $this->model->confirmNewPassword && $this->model->newPassword != "") {
            try {
                $resetHash = $this->model->itemIdentifier;

                $this->user = User::fromPasswordResetHash($resetHash);
                $this->user->setNewPassword($this->model->newPassword);
                $this->user->save();

                Log::debug("Password reset for user `" . $this->user->Username . "`", "MVP");

                $this->model->message = "PasswordReset";
                return true;
            } catch (RecordNotFoundException $ex) {
                $this->model->message = "UserNotRecognised";
                return false;
            }
        } else if ($this->model->newPassword == "") {
            $this->model->message = "PasswordEmpty";
            return false;
        } else {
            $this->model->message = "PasswordsDontMatch";
            return false;
        }
    }

    /**
     * Returns the name of the standard view used for this leaf.
     *
     * @return string
     */
    protected function getViewClass()
    {
        return ConfirmResetPasswordView::class;
    }

    /**
     * Should return a class that derives from LeafModel
     *
     * @return LeafModel
     */
    protected function createModel()
    {
        $model = new ConfirmResetPasswordModel();
        $model->confirmPasswordResetEvent->attachHandler(function(){
            $this->confirmPasswordReset();
        });
    }
}
