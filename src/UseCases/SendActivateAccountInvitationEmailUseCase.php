<?php

namespace Rhubarb\Scaffolds\Authentication\Leaves;

use Rhubarb\Crown\DependencyInjection\Container;
use Rhubarb\Crown\Sendables\Email\EmailProvider;
use Rhubarb\Scaffolds\Authentication\Emails\ActivateAccountInvitationEmail;
use Rhubarb\Scaffolds\Authentication\User;

class SendActivateAccountInvitationEmailUseCase
{
    public static function execute(User $user)
    {
        $user->generatePasswordResetHash();
        $resetPasswordEmail = Container::instance(ActivateAccountInvitationEmail::class, $user);
        EmailProvider::selectProviderAndSend($resetPasswordEmail);
    }
}