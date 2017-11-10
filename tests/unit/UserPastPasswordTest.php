<?php

namespace Rhubarb\Scaffolds\Authentication\Tests;

use Rhubarb\Crown\Encryption\HashProvider;
use Rhubarb\Crown\Encryption\Sha512HashProvider;
use Rhubarb\Crown\Tests\Fixtures\TestCases\RhubarbTestCase;
use Rhubarb\Scaffolds\Authentication\Settings\AuthenticationSettings;
use Rhubarb\Scaffolds\Authentication\User;
use Rhubarb\Scaffolds\Authentication\UserPastPassword;
use Rhubarb\Stem\Filters\Equals;

class UserPastPasswordTest extends RhubarbTestCase
{
    protected function _before()
    {
        parent::_before();

        HashProvider::setProviderClassName(Sha512HashProvider::class);
    }


    public function testRemovePastPasswords()
    {
        AuthenticationSettings::singleton()->compareNewUserPasswordWithPreviousEntries = true;
        AuthenticationSettings::singleton()->totalPreviousPasswordsToStore = 10;

        for ($i = 0; $i < 30; $i++) {
            $pastPassword = new UserPastPassword();
            $pastPassword->UserID = 23;
            $pastPassword->Password = 'testingthisshouldbeahash';
            $pastPassword->save();
        }

        $this->assertEquals(UserPastPassword::find(new Equals("UserID", 23))->count(), 30);
        UserPastPassword::removePreviousPasswords(23);
        $this->assertEquals(UserPastPassword::find(new Equals("UserID", 23))->count(), AuthenticationSettings::singleton()->totalPreviousPasswordsToStore - 1);
    }

    public function testMaxPastPasswordsToStore()
    {
        AuthenticationSettings::singleton()->compareNewUserPasswordWithPreviousEntries = true;
        AuthenticationSettings::singleton()->totalPreviousPasswordsToStore = 10;

        $user = new User();
        $user->Password = "abc123";
        $user->Username = "test";
        $user->Forename = "test";
        $user->Enabled = 1;
        $user->save();

        for ($i = 0; $i < 30; $i++) {
            $pastPassword = new UserPastPassword();
            $pastPassword->UserID = $user->UniqueIdentifier;
            $pastPassword->Password = 'testingthisshouldbeahash';
            $pastPassword->save();
        }

        $this->assertEquals(UserPastPassword::find(new Equals("UserID", $user->UniqueIdentifier))->count(), 30);
        $user->setNewPassword("newjazzypassword");
        $user->save();

        $this->assertEquals(UserPastPassword::find(new Equals("UserID", $user->UniqueIdentifier))->count(), AuthenticationSettings::singleton()->totalPreviousPasswordsToStore);
    }

    public function testDisableStorePastPasswords()
    {
        AuthenticationSettings::singleton()->compareNewUserPasswordWithPreviousEntries = false;

        $user = new User();
        $user->Password = "abc123";
        $user->Username = "test";
        $user->Forename = "test";
        $user->Enabled = 1;
        $user->save();

        $this->assertEquals(UserPastPassword::find(new Equals("UserID", $user->UniqueIdentifier))->count(), 0);

        $user->setNewPassword("brandnewjazzypassword");
        $this->assertEquals(UserPastPassword::find(new Equals("UserID", $user->UniqueIdentifier))->count(), 0);
    }
}
