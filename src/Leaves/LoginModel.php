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

use Rhubarb\Crown\Events\Event;
use Rhubarb\Leaf\Leaves\LeafModel;

class LoginModel extends LeafModel
{
    /**
     * @var string
     */
    public $redirectUrl;

    public $username;

    public $password;

    public $rememberMe;

    public $identityColumnName;

    public $failed = false;

    public $disabled = false;

    public $expired = false;

    /**
     * Raised when the user attempts the login.
     *
     * @var Event
     */
    public $attemptLoginEvent;

    public $passwordResetUrl = '/login/reset/';

    public function __construct()
    {
        parent::__construct();

        $this->attemptLoginEvent = new Event();
    }

    protected function getExposableModelProperties()
    {
        $list = parent::getExposableModelProperties();
        $list[] = 'redirectUrl';

        return $list;
    }
}
