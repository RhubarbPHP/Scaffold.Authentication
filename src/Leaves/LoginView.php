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
use Rhubarb\Leaf\Controls\Common\Checkbox\Checkbox;
use Rhubarb\Leaf\Controls\Common\Text\PasswordTextBox;
use Rhubarb\Leaf\Controls\Common\Text\TextBox;
use Rhubarb\Leaf\Views\View;

class LoginView extends View
{
    /**
     * @var LoginModel
     */
    protected $model;

    public function createSubLeaves()
    {
        parent::createSubLeaves();

        $this->registerSubLeaf(
            new TextBox("username"),
            new PasswordTextBox("password"),
            new Checkbox("rememberMe"),
            new Button("Login", "Login", function () {
                $this->model->attemptLoginEvent->raise();
            })
        );
    }

    public function printViewContent()
    {
        if ($this->model->failed) {
            print "<div class='c-alert c-alert--error'>Sorry, this username and password combination could not be found, please check and try again.</div>";
        }

        ?>
        <fieldset class="c-form c-form--inline">
            <div class="c-form__group">
                <label class="c-form__label"><?= ucwords($this->model->identityColumnName); ?></label>
                <?= $this->leaves["username"]; ?>
            </div>
            <div class="c-form__group">
                <label class="c-form__label">Password</label>
                <?= $this->leaves["password"]; ?>
            </div>

            <div class="c-form__actions">
            <div class="c-form__actions-remember">
                <label class="c-form__label c-form__label--checkbox"><?= $this->leaves["rememberMe"] . " Remember Me"; ?></label>
            </div>
            <div class="c-form__actions-forgot">
                <a href="/login/reset/">I've forgotten my password.</a>
            </div>
            </div>

            <div class="c-form__actions">
                <?= $this->leaves["Login"]; ?>
            </div>
        </fieldset>

        <?php
    }
}
