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

use Rhubarb\Crown\Exceptions\ForceResponseException;
use Rhubarb\Crown\LoginProviders\Exceptions\LoginDisabledException;
use Rhubarb\Crown\LoginProviders\Exceptions\LoginExpiredException;
use Rhubarb\Crown\LoginProviders\Exceptions\LoginFailedException;
use Rhubarb\Crown\LoginProviders\LoginProvider;
use Rhubarb\Crown\Request\Request;
use Rhubarb\Crown\Request\WebRequest;
use Rhubarb\Crown\Response\RedirectResponse;
use Rhubarb\Leaf\Leaves\Leaf;
use Rhubarb\Scaffolds\Authentication\Settings\AuthenticationSettings;

class Login extends Leaf
{
    protected $loginProviderClassName = "";

    /**
     * @var LoginModel
     */
    protected $model;

    /**
     * @param string $loginProviderClassName If not supplied, the default login provider will be used.
     */
    public function __construct($loginProviderClassName = null)
    {
        parent::__construct();

        $settings = AuthenticationSettings::singleton();

        $this->loginProviderClassName = $loginProviderClassName;
        $this->model->identityColumnName = $settings->identityColumnName;
    }

    /**
     * Returns the login provider for this presenter.
     *
     * @return \Rhubarb\Stem\LoginProviders\ModelLoginProvider
     */
    private function getLoginProvider()
    {
        $provider = $this->loginProviderClassName;

        if ($provider == "") {
            return LoginProvider::getProvider();
        }

        return $provider::singleton();
    }

    protected function onSuccess()
    {
        if (isset($this->model->redirectUrl)) {
            throw new ForceResponseException(new RedirectResponse($this->model->redirectUrl));
        }

        throw new ForceResponseException(new RedirectResponse($this->getDefaultSuccessUrl()));
    }

    protected function getDefaultSuccessUrl()
    {
        return "/";
    }

    protected function parseRequest(WebRequest $request)
    {
        $login = $this->getLoginProvider();

        $logout = $request->get("logout");
        if (isset($logout)) {
            $login->logOut();
        }

        if ($login->isLoggedIn()) {
            $this->onSuccess();
        }

        parent::parseRequest($request);
    }

    /**
     * Returns the name of the standard view used for this leaf.
     *
     * @return string
     */
    protected function getViewClass()
    {
        return LoginView::class;
    }

    /**
     * Should return a class that derives from LeafModel
     *
     * @return LoginModel
     */
    protected function createModel()
    {
        return new LoginModel();
    }

    protected function onModelCreated()
    {
        /** @var WebRequest $request */
        $request = Request::current();
        $redirectUrl = $request->get('rd');
        if ($redirectUrl) {
            $redirectUrl = urldecode($redirectUrl);
            $this->model->redirectUrl = $redirectUrl;
        }

        $this->model->attemptLoginEvent->attachHandler(function () {
            $login = $this->getLoginProvider();

            try {
                if ($login->login($this->model->username, $this->model->password)) {

                    if ($this->model->rememberMe) {
                        $login = $this->getLoginProvider();
                        $login->rememberLogin();
                    }

                    $this->onSuccess();
                }
            } catch (LoginDisabledException $er) {
                $this->model->disabled = true;
                $this->model->failed = true;
            } catch (LoginFailedException $er) {
                $this->model->failed = true;
            } catch (LoginExpiredException $er) {
                $this->model->expired = true;
            }
        });
    }

    /**
     * Allows setting the URL for the forgotten password link on the login form
     *
     * @param $url
     */
    public function setPasswordResetUrl($url)
    {
        $this->model->passwordResetUrl = $url;
    }
}
