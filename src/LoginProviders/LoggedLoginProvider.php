<?php

namespace Rhubarb\Scaffolds\Authentication\LoginProviders;

class LoggedLoginProvider extends LoginProvider
{
    use LogLoginAttemptsTrait;
}
