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

use Rhubarb\Crown\AppSettings;
use Rhubarb\Crown\Integration\Email\TemplateEmail;

class ResetPasswordInvitationEmail extends TemplateEmail
{
    protected function getTextTemplateBody()
    {
        $appSettings = new AppSettings();

        return "You recently requested an invitation to reset your password. Below you will find a
link which when clicked will return you to the site and let you enter a new password.

Please note you must do this within 24 hours or you will need to request a new invitation.

If you did not request this password reset invitation please disregard this email.

" . $appSettings->AppBaseUrl . "/login/reset/{PasswordResetHash}/";

    }

    protected function getHtmlTemplateBody()
    {
        $appSettings = new AppSettings();

        return "
<h1>Password Reset Invitation</h1>
<p>You recently requested an invitation to reset your password. Below you will find a
link which when clicked will return you to the site and let you enter a new password.</p>

<p>Please note you must do this within 24 hours or you will need to request a new invitation.</p>

<p>If you did not request this password reset invitation please disregard this email.</p>

<p><a href=\"" . $appSettings->AppBaseUrl . "/login/reset/{PasswordResetHash}/\">Click to reset password</a></p>";
    }

    protected function getSubjectTemplate()
    {
        return "Your password reset invitation.";
    }
}