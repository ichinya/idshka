<?php

namespace App\Domain\Sites\Services;

use Illuminate\Support\Str;

final class SiteIdFactory
{
    public function make(): string
    {
        return 'site_'.Str::lower((string) Str::ulid());
    }
}
