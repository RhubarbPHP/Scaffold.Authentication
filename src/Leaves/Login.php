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

    /**
     * Login constructor.
     *
     * @param LoginProvider $loginProvider The login provider to use when attempting login
     * @param null $redirectionUrl Optionally the URL to redirect to if login succeeds.
     * @throws \Rhubarb\Leaf\Exceptions\InvalidLeafModelException
     */
    public function __construct(LoginProvider $loginProvider, $redirectionUrl = null)
    {
        parent::__construct($loginProvider);

        $this->model->identityColumnName = $this->getLoginProvider()->getSettings()->identityColumnName;

        if ($redirectionUrl !== null){
            $this->model->redirectUrl = $redirectionUrl;
        }
    }

    /**
     * Handles the behaviour if the login is successful.
     *
     * The default implementation is to redirect to
     * 1) Our model's `redirectUrl` property
     * 2) A URL (within the current site) specified on the URL base64 encoded
     *      (e.g /login/L2NvbmdyYXRzLWZvci1jaGVja2luZy8=)
     * 3) The URL provided by `getDefaultSuccessUrl()`
     *
     * The first of those to return a value is used for the redirection.
     *
     * @return bool|string
     * @throws ForceResponseException
     */
    protected function onSuccess()
    {
        $redirectionUrl = "";

        // First check if the model has a target for redirection in mind.
        if (isset($this->model->redirectUrl)) {
            $redirectionUrl = $this->model->redirectUrl;
        }

        if (!$redirectionUrl){
            // Finally fallback to the Leave's default success URL:
            $redirectionUrl = $this->getDefaultSuccessUrl();
        }

        if (!$redirectionUrl){
            $redirectionUrl = "/";
        } else {
            // Check any supplied redirection URL is on the same host
            if (!$this->isRedirectionUrlValid($redirectionUrl)){
                $redirectionUrl = "/";
            }
        }

        throw new ForceResponseException(new RedirectResponse($redirectionUrl));
    }

    /**
     * Checks to make sure the URL is a relative or absolute path on the
     * existing domain.
     *
     * Simply returns false if the url starts with http or https
     *
     * @param string $url
     * @return bool True if the url is valid, false if it is not
     */
    protected function isRedirectionUrlValid(string $url)
    {
        // No http or https
        if (stripos($url, "http")===0){
            return false;
        }

        // In fact no scheme:// at all thanks.
        if (stripos($url, "://")!==false){
            return false;
        }

        return true;
    }

    /**
     * Returns the URL the Login will redirect to if successful and no other advice
     * has been given about ongoing target.
     *
     * @return string
     */
    protected function getDefaultSuccessUrl()
    {
        return "/";
    }

    /**
     * @param WebRequest $request
     * @throws ForceResponseException
     */
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
        $request = Request::current();#

        // Deprecated - instead of using ?rd=[base64encodedUrl] use
        // /login/[base64encodedurl]
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
