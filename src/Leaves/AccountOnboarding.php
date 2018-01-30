<?php

namespace Rhubarb\Scaffolds\Authentication\Leaves;

class AccountOnboarding extends ConfirmResetPassword
{
    protected function getViewClass()
    {
        return AccountOnboardingView::class;
    }
}