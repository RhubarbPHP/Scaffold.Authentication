<?php

namespace Rhubarb\Scaffolds\Authentication\Tests;

use Rhubarb\Crown\Application;
use Rhubarb\Crown\DateTime\RhubarbDateTime;
use Rhubarb\Crown\Encryption\HashProvider;
use Rhubarb\Crown\Encryption\Sha512HashProvider;
use Rhubarb\Crown\LoginProviders\Exceptions\LoginExpiredException;
use Rhubarb\Crown\LoginProviders\Exceptions\LoginFailedException;
use Rhubarb\Crown\Request\Request;
use Rhubarb\Crown\Request\WebRequest;
use Rhubarb\Crown\Tests\Fixtures\TestCases\RhubarbTestCase;
use Rhubarb\Scaffolds\Authentication\DatabaseSchema;
use Rhubarb\Scaffolds\Authentication\LoginProviders\LoginProvider;
use Rhubarb\Scaffolds\Authentication\Settings\AuthenticationSettings;
use Rhubarb\Scaffolds\Authentication\User;
use Rhubarb\Stem\Schema\SolutionSchema;

class LoginProviderTest extends RhubarbTestCase
{
    protected function _before()
    {
        parent::_before();

        HashProvider::setProviderClassName(Sha512HashProvider::class);
        \Rhubarb\Crown\LoginProviders\LoginProvider::setProviderClassName(LoginProvider::class);
        SolutionSchema::registerSchema( "AuthenticationSchema", DatabaseSchema::class);

        Application::current()->setCurrentRequest(new WebRequest());
    }

    public function testAutoLogin()
    {
        $user = new User();
        $user->setNewPassword("abc123");
        $user->Username = "test";
        $user->Forename = "test";
        $user->Enabled = 1;
        $user->save();

        $token = $user->createToken();

        /** @var \Rhubarb\Crown\Request\WebRequest $request */
        $request = Request::current();
        $request->cookieData['lun'] = "test";
        $request->cookieData['ltk'] = "anyoldvalue";

        $loginProvider = LoginProvider::singleton();

        $this->assertFalse($loginProvider->isLoggedIn());

        $request->cookieData['ltk'] = $token;

        Application::current()->container()->clearSingleton(LoginProvider::class);

        $loginProvider = LoginProvider::singleton();

        $this->assertTrue($loginProvider->isLoggedIn());
    }

    public function testGetLoggedInUser()
    {
        $user = new User();
        $user->setNewPassword("abc123");
        $user->Username = "test";
        $user->Forename = "test";
        $user->Enabled = 1;
        $user->save();

        $loginProvider = LoginProvider::singleton();
        $loginProvider->login("test", "abc123");

        $pUser = LoginProvider::getLoggedInUser();

        $this->assertEquals($user->UniqueIdentifier, $pUser->UniqueIdentifier);
    }

    public function testPasswordExpired()
    {
        AuthenticationSettings::singleton()->passwordExpirationIntervalInDays = 3;

        $user = new User();
        $user->setNewPassword("abc123");
        $user->Username = "test";
        $user->Forename = "test";
        $user->Enabled = 1;
        $user->LastPasswordChangeDate = new RhubarbDateTime('-4 days');
        $user->save();

        try {
            $loginProvider = LoginProvider::singleton();
            $loginProvider->login("test", "abc123");

            $this->fail("Expected Password to be seen as expired");
        } catch (LoginExpiredException $exception) {
        }

        $user->LastPasswordChangeDate = new RhubarbDateTime('-2 days');
        $user->save();

        try {
            $loginProvider = LoginProvider::singleton();
            $loginProvider->login("test", "abc123");

        } catch (LoginExpiredException $exception) {
            $this->fail("Login should not be detected as expired");
        }

        $user->LastPasswordChangeDate = new RhubarbDateTime('-1 day');
        $user->save();

        try {
            $loginProvider = LoginProvider::singleton();
            $loginProvider->login("test", "abc123");
        } catch (LoginExpiredException $exception) {
            $this->fail("Login should not be detected as expired");
        }
    }
}
