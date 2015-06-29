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

namespace Rhubarb\Scaffolds\Authentication;

use Rhubarb\Crown\Context;
use Rhubarb\Crown\Http\HttpResponse;
use Rhubarb\Crown\LoginProviders\Exceptions\NotLoggedInException;
use Rhubarb\Stem\LoginProviders\ModelLoginProvider;

class LoginProvider extends ModelLoginProvider
{
    public function __construct($modelClassName = "User", $usernameColumnName = "Username", $passwordColumnName = "Password", $activeColumnName = "Enabled")
    {
        parent::__construct($modelClassName, $usernameColumnName, $passwordColumnName, $activeColumnName);
    }

    protected function initialiseDefaultValues()
    {
        parent::initialiseDefaultValues();

        // If we're not logged in, let's see if we can auto login using a saved token.
        if (!$this->isLoggedIn()) {
            $request = Context::currentRequest();

            if ($request->cookie('lun') != "") {
                $username = $request->cookie('lun');
                $user = User::fromUsername($username);

                $token = $request->cookie('ltk');

                if ($user->validateToken($token)) {
                    $this->LoggedIn = true;
                    $this->LoggedInUserIdentifier = $user->UniqueIdentifier;
                    $this->storeSession();
                }
            }
        }
    }

    public function rememberLogin()
    {
        $user = $this->getLoggedInUser();
        HttpResponse::setCookie('lun', $this->getUsername());
        HttpResponse::setCookie('ltk', $user->createToken());
    }

    protected function onLogOut()
    {
        parent::onLogOut();
        HttpResponse::unsetCookie('lun');
        HttpResponse::unsetCookie('ltk');
    }

    /**
     * Get's the User model currently logged in user
     *
     * @return User
     * @throws NotLoggedInException Thrown if the user isn't logged in.
     */
    public static function getLoggedInUser()
    {
        $provider = new static();
        return $provider->getModel();
    }
}