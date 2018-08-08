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
use Rhubarb\Crown\LoginProviders\Exceptions\LoginFailedException;
use Rhubarb\Crown\Request\Request;
use Rhubarb\Crown\Request\WebRequest;
use Rhubarb\Crown\Response\RedirectResponse;
use Rhubarb\Crown\UrlHandlers\UrlHandler;
use Rhubarb\Scaffolds\Authentication\Exceptions\LoginDisabledException;
use Rhubarb\Scaffolds\Authentication\Exceptions\LoginTemporarilyLockedOutException;
use Rhubarb\Scaffolds\Authentication\Exceptions\LoginExpiredException;
use Rhubarb\Scaffolds\Authentication\LoginProviders\LoginProvider;

class Login extends LoginProviderLeaf
{
    protected $loginProviderClassName = "";

    /**
     * @var LoginModel
     */
    protected $model;

    public function __construct(LoginProvider $loginProvider)
    {
        parent::__construct($loginProvider);

        $this->model->identityColumnName = $this->getLoginProvider()->getSettings()->identityColumnName;
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
        $path = UrlHandler::getExecutingUrlHandler()->getHandledUrl();
        if (preg_match('|^' . preg_quote($path) . '([^/]+)|', WebRequest::current()->urlPath, $match)) {
            $url = base64_decode($match[1]);
            if ($url !== false) {
                return $url;
            }
        }
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
            } catch (LoginExpiredException $er) {
                $this->model->expired = true;
            } catch (LoginTemporarilyLockedOutException $er) {
                $this->model->failedLoginAttempts = true;
            } catch (LoginFailedException $er) {
                $this->model->failed = true;
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
