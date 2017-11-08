<?php

namespace Rhubarb\Scaffolds\Authentication\LoginProviders;

use Rhubarb\Stem\LoginProviders\ModelLoginProvider;

class LoggedModelLoginProvider extends ModelLoginProvider
{
    use LogLoginAttemptsTrait;
}
