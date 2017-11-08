<?php

namespace Rhubarb\Scaffolds\Authentication\LoginProviders;

use Rhubarb\Crown\LoginProviders\Exceptions\LoginDisabledException;
use Rhubarb\Crown\LoginProviders\Exceptions\LoginDisabledFailedAttemptsException;
use Rhubarb\Crown\LoginProviders\Exceptions\LoginExpiredException;
use Rhubarb\Crown\LoginProviders\Exceptions\LoginFailedException;
use Rhubarb\Scaffolds\Authentication\UserLoginAttempt;

trait LogLoginAttemptsTrait
{
    public function login($username, $password)
    {
        try {
            $loginStatus = parent::login($username, $password);

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
        } catch (LoginDisabledFailedAttemptsException $loginDisabledFailedAttemptsException) {
            $this->createFailedUserLoginAttempt($username, (string) $loginDisabledFailedAttemptsException);
            throw $loginDisabledFailedAttemptsException;
        }
    }

    private function createSuccessfulUserLoginAttempt($username)
    {
        $userLoginAttempt = new UserLoginAttempt();
        $userLoginAttempt->EnteredUsername = $username;
        $userLoginAttempt->Successful = true;
        $userLoginAttempt->save();
    }

    private function createFailedUserLoginAttempt($username, $exceptionMessage)
    {
        $userLoginAttempt = new UserLoginAttempt();
        $userLoginAttempt->EnteredUsername = $username;
        $userLoginAttempt->ExceptionMessage = $exceptionMessage;
        $userLoginAttempt->Successful = false;
        $userLoginAttempt->save();
    }
}
