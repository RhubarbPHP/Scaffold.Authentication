<?php

namespace Rhubarb\Scaffolds\Authentication;

use Rhubarb\Crown\Context;
use Rhubarb\Crown\Settings;
use Rhubarb\Crown\UnitTesting\CoreTestCase;

class LoginProviderTest extends CoreTestCase
{
	public function testAutoLogin()
	{
		$user = new User();
		$user->SetNewPassword( "abc123" );
		$user->Username = "test";
		$user->Forename = "test";
		$user->Enabled = 1;
		$user->Save();

		$token = $user->CreateToken();

		Settings::DeleteSettingNamespace( "LoginProvider" );

		$request = Context::CurrentRequest();
		$request->Cookie( 'lun', "test" );
		$request->Cookie( 'ltk', "anyoldvalue" );

		$loginProvider = new LoginProvider();

		$this->assertFalse( $loginProvider->isLoggedIn() );

		Settings::DeleteSettingNamespace( "LoginProvider" );

		$request->Cookie( 'ltk', $token );

		$loginProvider = new LoginProvider();

		$this->assertTrue( $loginProvider->isLoggedIn() );
	}

	public function testGetLoggedInUser()
	{
		$user = new User();
		$user->SetNewPassword( "abc123" );
		$user->Username = "test";
		$user->Forename = "test";
		$user->Enabled = 1;
		$user->Save();

		$loginProvider = new LoginProvider();
		$loginProvider->Login( "test", "abc123" );

		$pUser = LoginProvider::GetLoggedInUser();

		$this->assertEquals( $user->UniqueIdentifier, $pUser->UniqueIdentifier );
	}
}
