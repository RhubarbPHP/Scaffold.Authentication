# Change Log

### 1.0.x

* Added:        Method to load users from an identity column value
* Added:        User model validation to ensure that the identifier is not used by any other active record.
* Removed:      Restriction on changing identifier column value after model is created
* Changed:      LoginProvider uses this modules own AuthenticationSettings object to determine the default identity column.

### 1.0.1

* Fixed:		The login url passed to the module constructor wasn't being used for the URL handler.

### 1.0.0

* Changed:        Changes for 1.0.0 of Leaf
