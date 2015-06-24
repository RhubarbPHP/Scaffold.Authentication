<?php

namespace Rhubarb\Scaffolds\Authentication\Tests\Presenters;

use Rhubarb\Crown\Tests\RhubarbTestCase;
use Rhubarb\Leaf\Tests\Fixtures\Presenters\UnitTestView;
use Rhubarb\Scaffolds\Authentication\Presenters\ConfirmResetPasswordPresenter;
use Rhubarb\Scaffolds\Authentication\User;

class ConfirmResetPasswordPresenterTest extends RhubarbTestCase
{
    public function testResetHappens()
    {
        $user = new User();
        $user->Username = "abc123";
        $user->Forename = "Billy";
        $user->SetNewPassword("abc123");
        $user->Save();

        $oldPassword = $user->Password;

        $hash = $user->GeneratePasswordResetHash();

        $mvp = new ConfirmResetPasswordPresenter();
        $view = new UnitTestView();
        $mvp->AttachMockView($view);

        $mvp->ItemIdentifier = $hash;
        $mvp->NewPassword = "def324";

        $view->SimulateEvent("ConfirmPasswordReset");

        $user->Reload();

        $this->assertNotEquals($oldPassword, $user->Password, "The password should have changed.");
    }
}
