<?php

namespace App\Enums;

enum WalletTransactionType: string
{
    case Deposit = 'deposit';
    case CommissionDeduction = 'commission_deduction';
    case Adjustment = 'adjustment';
}
