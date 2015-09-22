<?php


namespace Rhubarb\Scaffolds\Authentication\Tests\Presenters;

use Gcd\Hub\Emails\StaffSessionExpiry;
use Gcd\Hub\Model\Staff\Staff;
use Rhubarb\Crown\Tests\Fixtures\UnitTestingEmailProvider;
use Rhubarb\Leaf\Tests\Fixtures\Presenters\UnitTestView;
use Rhubarb\Scaffolds\Authentication\Presenters\ResetPasswordPresenter;
use Rhubarb\Stem\Schema\SolutionSchema;
use Rhubarb\Stem\Tests\Fixtures\ModelUnitTestCase;

class ResetPasswordPresenterTest extends ModelUnitTestCase
{
    public function testResetPasswordButton()
    {
        /* @var $user \Rhubarb\Scaffolds\Authentication\User */
        $user = SolutionSchema::GetModel("User");
        $user->Username = "timothy";
        $user->Forename = "test";
        $user->Surname = "guy";
        $user->Email = "test@nowhere.com";
        $user->Save();

        $staff = new Staff();
        $staff->Forename = "test";
        $staff->Surname = "guy";
        $staff->EmailAddress = "test@nowhere.com";
        $staff->save();

        $user->StaffID = $staff->UniqueIdentifier;
        $user->save();

        $presenter = new ResetPasswordPresenter();
        $view = new UnitTestView();

        $presenter->AttachMockView($view);
        $presenter->model->Username = "timothy";

        $view->SimulateEvent("ResetPassword");

        $user->Reload();
        $this->assertNotEmpty($user->PasswordResetHash);
        $this->assertEquals(date("Y-m-d"), $user->PasswordResetDate->format("Y-m-d"));

        // Check that an email is delivered to the user.
        $email = UnitTestingEmailProvider::GetLastEmail();

        $this->assertEquals("Your password reset invitation.", $email->GetSubject());

        $this->assertEquals("test guy", $email->GetRecipients()["test@nowhere.com"]->name);

    }

    public function testBadUsernameIsHandled()
    {
        $presenter = new ResetPasswordPresenter();
        $view = new UnitTestView();

        $presenter->AttachMockView($view);
        $presenter->model->Username = "timothy";

        $view->SimulateEvent("ResetPassword");

        $presenter->test();

        $this->assertTrue( $view->usernameNotFound );
    }
}