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
use Rhubarb\Leaf\UrlHandlers\LeafCollectionUrlHandler;
use Rhubarb\Stem\Schema\SolutionSchema;
use Rhubarb\Stem\StemModule;

class AuthenticationModule extends Module
{
    protected $urlToProtect;
    protected $loginUrl;

    /**
     * Creates an instance of the Authentication module.
     *
     * @param null $loginProviderClassName
     * @param string $urlToProtect Optional. The URL stub to protect by requiring a login. Defaults to
     *                                  the entire URL tree.
     * @param string $loginUrl The URL to redirect the user to for logging in
     * @param string $identityColumnName The name of the column in the user table storing the login identity.
     */
    public function __construct($loginProviderClassName = null, $urlToProtect = "/", $loginUrl = "/login/")
    {
        parent::__construct();

        $this->urlToProtect = $urlToProtect;
        $this->loginUrl = $loginUrl;

        if ($loginProviderClassName != null) {
            LoginProvider::setProviderClassName($loginProviderClassName);
        }
    }

    public function initialise()
    {
        SolutionSchema::registerSchema("Authentication", __NAMESPACE__ . '\DatabaseSchema');
    }

    protected function registerUrlHandlers()
    {
        $reset = new LeafCollectionUrlHandler(
            __NAMESPACE__ . '\Leaves\ResetPassword',
            __NAMESPACE__ . '\Leaves\ConfirmResetPassword');

        $login = new ClassMappedUrlHandler(__NAMESPACE__ . '\Leaves\Login', [
            "reset/" => $reset
        ]);

        $login->setName("login");

        $validateLoginUrlHandler = new ValidateLoginUrlHandler(LoginProvider::getProvider(), $this->loginUrl);

        $this->addUrlHandlers(
            [
                "/login/" => $login,
                $this->urlToProtect => $validateLoginUrlHandler
            ]);

        $logout = new ClassMappedUrlHandler(__NAMESPACE__ . '\Leaves\Logout');

        $logout->setName("logout");

        $this->addUrlHandlers(
            [
                "/logout/" => $logout
            ]);

        // Make sure that the login url handlers are given greater precedence than those of the application.
        $login->setPriority(10);
        //$reset->setPriority(10);
        $validateLoginUrlHandler->setPriority(10);
    }

    /**
     * Should your module require other modules, they should register the module here.
     */
    protected function getModules()
    {
        return [new StemModule()];
    }
}
