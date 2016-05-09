<?php

namespace Rhubarb\Scaffolds\Authentication\Tests\Leaves;

use Rhubarb\Crown\Tests\RhubarbTestCase;
use Rhubarb\Leaf\Tests\Fixtures\Leaves\UnitTestView;
use Rhubarb\Scaffolds\Authentication\Leaves\ConfirmResetPassword;
use Rhubarb\Scaffolds\Authentication\User;

class ConfirmResetPasswordPresenterTest extends RhubarbTestCase
{
    public function testResetHappens()
    {
        $user = new User();
        $user->Username = "abc123";
        $user->Forename = "Billy";
        $user->setNewPassword("abc123");
        $user->save();

        $oldPassword = $user->Password;

        $hash = $user->generatePasswordResetHash();

        $mvp = new ConfirmResetPassword();
        $view = new UnitTestView();
        $mvp->attachMockView($view);

        $mvp->itemIdentifier = $hash;
        $mvp->NewPassword = "def324";

        $view->simulateEvent("ConfirmPasswordReset");

        $user->reload();

        $this->assertNotEquals($oldPassword, $user->Password, "The password should have changed.");
    }
}
