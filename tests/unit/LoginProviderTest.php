<?php

namespace Rhubarb\Scaffolds\Authentication\Tests;

use Rhubarb\Crown\Application;
use Rhubarb\Crown\DateTime\RhubarbDateTime;
use Rhubarb\Crown\Encryption\HashProvider;
use Rhubarb\Crown\Encryption\Sha512HashProvider;
use Rhubarb\Crown\Request\Request;
use Rhubarb\Crown\Request\WebRequest;
use Rhubarb\Crown\Tests\Fixtures\TestCases\RhubarbTestCase;
use Rhubarb\Scaffolds\Authentication\DatabaseSchema;
use Rhubarb\Scaffolds\Authentication\Exceptions\LoginTemporarilyLockedOutException;
use Rhubarb\Scaffolds\Authentication\Exceptions\LoginExpiredException;
use Rhubarb\Scaffolds\Authentication\LoginProviders\LoginProvider;
use Rhubarb\Scaffolds\Authentication\Settings\AuthenticationSettings;
use Rhubarb\Scaffolds\Authentication\User;
use Rhubarb\Scaffolds\Authentication\UserLog;
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
        $loginProvider = LoginProvider::singleton();
        $loginProvider->getSettings()->passwordExpirationIntervalInDays = 3;

        $user = new User();
        $user->setNewPassword("abc123");
        $user->Username = "test";
        $user->Forename = "test";
        $user->Enabled = 1;
        $user->LastPasswordChangeDate = new RhubarbDateTime('-4 days');
        $user->save();

        try {
            $loginProvider->login("test", "abc123");

            $this->fail("Expected Password to be seen as expired");
        } catch (LoginExpiredException $exception) {
        }

        $user->LastPasswordChangeDate = new RhubarbDateTime('-2 days');
        $user->save();

        try {
            $loginProvider->login("test", "abc123");

        } catch (LoginExpiredException $exception) {
            $this->fail("Login should not be detected as expired");
        }

        $user->LastPasswordChangeDate = new RhubarbDateTime('-1 day');
        $user->save();

        try {
            $loginProvider->login("test", "abc123");
        } catch (LoginExpiredException $exception) {
            $this->fail("Login should not be detected as expired");
        }
    }

    public function testNumerousFailedLoginAttempts()
    {
        $loginProvider = LoginProvider::singleton();

        $user = new User();
        $user->setNewPassword("abc123");
        $user->Username = "test";
        $user->Forename = "test";
        $user->Enabled = 1;
        $user->LastPasswordChangeDate = new RhubarbDateTime('-4 days');
        $user->save();

        //  Adding multiple login attempts
        $loginProvider->getSettings()->lockoutAccountAfterFailedLoginAttempts = true;
        $loginProvider->getSettings()->numberOfFailedLoginAttemptsBeforeLockout = 10;

        for ($i = 0; $i < 30; $i++) {
            $pastPassword = new UserLog();
            $pastPassword->LogType = UserLog::USER_LOG_LOGIN_FAILED;
            $pastPassword->EnteredUsername = $user->Username;
            $pastPassword->save();
        }

        try {
            $loginProvider->login("test", "jibberish");
            $this->fail("Login should be detected as disabled");
        } catch (LoginTemporarilyLockedOutException $exception) {

        }
    }

    public function testFailedLoginAttemptBeingRecorded()
    {
        $this->assertEquals(0, UserLog::find()->count());

        LoginProvider::setProviderClassName(LoginProvider::class);
        SolutionSchema::registerSchema('Authentication', DatabaseSchema::class);

        try {
            $loggedLoginProvider = LoginProvider::getProvider();
            $loggedLoginProvider->login('test', 'test');
        } catch (\Exception $exception) {
        }

        $this->assertEquals(1, UserLog::find()->count());
        $userLoginAttempt = UserLog::find()[0];

        $this->assertEquals($userLoginAttempt->LogType, UserLog::USER_LOG_LOGIN_FAILED);
    }

    public function testSuccessfulLoginAttemptBeingRecorded()
    {
        $this->assertEquals(0, UserLog::find()->count());

        LoginProvider::setProviderClassName(LoginProvider::class);
        HashProvider::setProviderClassName(Sha512HashProvider::class);
        SolutionSchema::registerSchema('Authentication', DatabaseSchema::class);

        $user = new User();
        $user->setNewPassword("abc123");
        $user->Username = "test";
        $user->Forename = "test";
        $user->Enabled = 1;
        $user->save();

        try {
            $loggedLoginProvider = LoginProvider::getProvider();
            $loggedLoginProvider->login('test', 'abc123');
        } catch (\Exception $exception) {
        }

        $this->assertEquals(1, UserLog::find()->count());
        $userLoginAttempt = UserLog::find()[0];

        $this->assertTrue($userLoginAttempt->LogType == UserLog::USER_LOG_LOGIN_SUCCESSFUL);
        $this->assertEmpty($userLoginAttempt->Message);
    }


    public function testHasModelExpired()
    {
        $provider = LoginProvider::singleton();
        $provider->getSettings()->passwordExpirationIntervalInDays = 3;

        $user = new User();
        $user->setNewPassword("test");
        $user->Username = "test";
        $user->Forename = "test";
        $user->Enabled = 1;
        $user->LastPasswordChangeDate = new RhubarbDateTime('-4 days');
        $user->save();

        try {
            $provider->login("test", "test");
            $this->fail("The user should have been reported as expired");
        } catch(LoginExpiredException $er){
        }


        $user->LastPasswordChangeDate = new RhubarbDateTime('-3 days');
        $user->save();

        try {
            $provider->login("test", "test");
        } catch(LoginExpiredException $er){
            $this->fail("The user should not have been reported as expired");
        }

        $user->LastPasswordChangeDate = new RhubarbDateTime('-2 days');
        $user->save();

        try {
            $provider->login("test", "test");
        } catch(LoginExpiredException $er){
            $this->fail("The user should not have been reported as expired");
        }

        $user->LastPasswordChangeDate = new RhubarbDateTime('-1 day');
        $user->save();

        try {
            $provider->login("test", "test");
        } catch(LoginExpiredException $er){
            $this->fail("The user should not have been reported as expired");
        }
    }

    public function testPreviouslyUsedPassword()
    {
        AuthenticationSettings::singleton()->compareNewUserPasswordWithPreviousEntries = true;
        AuthenticationSettings::singleton()->totalPreviousPasswordsToStore = 10;

        AuthenticationSettings::singleton()->numberOfPreviousPasswordsToCompareTo = 10;

        $user = new User();
        $user->setNewPassword("abc123");
        $user->Username = "test";
        $user->Forename = "test";
        $user->Enabled = 1;
        $user->LastPasswordChangeDate = new RhubarbDateTime('-4 days');
        $user->save();

        try {
            $user->setNewPassword("abc123");
            $user->save();
        } catch (ModelConsistencyValidationException $exception) {
            $this->assertEquals("The password you have entered has already been used. Please enter a new password.", $exception->getErrors()["Password"]);
        }
    }
}
