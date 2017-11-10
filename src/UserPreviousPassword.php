<?php

namespace Rhubarb\Scaffolds\Authentication;

use Rhubarb\Crown\DateTime\RhubarbDateTime;
use Rhubarb\Scaffolds\Authentication\Settings\AuthenticationSettings;
use Rhubarb\Stem\Filters\Equals;
use Rhubarb\Stem\Models\Model;
use Rhubarb\Stem\Schema\Columns\AutoIncrementColumn;
use Rhubarb\Stem\Schema\Columns\DateTimeColumn;
use Rhubarb\Stem\Schema\Columns\ForeignKeyColumn;
use Rhubarb\Stem\Schema\Columns\StringColumn;
use Rhubarb\Stem\Schema\ModelSchema;

class UserPreviousPassword extends Model
{
    protected function createSchema()
    {
        $schema = new ModelSchema('tblAuthenticationPastPassword');
        $schema->addColumn(
            new AutoIncrementColumn('UserPreviousPasswordID'),
            new ForeignKeyColumn('UserID'),
            new StringColumn("Password", 200),
            new DateTimeColumn('DateCreated')
        );

        return $schema;
    }

    protected function beforeSave()
    {
        parent::beforeSave();

        if ($this->isNewRecord()) {
            $this->DateCreated = new RhubarbDateTime('now');
        }
    }

    public static function removePreviousPasswords($userID)
    {
        $previousPasswords = self::find(new Equals("UserID", $userID));
        $previousPasswords->addSort("DateCreated", false);

        $totalPreviousPasswordsToStore = AuthenticationSettings::singleton()->totalPreviousPasswordsToStore;
        if ($previousPasswords->count() >= $totalPreviousPasswordsToStore) {
            $previousPasswords->setRange($totalPreviousPasswordsToStore - 1, 200);
            foreach ($previousPasswords as $passwordToRemove)
            {
                $passwordToRemove->delete();
            }
        }
    }
}
