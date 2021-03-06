<?php

namespace Rhubarb\Scaffolds\Authentication;

use Rhubarb\Crown\Request\Request;
use Rhubarb\Stem\Exceptions\RecordNotFoundException;
use Rhubarb\Stem\Filters\AndGroup;
use Rhubarb\Stem\Filters\Equals;
use Rhubarb\Stem\Filters\OrGroup;
use Rhubarb\Stem\Models\Model;
use Rhubarb\Stem\Repositories\MySql\Schema\Columns\MySqlEnumColumn;
use Rhubarb\Stem\Schema\Columns\AutoIncrementColumn;
use Rhubarb\Stem\Schema\Columns\BooleanColumn;
use Rhubarb\Stem\Schema\Columns\DateTimeColumn;
use Rhubarb\Stem\Schema\Columns\ForeignKeyColumn;
use Rhubarb\Stem\Schema\Columns\IntegerColumn;
use Rhubarb\Stem\Schema\Columns\LongStringColumn;
use Rhubarb\Stem\Schema\Columns\StringColumn;
use Rhubarb\Stem\Schema\ModelSchema;

class UserLog extends Model
{
    const USER_LOG_LOGIN_SUCCESSFUL = 'Login successful';
    const USER_LOG_LOGIN_FAILED = 'Login failed';
    const USER_LOG_LOGIN_EXPIRED = 'Login expired';
    const USER_LOG_LOGIN_LOCKED = 'Login locked out';
    const USER_LOG_LOGIN_DISABLED = 'Login disabled';
    const USER_LOG_PASSWORD_CHANGED = 'Password changed';

    protected function createSchema()
    {
        $modelSchema = new ModelSchema('tblAuthenticationUserLog');
        $modelSchema->addColumn(
            new AutoIncrementColumn('UserLogID'),
            new ForeignKeyColumn('UserID'),
            new StringColumn('EnteredUsername', 200),
            new MySqlEnumColumn("LogType", '', [
                '',
                self::USER_LOG_LOGIN_SUCCESSFUL,
                self::USER_LOG_LOGIN_LOCKED,
                self::USER_LOG_LOGIN_FAILED,
                self::USER_LOG_LOGIN_EXPIRED,
                self::USER_LOG_LOGIN_DISABLED,
                self::USER_LOG_PASSWORD_CHANGED
            ]),
            new LongStringColumn('Data'),
            new DateTimeColumn('DateCreated'),
            new StringColumn('IPAddress', 40)
        );

        return $modelSchema;
    }

    protected function beforeSave()
    {
        if ($this->isNewRecord()) {
            $this->DateCreated = 'now';
            $this->IPAddress = self::getUserIpAddress();
        }

        parent::beforeSave();
    }

    public static function getUserIpAddress() : string {
        // Support for Amazon ELB's
        if(!empty($_SERVER['HTTP_X_FORWARDED_FOR'])){
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        }

        if (!empty($_SERVER['REMOTE_ADDR'])) {
            return $_SERVER['REMOTE_ADDR'];
        }

        return '';
    }

    public static function getLastSuccessfulLoginAttempt($username)
    {
        try {
            return self::findLast(new AndGroup(
                [
                    new Equals("EnteredUsername", $username),
                    new Equals("LogType", self::USER_LOG_LOGIN_SUCCESSFUL)
                ]
            ));
        } catch (RecordNotFoundException $exception) {
        }

        return null;
    }

    public static function getLastSuccessfulPasswordChangeAttempt($userId)
    {
        try {
            return self::findLast(new AndGroup(
                [
                    new Equals("UserID", $userId),
                    new Equals("LogType", self::USER_LOG_PASSWORD_CHANGED)
                ]
            ));
        } catch (RecordNotFoundException $exception) {
        }

        return null;
    }

    public static function getLastSuccessfulUserLoginOrPasswordChangeAttempt($username, $userId)
    {
        try {
            return self::findLast(
                new OrGroup([
                    new AndGroup(
                        [
                            new Equals("EnteredUsername", $username),
                            new Equals("LogType", self::USER_LOG_LOGIN_SUCCESSFUL)
                        ]),
                    new AndGroup(
                        [
                            new Equals("UserID", $userId),
                            new Equals("LogType", self::USER_LOG_PASSWORD_CHANGED)
                        ]),
                ])
            );
        } catch (RecordNotFoundException $exception) {
        }

        return null;
    }
}
