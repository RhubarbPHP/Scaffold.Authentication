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

use Rhubarb\Crown\LoginProviders\LoginProvider;
use Rhubarb\Crown\LoginProviders\UrlHandlers\ValidateLoginUrlHandler;
use Rhubarb\Crown\Module;
use Rhubarb\Crown\UrlHandlers\ClassMappedUrlHandler;
use Rhubarb\Leaf\UrlHandlers\MvpCollectionUrlHandler;
use Rhubarb\Stem\Schema\SolutionSchema;

class AuthenticationModule extends Module
{
    public function __construct($loginProviderClassName = null)
    {
        parent::__construct();

        if ($loginProviderClassName != null) {
            LoginProvider::setDefaultLoginProviderClassName($loginProviderClassName);
        }
    }

    public function initialise()
    {
        SolutionSchema::registerSchema("Authentication", __NAMESPACE__ . '\DatabaseSchema');
    }

    protected function registerUrlHandlers()
    {
        $reset = new MvpCollectionUrlHandler(__NAMESPACE__ . '\Presenters\ResetPasswordPresenter', __NAMESPACE__ . '\Presenters\ConfirmResetPasswordPresenter');

        $login = new ClassMappedUrlHandler(__NAMESPACE__ . '\Presenters\LoginPresenter', [
            "reset/" => $reset
        ]);

        $login->setName("login");

        $validateLoginUrlHandler = new ValidateLoginUrlHandler(LoginProvider::getDefaultLoginProvider(), "/login/");

        $this->addUrlHandlers(
            [
                "/login/" => $login,
                "/" => $validateLoginUrlHandler
            ]);

        // Make sure that the login url handlers are given greater precedence than those of the application.
        $login->setPriority(10);
        $reset->setPriority(10);
        $validateLoginUrlHandler->setPriority(10);
    }
}
