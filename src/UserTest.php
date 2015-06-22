<?php

namespace Rhubarb\Scaffolds\Authentication;

use Rhubarb\Crown\Encryption\HashProvider;
use Rhubarb\Crown\UnitTesting\CoreTestCase;
use Rhubarb\Scaffolds\AuthenticationWithRoles\User;

class UserTest extends CoreTestCase
{
    public function testPasswordResetHash()
    {
        $user = new User();
        $user->Forename = "test";
        $user->Username = "test";
        $user->Save();

        $hash = $user->GeneratePasswordResetHash();

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
        $user->Save();

        $user = User::FromUsername("joebloggs");

        $this->assertEquals("joebloggs", $user->Username);
    }

    public function testSettingPassword()
    {
        $user = new User();
        $user->Forename = "test";
        $user->Username = "joebloggs";
        $user->SetNewPassword("abc123");

        $hashProvider = HashProvider::GetHashProvider();
        $hashProvider->CompareHash("abc123", $user->Password);
    }

    public function testCreateToken()
    {
        $user = new User();
        $user->Forename = "test";
        $user->Username = "joebloggs2";
        $user->SetNewPassword("abc123");
        $user->Save();

        $user->TakeChangeSnapshot();

        $token = $user->CreateToken();

        $this->assertNotEmpty($token, "No token returned");
        $this->assertGreaterThan(40, strlen($token), "The token isn't long enough to be valid.");
        $this->assertEquals($user->Token, $token, "The model wasn't updated with the token");

        $user->Reload();

        $this->assertEquals($user->Token, $token, "The model wasn't saved");
        $this->assertTrue(strtotime($user->TokenExpiry) > time(), "Token Expiry wasn't set.");

        $this->setExpectedException("Rhubarb\Scaffolds\Authentication\Exceptions\TokenException");

        $user = new User();
        $user->CreateToken();
    }

    public function testValidateToken()
    {
        $user = new User();
        $user->Forename = "test";
        $user->Username = "goatsboats";
        $user->SetNewPassword("abc123");
        $user->Save();

        $token = $user->CreateToken();

        $this->assertTrue($user->ValidateToken($token), "The token didn't validate");

        // Fiddle with the tokens to simulate an attack.

        $token = "asdfklajsdfkjqpiowerioqwerjoqwejr;oqr";
        $user->Token = $token;

        $this->assertFalse($user->ValidateToken($token), "Token vulnerable to attack by resetting to known value.");

        $user = new User();
        $user->Forename = "test2";
        $user->Username = "goatsboats2";
        $user->SetNewPassword("abc123");
        $user->Save();

        $token = $user->CreateToken();

        $user->TokenExpiry = date("Y-m-d H:i:s", time() - 100);

        $this->assertFalse($user->ValidateToken($token), "The token should be expired.");
    }


    public function testPasswordResetClearsResetHash()
    {
        $user = new User();
        $user->Forename = "test";
        $user->Username = "gcdtest";
        $user->GeneratePasswordResetHash();
        $user->Save();
        $this->assertNotEquals("", $user->PasswordResetHash);

        $user->SetNewPassword("abc123");
        $user->Save();
        $this->assertEquals("", $user->PasswordResetHash);
    }

}