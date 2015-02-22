<?php


namespace Rhubarb\Scaffolds\Authentication\Presenters;

use Rhubarb\Crown\Integration\Email\UnitTestingEmailProvider;
use Rhubarb\Stem\Repositories\Repository;
use Rhubarb\Stem\Schema\SolutionSchema;
use Rhubarb\Leaf\Views\UnitTestView;
use Rhubarb\Crown\UnitTesting\CoreTestCase;

class ResetPasswordPresenterTest extends CoreTestCase
{
	public function testResetPasswordButton()
	{
		/* @var $user \Rhubarb\Scaffolds\Authentication\User */
		$user = SolutionSchema::GetModel( "User" );
		$user->Username = "timothy";
		$user->Forename = "test";
		$user->Surname = "guy";
		$user->Email = "test@nowhere.com";
		$user->Save();

		$presenter = new ResetPasswordPresenter();
		$view = new UnitTestView();

		$presenter->AttachMockView( $view );
		$presenter->model->Username = "timothy";

		$view->SimulateEvent( "ResetPassword" );

		$user->Reload();
		$this->assertNotEmpty( $user->PasswordResetHash );
		$this->assertEquals( date( "Y-m-d" ), $user->PasswordResetDate->format( "Y-m-d" ) );

		// Check that an email is delivered to the user.
		$email = UnitTestingEmailProvider::GetLastEmail();

		$this->assertEquals( "Your password reset invitation.", $email->GetSubject() );

		$this->assertEquals( "test guy", $email->GetRecipients()["test@nowhere.com" ]->name );

	}
}
