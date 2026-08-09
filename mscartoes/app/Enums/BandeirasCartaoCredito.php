<?php

namespace App\Enums;

enum BandeirasCartaoCredito: string
{
    case VISA = 'visa';
    case MASTERCARD = 'mastercard';
    case AMEX = 'amex';
    case ELO = 'elo';
    case HIPERCARD = 'hipercard';
    case DISCOVER = 'discover';
    case DINERSCLUB = 'dinersclub';
    case JCB = 'jcb';
    case CREDZ = 'credz';
    case SOROCRED = 'sorocred';
    case CABAL = 'cabal';
    case BANESCARD = 'banescard';
}
