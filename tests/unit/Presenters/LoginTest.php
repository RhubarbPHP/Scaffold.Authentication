<?php

namespace Rhubarb\Scaffolds\Authentication\Tests\Leaves;

use Rhubarb\Crown\Application;
use Rhubarb\Crown\Encryption\HashProvider;
use Rhubarb\Crown\Encryption\Sha512HashProvider;
use Rhubarb\Crown\Exceptions\ForceResponseException;
use Rhubarb\Crown\Request\WebRequest;
use Rhubarb\Crown\Tests\Fixtures\TestCases\RhubarbTestCase;
use Rhubarb\Scaffolds\Authentication\DatabaseSchema;
use Rhubarb\Scaffolds\Authentication\Leaves\Login;
use Rhubarb\Scaffolds\Authentication\Leaves\LoginModel;
use Rhubarb\Scaffolds\Authentication\LoginProviders\LoginProvider;
use Rhubarb\Scaffolds\Authentication\User;
use Rhubarb\Stem\Schema\SolutionSchema;

class LoginTest extends RhubarbTestCase
{
    protected function _before()
    {
        parent::_before();

        HashProvider::setProviderClassName(Sha512HashProvider::class);
        \Rhubarb\Crown\LoginProviders\LoginProvider::setProviderClassName(LoginProvider::class);
        SolutionSchema::registerSchema( "AuthenticationSchema", DatabaseSchema::class);

        Application::current()->setCurrentRequest(new WebRequest());
    }

    public function testLoginFailsToRedirectIfSuccessUrlIsOutsideExistingSite()
    {
        $user = new User();
        $user->setNewPassword("abc123");
        $user->Username = "test";
        $user->Forename = "test";
        $user->Enabled = 1;
        $user->save();

        $login = new Login(new LoginProvider());

        /**
         * @var LoginModel $model
         */
        $model = $login->getModelForTesting();
        $model->redirectUrl = "/";
        $model->username = "test";
        $model->password = "abc123";

        try {
            $model->attemptLoginEvent->raise();
            $this->fail("We should have been redirected to /");
        } catch( ForceResponseException $er ){
            $this->assertEquals($er->getResponse()->getUrl(), "/");
        }

        $model->redirectUrl = "http://hackit.com/";

        try {
            $model->attemptLoginEvent->raise();
        } catch( ForceResponseException $er ){
            $this->assertEquals($er->getResponse()->getUrl(), "/", "We should have been redirected to / silently not ".$er->getResponse()->getUrl());
        }

        $model->redirectUrl = "chrome://net-internals/";

        try {
            $model->attemptLoginEvent->raise();
        } catch( ForceResponseException $er ){
            $this->assertEquals($er->getResponse()->getUrl(), "/", "We should have been redirected to / silently not ".$er->getResponse()->getUrl());
        }
    }
}