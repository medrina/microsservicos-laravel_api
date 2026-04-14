<?php

namespace App\Enums;

enum BandeirasCartaoCredito: string
{
    case VISA = 'visa';
    case MASTERCARD = 'mastercard';
    case AMEX = 'amex';
    case ELO = 'elo';
}
