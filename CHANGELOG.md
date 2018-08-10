# Change Log

### 1.3.0

* Fixed:        Log type field not being set properly upon different failures of login
* Changed:      Extraction of URL for redirection now moved to Url handler 
* Added:        Redirection only now possible to relative or absolute paths on the same domain.
* Added:        Redirection doesn't occur if the login is attempted using an XHR pathway 

### 1.2.13

* Fixed:	    Ensures password expiration only checking if the password expiration is in the past

### 1.2.12

* Fixed:	    Issue with password reset

### 1.2.11

* Fixed:        Logout redirects to given url to prevent NotLoggedInException from /login?logout=1

### 1.2.10

* Changed:      Automatically redirecting to previous URL after login 

### 1.2.9

* Fixed:        Fixed the check for a User account being Locked and checking a column that no longer exists on the UserLog table

### 1.2.8

* Fixed:        Fix for issue with Exception Logging
* Added:        Added new ExceptionMessage Column

### 1.2.7

* Fixed:        Fixed exception being thrown on the Login Leaf due to LoginFailedException being moved to the Rhubarb module

### 1.2.6

* Fixed:        Fix for validating if a user is reusing a previously entered password.

### 1.2.5

* Changed:      Updated LoginProvider failed login requests due to bad credentials to throw the exception CredentialsFailedException

### 1.2.4:

* Fixed:	ApplicationLayout tried to use non existant method after refactor

### 1.2.3:

* Fixed:        Fixed stemmodule error

### 1.2.2:

* Changed:      LoginProvider now uses the CredentialsLoginProviderInterface 

### 1.2.1:

* Changed:	    Added $identityColumnName property in User model to allow consistancy validation
		        to work for models not using the Username field.
* Added:        Added inbuilt activation UI

### 1.2.0

* Changed:      Supports Stem 1.5 with basic model login behaviours moved into this library.
* Changed:      Login settings are now part of the login provider and passed to all UI leaves
                properly allowing completely independent login providers to work concurrently
                with different settings in the same application.

### 1.1.4

* Fixed:	    Reset password broken	

### 1.1.3

* Fixed:	    Removed unnecessary second argument to TextBox

### 1.1.2

* Fixed:        Attempt to base64 decode an unencoded redirect URL

### 1.1.1

* Fix:		    Fixed backwards issue with default provider not being registered

### 1.1.0

* Changed:      Model initialisation to allow easily overriding the model class
* Added:        Support for multiple URLs protected by different LoginProviders

### 1.0.2

* Added:        Autofocus first credentials field on login form
* Added:        User identity column support to remember me
* Added:        User identity column support to reset password 
* Deprecated:   Loading users from Username, should be loaded from identity column value
* Added:        Method to load users from an identity column value
* Added:        User model validation to ensure that the identifier is not used by any other active record.
* Removed:      Restriction on changing identifier column value after model is created
* Changed:      LoginProvider uses this modules own AuthenticationSettings object to determine the default identity column.

### 1.0.1

* Fixed:		The login url passed to the module constructor wasn't being used for the URL handler.

### 1.0.0

* Changed:      Changes for 1.0.0 of Leaf
