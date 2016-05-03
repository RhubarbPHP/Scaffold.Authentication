<?php


namespace Rhubarb\Scaffolds\Authentication\Tests\Leaves;

use Gcd\Hub\Emails\StaffSessionExpiry;
use Gcd\Hub\Model\Staff\Staff;
use Rhubarb\Crown\Tests\Fixtures\UnitTestingEmailProvider;
use Rhubarb\Leaf\Tests\Fixtures\Leaves\UnitTestView;
use Rhubarb\Scaffolds\Authentication\Leaves\ResetPassword;
use Rhubarb\Stem\Schema\SolutionSchema;
use Rhubarb\Stem\Tests\Fixtures\ModelUnitTestCase;

class ResetPasswordPresenterTest extends ModelUnitTestCase
{
    public function testResetPasswordButton()
    {
        /* @var $user \Rhubarb\Scaffolds\Authentication\User */
        $user = SolutionSchema::getModel("User");
        $user->Username = "timothy";
        $user->Forename = "test";
        $user->Surname = "guy";
        $user->Email = "test@nowhere.com";
        $user->save();

        $staff = new Staff();
        $staff->Forename = "test";
        $staff->Surname = "guy";
        $staff->EmailAddress = "test@nowhere.com";
        $staff->save();

        $user->StaffID = $staff->UniqueIdentifier;
        $user->save();

        $presenter = new ResetPassword();
        $view = new UnitTestView();

        $presenter->attachMockView($view);
        $presenter->model->Username = "timothy";

        $view->simulateEvent("ResetPassword");

        $user->reload();
        $this->assertNotEmpty($user->PasswordResetHash);
        $this->assertEquals(date("Y-m-d"), $user->PasswordResetDate->format("Y-m-d"));

        // Check that an email is delivered to the user.
        $email = UnitTestingEmailProvider::getLastEmail();

        $this->assertEquals("Your password reset invitation.", $email->getSubject());

        $this->assertEquals("test guy", $email->getRecipients()["test@nowhere.com"]->name);

    }

    public function testBadUsernameIsHandled()
    {
        $presenter = new ResetPassword();
        $view = new UnitTestView();

        $presenter->attachMockView($view);
        $presenter->model->Username = "timothy";

        $view->simulateEvent("ResetPassword");

        $presenter->test();

        $this->assertTrue($view->usernameNotFound);
    }
}
