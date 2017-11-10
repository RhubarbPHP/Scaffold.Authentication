<?php

namespace Rhubarb\Scaffolds\Authentication\Tests;

use Rhubarb\Crown\DateTime\RhubarbDateTime;
use Rhubarb\Crown\Encryption\HashProvider;
use Rhubarb\Crown\Encryption\Sha512HashProvider;
use Rhubarb\Crown\Tests\Fixtures\TestCases\RhubarbTestCase;
use Rhubarb\Scaffolds\Authentication\Exceptions\TokenException;
use Rhubarb\Scaffolds\Authentication\Settings\AuthenticationSettings;
use Rhubarb\Scaffolds\Authentication\User;
use Rhubarb\Stem\Exceptions\ModelConsistencyValidationException;

class UserTest extends RhubarbTestCase
{
    protected function _before()
    {
        parent::_before();

        HashProvider::setProviderClassName(Sha512HashProvider::class);
    }

    public function testPasswordResetHash()
    {
        $user = new User();
        $user->Forename = "test";
        $user->Username = "test";
        $user->save();

        $hash = $user->generatePasswordResetHash();

        $user = new User($user->UniqueIdentifier);

        $this->assertNotEmpty($hash);
        $this->assertNotEmpty($user->PasswordResetHash);
        $this->assertEquals($hash, $user->PasswordResetHash);

        $this->assertNotEmpty($user->PasswordResetDate);
        $this->assertEquals(date("Ymd"), date("Ymd", strtotime($user->PasswordResetDate)));
    }

    public function testFromUsername()
    {
        $user = new User();
        $user->Forename = "test";
        $user->Username = "joebloggs";
        $user->save();

        $user = User::fromUsername("joebloggs");

        $this->assertEquals("joebloggs", $user->Username);
    }

    public function testSettingPassword()
    {
        $user = new User();
        $user->Forename = "test";
        $user->Username = "joebloggs";

        $this->assertFalse($user->LastPasswordChangeDate->isValidDateTime());
        $user->setNewPassword("abc123");

        $hashProvider = HashProvider::getProvider();
        $hashProvider->compareHash("abc123", $user->Password);

        $this->assertTrue($user->LastPasswordChangeDate->isValidDateTime());
    }

    public function testCreateToken()
    {
        $user = new User();
        $user->Forename = "test";
        $user->Username = "joebloggs2";
        $user->setNewPassword("abc123");
        $user->save();

        $user->takeChangeSnapshot();

        $token = $user->createToken();

        $this->assertNotEmpty($token, "No token returned");
        $this->assertGreaterThan(40, strlen($token), "The token isn't long enough to be valid.");
        $this->assertEquals($user->Token, $token, "The model wasn't updated with the token");

        $user->reload();

        $this->assertEquals($user->Token, $token, "The model wasn't saved");
        $this->assertTrue(strtotime($user->TokenExpiry) > time(), "Token Expiry wasn't set.");

        $this->setExpectedException(TokenException::class);

        $user = new User();
        $user->createToken();
    }

    public function testValidateToken()
    {
        $user = new User();
        $user->Forename = "test";
        $user->Username = "goatsboats";
        $user->setNewPassword("abc123");
        $user->save();

        $token = $user->createToken();

        $this->assertTrue($user->validateToken($token), "The token didn't validate");

        // Fiddle with the tokens to simulate an attack.

        $token = "asdfklajsdfkjqpiowerioqwerjoqwejr;oqr";
        $user->Token = $token;

        $this->assertFalse($user->validateToken($token), "Token vulnerable to attack by resetting to known value.");

        $user = new User();
        $user->Forename = "test2";
        $user->Username = "goatsboats2";
        $user->setNewPassword("abc123");
        $user->save();

        $token = $user->createToken();

        $user->TokenExpiry = date("Y-m-d H:i:s", time() - 100);

        $this->assertFalse($user->validateToken($token), "The token should be expired.");
    }

    public function testPasswordResetClearsResetHash()
    {
        $user = new User();
        $user->Forename = "test";
        $user->Username = "gcdtest";
        $user->generatePasswordResetHash();
        $user->save();
        $this->assertNotEquals("", $user->PasswordResetHash);

        $user->setNewPassword("abc123");
        $user->save();
        $this->assertEquals("", $user->PasswordResetHash);
    }

    public function testHasModelExpired()
    {
        AuthenticationSettings::singleton()->passwordExpirationIntervalInDays = 3;

        $user = new User();
        $user->setNewPassword("abc123");
        $user->Username = "test";
        $user->Forename = "test";
        $user->Enabled = 1;
        $user->LastPasswordChangeDate = new RhubarbDateTime('-4 days');
        $user->save();

        $this->assertTrue($user->isModelExpired());

        $user->LastPasswordChangeDate = new RhubarbDateTime('-3 days');
        $user->save();

        $this->assertFalse($user->isModelExpired());

        $user->LastPasswordChangeDate = new RhubarbDateTime('-2.5 days');
        $user->save();

        $this->assertTrue($user->isModelExpired());

        $user->LastPasswordChangeDate = new RhubarbDateTime('-2 days');
        $user->save();

        $this->assertFalse($user->isModelExpired());

        $user->LastPasswordChangeDate = new RhubarbDateTime('-1 day');
        $user->save();

        $this->assertFalse($user->isModelExpired());
    }

    public function testPreviouslyUsedPassword()
    {
        AuthenticationSettings::singleton()->compareNewUserPasswordWithPreviousEntries = true;
        AuthenticationSettings::singleton()->totalPreviousPasswordsToStore = 10;

        AuthenticationSettings::singleton()->numberOfPastPasswordsToCompareTo = 10;

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
