<?php


namespace Rhubarb\Scaffolds\Authentication\Leaves;


class ActivateAccount extends ConfirmResetPassword
{
    protected function getViewClass()
    {
        return ActivateAccountView::class;
    }
}