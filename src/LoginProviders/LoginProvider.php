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

namespace Rhubarb\Scaffolds\Authentication\LoginProviders;

use Rhubarb\Crown\DateTime\RhubarbDateTime;
use Rhubarb\Crown\Encryption\HashProvider;
use Rhubarb\Crown\Http\HttpResponse;
use Rhubarb\Crown\Logging\Log;
use Rhubarb\Crown\Request\Request;
use Rhubarb\Scaffolds\Authentication\Exceptions\LoginDisabledException;
use Rhubarb\Scaffolds\Authentication\Exceptions\LoginTemporarilyLockedOutException;
use Rhubarb\Scaffolds\Authentication\Exceptions\LoginExpiredException;
use Rhubarb\Scaffolds\Authentication\Exceptions\LoginFailedException;
use Rhubarb\Scaffolds\Authentication\Settings\AuthenticationSettings;
use Rhubarb\Scaffolds\Authentication\Settings\LoginProviderSettings;
use Rhubarb\Scaffolds\Authentication\User;
use Rhubarb\Scaffolds\Authentication\UserLog;
use Rhubarb\Stem\Collections\RepositoryCollection;
use Rhubarb\Stem\Exceptions\ModelConsistencyValidationException;
use Rhubarb\Stem\Exceptions\RecordNotFoundException;
use Rhubarb\Stem\Filters\AndGroup;
use Rhubarb\Stem\Filters\Equals;
use Rhubarb\Stem\Filters\GreaterThan;
use Rhubarb\Stem\LoginProviders\ModelLoginProvider;

class LoginProvider extends ModelLoginProvider
{
    protected $usernameColumnName = "";
    protected $passwordColumnName = "";
    protected $activeColumnName = "";

    /**
     * @var LoginProviderSettings
     */
    private $providerSettings;

    /**
     * @param string $modelClassName
     * @param string|null $usernameColumnName Leave null to inherit from AuthenticationSettings::$identifyColumnName (Username by default)
     * @param string $passwordColumnName
     * @param string $activeColumnName
     */
    public function __construct($modelClassName = "User", $usernameColumnName = "Username", $passwordColumnName = "Password", $activeColumnName = "Enabled")
    {
        $this->usernameColumnName = $usernameColumnName;
        $this->passwordColumnName = $passwordColumnName;
        $this->activeColumnName = $activeColumnName;

        $this->providerSettings = $this->generateSettings();

        parent::__construct($modelClassName);
    }

    /**
     * Returns the configuration options for this login provider.
     *
     * The exposing of these properties allows for other systems (such as the UI) to match
     * the validated behaviour of the login provider.
     *
     * @return LoginProviderSettings
     */
    public function getSettings()
    {
        return $this->providerSettings;
    }

    protected function generateSettings()
    {
        $settings = new LoginProviderSettings();
        $settings->identityColumnName = $this->usernameColumnName;
        $settings->lockoutAccountAfterFailedLoginAttempts = true;
        $settings->numberOfFailedLoginAttemptsBeforeLockout = 3;
        $settings->numberOfPreviousPasswords = 3;
        $settings->totalPreviousPasswordsToStore = 5;
        $settings->totalMinutesToLockUserAccount = 10;

        return $settings;
    }

    protected function initialiseDefaultValues()
    {
        parent::initialiseDefaultValues();

        $this->detectRememberMe();
    }

    protected function isModelActive($model)
    {
        return ($model[$this->activeColumnName] == true);
    }

    public function login($username, $password)
    {
        try {
            $loginStatus = $this->attemptLogin($username, $password);

            if ($loginStatus) {
                $this->createSuccessfulUserLoginAttempt($username);
            }

            return $loginStatus;
        } catch (LoginDisabledException $loginDisabledException) {
            $this->createFailedUserLoginAttempt($username, (string) $loginDisabledException);
            throw $loginDisabledException;
        } catch (LoginFailedException $loginFailedException) {
            $this->createFailedUserLoginAttempt($username, (string) $loginFailedException);
            throw $loginFailedException;
        } catch (LoginExpiredException $loginExpiredException) {
            $this->createFailedUserLoginAttempt($username, (string) $loginExpiredException);
            throw $loginExpiredException;
        } catch (LoginTemporarilyLockedOutException $loginDisabledFailedAttemptsException) {
            $this->createFailedUserLoginAttempt($username, (string) $loginDisabledFailedAttemptsException);
            throw $loginDisabledFailedAttemptsException;
        }
    }

    protected function attemptLogin($username, $password)
    {
        // We don't allow spaces around our usernames and passwords
        $username = trim($username);
        $password = trim($password);

        if ($username == "") {
            throw new LoginFailedException();
        }

        $list = new RepositoryCollection($this->modelClassName);
        $list->filter(new Equals($this->usernameColumnName, $username));

        if (!sizeof($list)) {
            Log::debug("Login failed for {$username} - the username didn't match a user", "LOGIN");
            throw new LoginFailedException();
        }

        $hashProvider = HashProvider::getProvider();

        // There should only be one user matching the username. It would be possible to support
        // unique *combinations* of username and password but it's a potential security issue and
        // could trip us up when supporting the project.
        $existingActiveUsers = 0;
        foreach ($list as $user) {
            if ($this->isModelActive($user)) {
                $activeUser = $user;
                $existingActiveUsers++;
            }

            if ($existingActiveUsers > 1) {
                Log::debug("Login failed for {$username} - the username wasn't unique", "LOGIN");
                throw new LoginFailedException();
            }
        }

        $this->checkUserIsPermitted($activeUser);

        // Test the password matches.
        $userPasswordHash = $activeUser[$this->passwordColumnName];

        if ($hashProvider->compareHash($password, $userPasswordHash)) {
            $this->loggedIn = true;
            $this->loggedInUserIdentifier = $activeUser->getUniqueIdentifier();

            $this->storeSession();

            return true;
        }

        Log::debug("Login failed for {$username} - the password hash $userPasswordHash didn't match the stored hash.", "LOGIN");

        throw new LoginFailedException();
    }
    
    public function changePassword(User $user, $password)
    {
        //  Validate new password has not been previously used
        $numberOfPastPasswordsToCompareTo = $this->getSettings()->numberOfPreviousPasswords;

        if ($numberOfPastPasswordsToCompareTo) {
            $hashProvider = HashProvider::getProvider();

            $userPastPasswords = UserLog::find(
                new Equals("LogType", UserLog::USER_LOG_PASSWORD_CHANGED),
                new Equals("UserID", $user->UniqueIdentifier)
            );

            $userPastPasswords->addSort("DateCreated", false);
            $userPastPasswords->setRange(0, $numberOfPastPasswordsToCompareTo);

            foreach ($userPastPasswords as $log) {
                if ($hashProvider->compareHash($user->Password, $log->Data)) {
                    $errors["Password"] = "The password you have entered has already been used. Please enter a new password.";

                    throw new ModelConsistencyValidationException($errors);
                }
            }
        }

        $user->setNewPassword($password);
        $user->save();

        // Only keep a fixed number of passwords. We keep the log entry but clear the 'data' - no point
        // keeping a trove of hashed passwords for someone to steal!
        $previousPasswordLogs = UserLog::find(
            new Equals("LogType", UserLog::USER_LOG_PASSWORD_CHANGED),
            new Equals("UserID", $user->UniqueIdentifier)
        );

        if ($previousPasswordLogs->count() >= $numberOfPastPasswordsToCompareTo) {
            $previousPasswordLogs->setRange($numberOfPastPasswordsToCompareTo - 1, 200);
            foreach ($previousPasswordLogs as $passwordToRemove)
            {
                $passwordToRemove->Data = '';
                $passwordToRemove->save();
            }
        }

        $log = new UserLog();
        $log->UserID = $user->UniqueIdentifier;
        $log->LogType = UserLog::USER_LOG_PASSWORD_CHANGED;
        $log->Data = $user->Password;
        $log->save();
        
    }

    /**
     * Provides an opportunity for extending classes to do additional checks on the user object before
     * allowing them to login.
     *
     * You should throw an exception if you want to prevent the login.
     *
     * @param $user
     * @throws \Exception Thrown if the user should not be permitted to login.
     */
    protected function checkUserIsPermitted($user)
    {
        if (!isset($user)) {
            Log::debug("Login failed for ".$user[$this->usernameColumnName]." - the user is disabled.", "LOGIN");
            throw new LoginDisabledException();
        }

        if ($this->hasPasswordExpired($user)){
            Log::debug("Login failed for ".$user[$this->usernameColumnName]." - the password has expired.", "LOGIN");
            throw new LoginExpiredException();
        }

        if ($this->isUserTemporarilyLockedOut($user)){
            Log::debug("Login failed for ".$user[$this->usernameColumnName]." - the user is temporarily disabled.", "LOGIN");
            throw new LoginTemporarilyLockedOutException();
        }
    }

    public function hasPasswordExpired(User $user)
    {
        $settings = $this->getSettings();

        $passwordExpirationDaysInterval = $settings->passwordExpirationIntervalInDays;

        /** @var $lastPasswordChangeDate \Rhubarb\Crown\DateTime\RhubarbDateTime */
        $lastPasswordChangeDate = $user->LastPasswordChangeDate;
        $currentDate = new RhubarbDateTime('now');

        if ($passwordExpirationDaysInterval && $lastPasswordChangeDate && $lastPasswordChangeDate->isValidDateTime()) {
            $timeDifference = $currentDate->diff($lastPasswordChangeDate);
            if ($timeDifference->totalDays > $passwordExpirationDaysInterval) {
                return true;
            }
        }

        return false;
    }

    public function isUserTemporarilyLockedOut(User $user)
    {
        $settings = $this->getSettings();

        if (!$settings->lockoutAccountAfterFailedLoginAttempts) {
            return false;
        }

        $andGroupFilter = new AndGroup();
        $andGroupFilter->addFilters(new Equals("EnteredUsername", $user[$settings->identityColumnName]));
        $andGroupFilter->addFilters(new Equals("Successful", false));

        // Retrieve last successful login attempt
        $lastSuccesfulLoginAttempt = UserLog::getLastSuccessfulLoginAttempt($user[$settings->identityColumnName]);
        if ($lastSuccesfulLoginAttempt) {
            $andGroupFilter->addFilters(new GreaterThan("UserLogID", $lastSuccesfulLoginAttempt->UserLogID));
        }

        //  Get all failed login attempts from the last successful login if one can be found
        $failedUserLoginAttempts = UserLog::find($andGroupFilter);
        $failedUserLoginAttempts->addSort("DateCreated", false);

        if ($failedUserLoginAttempts->count() >= $settings->numberOfFailedLoginAttemptsBeforeLockout) {
            $currentDate = new RhubarbDateTime('now');

            //  Check if the most recent Failed Login attempt was within the $totalMinutesToDisableUserAccount set within the AuthenticationSettings
            $mostRecentFailedLoginAttempt = $failedUserLoginAttempts[0];

            $timeDifference = $currentDate->diff($mostRecentFailedLoginAttempt->DateCreated);
            if ($timeDifference->totalMinutes < $settings->totalMinutesToLockUserAccount) {
                return true;
            } else {
                return false;
            }
        }

        return false;
    }

    private function createSuccessfulUserLoginAttempt($username)
    {
        $userLoginAttempt = new UserLog();
        $userLoginAttempt->LogType = UserLog::USER_LOG_LOGIN_SUCCESSFUL;
        $userLoginAttempt->EnteredUsername = $username;
        $userLoginAttempt->save();
    }

    private function createFailedUserLoginAttempt($username, $exceptionMessage)
    {
        $userLoginAttempt = new UserLog();
        $userLoginAttempt->LogType = UserLog::USER_LOG_LOGIN_FAILED;
        $userLoginAttempt->EnteredUsername = $username;
        $userLoginAttempt->Message = $exceptionMessage;
        $userLoginAttempt->save();
    }

    protected function getUsername()
    {
        $user = $this->getModel();

        return $user->{$this->usernameColumnName};
    }

    public function rememberLogin()
    {
        $user = $this->getLoggedInUser();

        HttpResponse::setCookie('lun', $this->getUsername());
        HttpResponse::setCookie('ltk', $user->createToken());
    }

    protected function onLogOut()
    {
        parent::onLogOut();
        HttpResponse::unsetCookie('lun');
        HttpResponse::unsetCookie('ltk');
    }

    /**
     * Gets the User model currently logged in user
     *
     * @return User
     * @throws NotLoggedInException Thrown if the user isn't logged in.
     */
    public static function getLoggedInUser()
    {
        $provider = new static();
        return $provider->getModel();
    }

    protected function detectRememberMe()
    {
        // If we're not logged in, let's see if we can auto login using a saved token.
        if (!$this->isLoggedIn()) {
            $request = Request::current();

            if ($request->cookie('lun') != "") {
                $username = $request->cookie('lun');
                try {
                    $user = User::fromIdentifierColumnValue($this->getSettings()->identityColumnName, $username);

                    $token = $request->cookie('ltk');

                    if ($user->validateToken($token)) {
                        $this->loggedIn = true;
                        $this->loggedInUserIdentifier = $user->UniqueIdentifier;
                        $this->storeSession();
                    }
                } catch (RecordNotFoundException $ex) {
                }
            }
        }
    }
}
