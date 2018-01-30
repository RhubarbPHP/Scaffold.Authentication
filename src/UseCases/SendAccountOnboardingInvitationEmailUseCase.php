<?php

namespace Rhubarb\Scaffolds\Authentication\Leaves;

use Rhubarb\Crown\DependencyInjection\Container;
use Rhubarb\Crown\Sendables\Email\EmailProvider;
use Rhubarb\Scaffolds\Authentication\Emails\AccountOnboardingInvitationEmail;
use Rhubarb\Scaffolds\Authentication\User;

class SendAccountOnboardingInvitationEmailUseCase
{
    public static function execute(User $user)
    {
        $user->generatePasswordResetHash();
        $resetPasswordEmail = Container::instance(AccountOnboardingInvitationEmail::class, $user);
        EmailProvider::selectProviderAndSend($resetPasswordEmail);
    }
}