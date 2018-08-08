<?php

namespace Rhubarb\Scaffolds\Authentication\Tests\Leaves;

use Rhubarb\Crown\Encryption\HashProvider;
use Rhubarb\Crown\Encryption\Sha512HashProvider;
use Rhubarb\Crown\Tests\Fixtures\TestCases\RhubarbTestCase;
use Rhubarb\Scaffolds\Authentication\Leaves\ConfirmResetPassword;
use Rhubarb\Scaffolds\Authentication\Leaves\ConfirmResetPasswordModel;
use Rhubarb\Scaffolds\Authentication\LoginProviders\LoginProvider;
use Rhubarb\Scaffolds\Authentication\User;

class ConfirmResetPasswordPresenterTest extends RhubarbTestCase
{
    public function testResetHappens()
    {
        HashProvider::setProviderClassName(Sha512HashProvider::class);

        $user = new User();
        $user->Username = "abc123";
        $user->Forename = "Billy";
        $user->setNewPassword("abc123");
        $user->save();

        $oldPassword = $user->Password;

        $hash = $user->generatePasswordResetHash();

        $mvp = new ConfirmResetPassword(new LoginProvider(),$hash);

        /**
         * @var ConfirmResetPasswordModel $model
         */
        $model = $mvp->getModelForTesting();
        $model->newPassword = "def324";
        $model->confirmNewPassword = "def324";

        $model->confirmPasswordResetEvent->raise();

        $user->reload();

        $this->assertNotEquals($oldPassword, $user->Password, "The password should have changed.");
    }
}
