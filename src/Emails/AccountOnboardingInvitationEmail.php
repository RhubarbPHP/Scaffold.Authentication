<?php

namespace Rhubarb\Scaffolds\Authentication\Emails;

use Rhubarb\Crown\Settings\WebsiteSettings;
use Rhubarb\Scaffolds\Authentication\Settings\ProtectedUrl;

class AccountOnboardingInvitationEmail extends ResetPasswordInvitationEmail
{
    public function getText()
    {
        $settings = WebsiteSettings::singleton();

        return <<<Text
You have recently been invited to {$settings->absoluteWebsiteUrl} 

Below you will find a link which will allow you to set your password.

Please note you must do this within 24 hours or you will need to request a new invitation.

{$settings->absoluteWebsiteUrl}/login/activate/{$this->user->PasswordResetHash}/
Text;
    }

    /**
     * @return string
     */
    public function getSubject()
    {
        return 'Create Your Account';
    }

    public function getHtmlHeading()
    {
        return "<h1>Create Your Account</h1>";
    }

    public function getHtmlBody()
    {
        $settings = WebsiteSettings::singleton();

        return <<<HtmlBody
<p>You have recently been invited to {$settings->absoluteWebsiteUrl}</p>

<p>Below you will find a link which will allow you to set your password.</p>

<p>Please note you must do this within 24 hours or you will need to request a new invitation.</p>

<p><a href="{$settings->absoluteWebsiteUrl}/login/activate/{$this->user->PasswordResetHash}/">Click to activate your account</a></p>
HtmlBody;
    }
}