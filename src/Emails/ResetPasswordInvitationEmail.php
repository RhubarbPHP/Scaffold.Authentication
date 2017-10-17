<?php

/*
 *	Copyright 2015 RhubarbPHP
 *
 *  Licensed under the Apache License, Version 2.0 (the "License");
 *  you may not use this file except in compliance with the License.
 *  You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 *  Unless required by applicable law or agreed to in writing, software
 *  distributed under the License is distributed on an "AS IS" BASIS,
 *  WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 *  See the License for the specific language governing permissions and
 *  limitations under the License.
 */

namespace Rhubarb\Scaffolds\Authentication\Emails;

use Rhubarb\Crown\DependencyInjection\Container;
use Rhubarb\Crown\Sendables\Email\Email;
use Rhubarb\Crown\Settings\WebsiteSettings;
use Rhubarb\Scaffolds\Authentication\User;

class ResetPasswordInvitationEmail extends Email
{
    protected $user;

    public function __construct(User $user)
    {
        $this->user = $user;
        $this->addRecipientByEmail($user->Email, $user->getFullName());
    }

    public function getText()
    {
        $settings = WebsiteSettings::singleton();

        return "You recently requested an invitation to reset your password. Below you will find a
link which when clicked will return you to the site and let you enter a new password.

Please note you must do this within 24 hours or you will need to request a new invitation.

If you did not request this password reset invitation please disregard this email.

" . $settings->absoluteWebsiteUrl . "/login/reset/" . $this->user->PasswordResetHash . "/";

    }

    public function getHtml()
    {
        return $this->getHtmlHeading() . $this->getHtmlBody();
    }

    /**
     * @return string
     */
    public function getSubject()
    {
        return "Your password reset invitation.";
    }
    /**
     * Expresses the sendable as an array allowing it to be serialised, stored and recovered later.
     *
     * @return array
     */
    public function toArray()
    {
        return ["UserID" => $this->user->UniqueIdentifier];
    }

    public static function fromArray($array)
    {
        $user = new User($array["UserID"]);
        return Container::instance(ResetPasswordInvitationEmail::class, $user);
    }

    public function getHtmlHeading()
    {
        return "<h1>Password Reset Invitation</h1>";
    }

    public function getHtmlBody()
    {
        $settings = WebsiteSettings::singleton();
        return <<<HtmlBody
<p>You recently requested an invitation to reset your password. Below you will find a
link which when clicked will return you to the site and let you enter a new password.</p>

<p>Please note you must do this within 24 hours or you will need to request a new invitation.</p>

<p>If you did not request this password reset invitation please disregard this email.</p>

<p><a href="{$settings->absoluteWebsiteUrl}/login/reset/{$this->user->PasswordResetHash}/">Click to reset your password</a></p>";
HtmlBody;
    }
}