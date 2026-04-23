<?php

namespace App\Domain\Sites\Enums;

enum SiteVerificationStatus: string
{
    case Pending = 'pending';
    case Verified = 'verified';
    case Failed = 'failed';
    case Expired = 'expired';
}
