<?php

namespace Rhubarb\Scaffolds\Authentication;

use Rhubarb\Stem\Exceptions\RecordNotFoundException;
use Rhubarb\Stem\Filters\AndGroup;
use Rhubarb\Stem\Filters\Equals;
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
    const USER_LOG_LOGIN_LOCKED = 'Login lLocked out';
    const USER_LOG_LOGIN_DISABLED = 'Login disabled';
    const USER_LOG_PASSWORD_CHANGED = 'Password changed';

    protected function createSchema()
    {
        $modelSchema = new ModelSchema('tblAuthenticationUserLog');
        $modelSchema->addColumn(
            new AutoIncrementColumn('UserLogID'),
            new ForeignKeyColumn('UserID'),
            new StringColumn('EnteredUsername', 200),
            new MySqlEnumColumn("LogType", null, [
                self::USER_LOG_LOGIN_SUCCESSFUL,
                self::USER_LOG_LOGIN_LOCKED,
                self::USER_LOG_LOGIN_FAILED,
                self::USER_LOG_LOGIN_EXPIRED,
                self::USER_LOG_LOGIN_DISABLED,
                self::USER_LOG_PASSWORD_CHANGED
            ]),
            new LongStringColumn('Message'),
            new LongStringColumn('Data'),
            new DateTimeColumn('DateCreated')

        );

        return $modelSchema;
    }

    protected function beforeSave()
    {
        if ($this->isNewRecord()) {
            $this->DateCreated = 'now';
        }

        parent::beforeSave();
    }

    public static function getLastSuccessfulLoginAttempt($username)
    {
        try {
            return self::findFirst(new AndGroup(
                [
                    new Equals("EnteredUsername", $username),
                    new Equals("LogType", self::USER_LOG_LOGIN_SUCCESSFUL)
                ]
            ));
        } catch (RecordNotFoundException $exception) {
        }

        return null;
    }
}
