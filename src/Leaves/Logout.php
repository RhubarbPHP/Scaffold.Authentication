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

use Rhubarb\Crown\Request\WebRequest;
use Rhubarb\Leaf\Leaves\Leaf;
use Rhubarb\Leaf\Leaves\LeafModel;

class Logout extends Leaf
{
    private $loginProviderClassName = "";

    /**
     * @var LogoutModel
     */
    protected $model;

    /**
     * @param null $loginProviderClassName
     */
    public function __construct($loginProviderClassName = null)
    {
        parent::__construct();

        $this->loginProviderClassName = $loginProviderClassName;
    }

    /**
     * Returns the name of the standard view used for this leaf.
     *
     * @return string
     */
    protected function getViewClass()
    {
        return LogoutView::class;
    }

    /**
     * Should return a class that derives from LeafModel
     *
     * @return LeafModel
     */
    protected function createModel()
    {
        $model = new LeafModel();

        return $model;
    }

    protected function parseRequest(WebRequest $request)
    {
        $login = $this->getLoginProvider();
        $login->logOut();

        return parent::parseRequest($request);
    }

    /**
     * Returns the login provider for this presenter.
     *
     * @return \Rhubarb\Stem\LoginProviders\ModelLoginProvider
     */
    private function getLoginProvider()
    {
        $provider = $this->loginProviderClassName;

        return $provider::singleton();
    }
}
