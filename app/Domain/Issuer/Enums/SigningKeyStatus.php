<?php

namespace App\Domain\Issuer\Enums;

enum SigningKeyStatus: string
{
    case Active = 'active';
    case Next = 'next';
    case Retired = 'retired';
}
