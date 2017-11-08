<?php

namespace Rhubarb\Scaffolds\Authentication\Tests;

use Rhubarb\Crown\Encryption\HashProvider;
use Rhubarb\Crown\Encryption\Sha512HashProvider;
use Rhubarb\Crown\LoginProviders\LoginProvider;
use Rhubarb\Crown\Tests\Fixtures\TestCases\RhubarbTestCase;
use Rhubarb\Scaffolds\Authentication\DatabaseSchema;
use Rhubarb\Scaffolds\Authentication\LoginProviders\LoggedLoginProvider;
use Rhubarb\Scaffolds\Authentication\User;
use Rhubarb\Scaffolds\Authentication\UserLoginAttempt;
use Rhubarb\Stem\Schema\SolutionSchema;

class LoggedLoginProviderTest extends RhubarbTestCase
{
    public function testFailedLoginAttemptBeingRecorded()
    {
        $this->assertEquals(0, UserLoginAttempt::find()->count());

        LoginProvider::setProviderClassName(LoggedLoginProvider::class);
        SolutionSchema::registerSchema('Authentication', DatabaseSchema::class);

        try {
            $loggedLoginProvider = LoginProvider::getProvider();
            $loggedLoginProvider->login('test', 'test');
        } catch (\Exception $exception) {
        }

        $this->assertEquals(1, UserLoginAttempt::find()->count());
        $userLoginAttempt = UserLoginAttempt::find()[0];

        $this->assertFalse($userLoginAttempt->Successful);
        $this->assertNotEmpty($userLoginAttempt->ExceptionMessage);
    }

    public function testSuccessfulLoginAttemptBeingRecorded()
    {
        $this->assertEquals(0, UserLoginAttempt::find()->count());

        LoginProvider::setProviderClassName(LoggedLoginProvider::class);
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

        $this->assertEquals(1, UserLoginAttempt::find()->count());
        $userLoginAttempt = UserLoginAttempt::find()[0];

        $this->assertTrue($userLoginAttempt->Successful);
        $this->assertEmpty($userLoginAttempt->ExceptionMessage);
    }
}
