<?php

namespace Rhubarb\Scaffolds\Authentication\Tests;

use Rhubarb\Crown\Settings;
use Rhubarb\Crown\Tests\RhubarbTestCase;
use Rhubarb\Scaffolds\Authentication\LoginProviders\LoginProvider;
use Rhubarb\Scaffolds\Authentication\User;

class LoginProviderTest extends RhubarbTestCase
{
    public function testAutoLogin()
    {
        $user = new User();
        $user->setNewPassword("abc123");
        $user->Username = "test";
        $user->Forename = "test";
        $user->Enabled = 1;
        $user->save();

        $token = $user->createToken();

        Settings::deleteSettingNamespace("LoginProvider");

        $request = Context::currentRequest();
        $request->cookie('lun', "test");
        $request->cookie('ltk', "anyoldvalue");

        $loginProvider = new LoginProvider();

        $this->assertFalse($loginProvider->isLoggedIn());

        Settings::deleteSettingNamespace("LoginProvider");

        $request->cookie('ltk', $token);

        $loginProvider = new LoginProvider();

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

        $loginProvider = new LoginProvider();
        $loginProvider->login("test", "abc123");

        $pUser = LoginProvider::getLoggedInUser();

        $this->assertEquals($user->UniqueIdentifier, $pUser->UniqueIdentifier);
    }
}
