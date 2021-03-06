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

namespace Rhubarb\Scaffolds\Authentication;

use Rhubarb\Crown\DateTime\RhubarbDateTime;
use Rhubarb\Crown\Encryption\HashProvider;
use Rhubarb\Crown\LoginProviders\Exceptions\NotLoggedInException;
use Rhubarb\Crown\LoginProviders\LoginProvider;
use Rhubarb\Scaffolds\Authentication\Exceptions\TokenException;
use Rhubarb\Scaffolds\Authentication\Settings\AuthenticationSettings;
use Rhubarb\Stem\Exceptions\ModelConsistencyValidationException;
use Rhubarb\Stem\Exceptions\RecordNotFoundException;
use Rhubarb\Stem\Filters\AndGroup;
use Rhubarb\Stem\Filters\Equals;
use Rhubarb\Stem\Filters\GreaterThan;
use Rhubarb\Stem\Filters\Not;
use Rhubarb\Stem\Interfaces\ValidateLoginModelInterface;
use Rhubarb\Stem\Models\Model;
use Rhubarb\Stem\Schema\Columns\AutoIncrementColumn;
use Rhubarb\Stem\Schema\Columns\BooleanColumn;
use Rhubarb\Stem\Schema\Columns\DateTimeColumn;
use Rhubarb\Stem\Schema\Columns\StringColumn;
use Rhubarb\Stem\Schema\ModelSchema;

class User extends Model
{
    /**
     * This flag is used to check whether a password has been changed and needs to be validated
     * inside getConsistencyValidationErrors()
     * @var bool
     */
    private $passwordChanged = false;

    protected $identityColumnName = 'Username';

    /**
     * Returns the schema for this data object.
     *
     * @return \Rhubarb\Stem\Schema\ModelSchema
     */
    protected function createSchema()
    {
        $schema = new ModelSchema("tblAuthenticationUser");

        $schema->addColumn(
            new AutoIncrementColumn("UserID"),
            new StringColumn("Username", 30, null),
            new StringColumn("Password", 200),
            new StringColumn("Forename", 80),
            new StringColumn("Surname", 80),
            new StringColumn("Email", 150),
            new StringColumn("Token", 200),
            new DateTimeColumn("TokenExpiry"),
            new BooleanColumn("Enabled", false),
            new StringColumn("PasswordResetHash", 200),
            new DateTimeColumn("PasswordResetDate"),
            new DateTimeColumn("LastPasswordChangeDate")
        );

        $schema->labelColumnName = "FullName";

        return $schema;
    }

    public function getFullName()
    {
        return trim($this->Forename . " " . $this->Surname);
    }

    protected function setDefaultValues()
    {
        parent::setDefaultValues();

        // New users should be enabled by default.
        $this->Enabled = true;
    }

    /**
     * Creates and returns a reset password hash that can be emailed to the user to invite them to reset their password.
     */
    public function generatePasswordResetHash()
    {
        $hashProvider = HashProvider::getProvider();
        $hash = sha1($hashProvider->createHash($this->UserID . uniqid(), uniqid("salt")));

        $this->PasswordResetHash = $hash;
        $this->PasswordResetDate = "now";
        $this->save();

        return $hash;
    }

    public function setNewPassword($password)
    {
        $provider = HashProvider::getProvider();

        $this->Password = $provider->createHash($password);
        $this->PasswordResetHash = "";

        $this->LastPasswordChangeDate = new RhubarbDateTime('now');

        $this->passwordChanged = true;
    }

    /**
     * Returns a user with a matching password reset hash.
     *
     * @param $hash
     * @return User
     */
    public static function fromPasswordResetHash($hash)
    {
        return static::findFirst(new Equals("PasswordResetHash", $hash));
    }

    /**
     * @param $username
     * @return User
     * @throws \Rhubarb\Stem\Exceptions\RecordNotFoundException
     * @deprecated Use fromIdentifierColumnValue instead.
     */
    public static function fromUsername($username)
    {
        return static::fromIdentifierColumnValue('Username', $username);
    }

    /**
     * @param mixed $value
     * @return Model|static
     */
    public static function fromIdentifierColumnValue($identityColumnName, $value)
    {
        return static::findFirst(new Equals($identityColumnName, $value));
    }

    /**
     * Returns a unique StringColumn identifying this record in the user table.
     *
     * @throws Exceptions\TokenException
     * @return StringColumn
     */
    private function getSavedPasswordTokenData()
    {
        if ($this->isNewRecord()) {
            // We can't fulfil the request as we have no UserID which is required for the StringColumn.
            throw new TokenException("The user has not been saved");
        }

        return sha1($this->Username . $this->Password . $this->FullName . $this->Enabled . $this->UserID);
    }

    /**
     * Creates a token for the user which allows for logging in via a cookie.
     *
     * @throws Exceptions\TokenException
     * @return StringColumn The token.
     */
    public function createToken()
    {
        $hashProvider = HashProvider::getProvider();
        $token = $hashProvider->createHash($this->getSavedPasswordTokenData(), sha1($this->Password));

        $this->Token = $token;
        $this->TokenExpiry = date("Y-m-d H:i:s", strtotime("+2 weeks"));
        $this->save();

        return $token;
    }

    protected function getConsistencyValidationErrors()
    {
        $errors = parent::getConsistencyValidationErrors();

        if ($this->Enabled) {

            // See if the identity is in use.
            $identityFilter = new Equals($this->identityColumnName, $this[$this->identityColumnName]);
            if (!$this->isNewRecord()) {
                $identityFilter = new AndGroup([
                    $identityFilter,
                    new Not(new Equals($this->getUniqueIdentifierColumnName(), $this->getUniqueIdentifier()))
                ]);
            }
            try
            {
                self::findFirst($identityFilter);
                $errors[$this->identityColumnName] = "This ".$this->identityColumnName." is already in use";
            }
            catch(RecordNotFoundException $ex) {
                // all is well!
            }

            if (!$this[$this->identityColumnName]) {
                $errors[$this->identityColumnName] = "The user must have a ".$this->identityColumnName;
            }

            if ($this->FullName == "") {
                $errors["Name"] = "The user must have a name";
            }
        }

        return $errors;
    }

    /**
     * Checks that the token supplied is valid for this user.
     *
     * @param $token
     * @return bool
     */
    public function validateToken($token)
    {
        // The token must match of course.
        if ($this->Token != $token) {
            return false;
        }

        // Has the token expired?
        if (strtotime($this->TokenExpiry) < time()) {
            return false;
        }

        $hashProvider = HashProvider::getProvider();
        return $hashProvider->compareHash($this->getSavedPasswordTokenData(), $token);
    }
}
