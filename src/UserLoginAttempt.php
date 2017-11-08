<?php

namespace Rhubarb\Scaffolds\Authentication;

use Rhubarb\Stem\Exceptions\RecordNotFoundException;
use Rhubarb\Stem\Filters\AndGroup;
use Rhubarb\Stem\Filters\Equals;
use Rhubarb\Stem\Models\Model;
use Rhubarb\Stem\Schema\Columns\AutoIncrementColumn;
use Rhubarb\Stem\Schema\Columns\BooleanColumn;
use Rhubarb\Stem\Schema\Columns\DateTimeColumn;
use Rhubarb\Stem\Schema\Columns\ForeignKeyColumn;
use Rhubarb\Stem\Schema\Columns\IntegerColumn;
use Rhubarb\Stem\Schema\Columns\LongStringColumn;
use Rhubarb\Stem\Schema\Columns\StringColumn;
use Rhubarb\Stem\Schema\ModelSchema;

class UserLoginAttempt extends Model
{
    protected function createSchema()
    {
        $modelSchema = new ModelSchema('tblAuthenticationLoginAttempt');
        $modelSchema->addColumn(
            new AutoIncrementColumn('UserLoginAttemptID'),
            new StringColumn('EnteredUsername', 200),
            new BooleanColumn('Successful'),
            new LongStringColumn('ExceptionMessage'),
            new DateTimeColumn('DateCreated'),
            new DateTimeColumn('DateModified')

        );

        return $modelSchema;
    }

    protected function beforeSave()
    {
        $this->DateModified = 'now';

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
                    new Equals("Successful", true)
                ]
            ));
        } catch (RecordNotFoundException $exception) {
        }

        return null;
    }
}
