<?php
/**
 * Copyright (c) 2016 RhubarbPHP.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *      http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace Rhubarb\Scaffolds\Authentication\Settings;

use Rhubarb\Crown\Settings;

class AuthenticationSettings extends Settings
{
    /**
     * The column used to identify users in the user table.
     * 
     * @var string
     */
    public $identityColumnName = "Username";

    /**
     * Used to detect the number of days that should be between a Password being changed
     * This will be used to ensure a Password has to be changed when the number of days between LastPasswordChangeDate
     * and CurrentDate is greater than the passwordExpirationInterval
     *
     * @var int
     */
    public $passwordExpirationInterval = 0;
}
