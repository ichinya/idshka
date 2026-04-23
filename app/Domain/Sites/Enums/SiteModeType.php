<?php

namespace App\Domain\Sites\Enums;

enum SiteModeType: string
{
    case ApiResource = 'api_resource';
    case WebClient = 'web_client';
}
