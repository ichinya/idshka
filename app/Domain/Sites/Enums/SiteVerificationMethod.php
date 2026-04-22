<?php

namespace App\Domain\Sites\Enums;

enum SiteVerificationMethod: string
{
    case DnsTxt = 'dns_txt';
    case File = 'file';
}
