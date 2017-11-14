<?php

namespace Rhubarb\Scaffolds\Authentication\Settings;

class LoginProviderSettings
{
    /**
     * The column used to identify users in the user table.
     *
     * @var string
     */
    public $identityColumnName = "Username";

    /**
     * Used to detect the number of days that should be between a Password being changed
     *
     * This will be used to ensure a Password has to be changed when the number of days between LastPasswordChangeDate
     * and CurrentDate is greater than the passwordExpirationInterval
     *
     * Zero indicates password expiration is not checked.
     *
     * @var int
     */
    public $passwordExpirationIntervalInDays = 0;

    /**
     * Used to validate how many previous passwords should be stored when a user updates their Password
     * @var int
     */
    public $totalPreviousPasswordsToStore = 0;

    /**
     * Used to check how many previous passwords should be compared with when a user changes their Password
     * This is used to ensure a User cannot just reuse their previous password each time
     *
     * NOTE: When this property is set to Zero the setting we disabled
     *
     * @var int
     */
    public $numberOfPreviousPasswordsToCompareTo = 0;

    /****** FAILED LOGIN ATTEMPTS SETTINGS ******/

    /**
     * The following flag is used to check if a users account should be disabled after a set amount of failed login attempts
     *
     * @var bool
     */
    public $lockoutAccountAfterFailedLoginAttempts = false;

    /**
     * The total number of failed login attempts that should be allowed before disabling the users account for a set amount of time
     * @var int
     */
    public $numberOfFailedLoginAttemptsBeforeLockout = 0;

    /**
     * The total number of minutes a users account should be disabled for when a users account is being marked as disabled
     * @var int
     */
    public $totalMinutesToLockUserAccount = 0;
}