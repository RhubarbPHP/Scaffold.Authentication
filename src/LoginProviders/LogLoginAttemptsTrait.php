<?php

namespace Rhubarb\Scaffolds\Authentication\LoginProviders;

use Rhubarb\Crown\LoginProviders\Exceptions\LoginDisabledException;
use Rhubarb\Crown\LoginProviders\Exceptions\LoginExpiredException;
use Rhubarb\Crown\LoginProviders\Exceptions\LoginFailedException;
use Rhubarb\Scaffolds\Authentication\UserLoginAttempt;

trait LogLoginAttemptsTrait
{
    public function login($username, $password)
    {
        try {
            $userLoginAttempt = new UserLoginAttempt();
            $userLoginAttempt->EnteredUsername = $username;
            $userLoginAttempt->save();

            $loginStatus = parent::login($username, $password);

            if ($loginStatus) {
                $userLoginAttempt->Successful = true;
                $userLoginAttempt->save();
            }

            return $loginStatus;
        } catch (LoginDisabledException $loginDisabledException) {
            $userLoginAttempt->Successful = false;
            $userLoginAttempt->ExceptionMessage = (string) $loginDisabledException;
            $userLoginAttempt->save();

            throw $loginDisabledException;
        } catch (LoginFailedException $loginFailedException) {
            $userLoginAttempt->Successful = false;
            $userLoginAttempt->ExceptionMessage = (string) $loginFailedException;
            $userLoginAttempt->save();

            throw $loginFailedException;
        } catch (LoginExpiredException $loginExpiredException) {
            $userLoginAttempt->Successful = false;
            $userLoginAttempt->ExceptionMessage = (string) $loginExpiredException;
            $userLoginAttempt->save();

            throw $loginExpiredException;
        }

        throw new LoginFailedException();
    }
}
