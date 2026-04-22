<?php

namespace App\Domain\Sites\Contracts;

interface DnsTxtRecordLookup
{
    /**
     * @return array<int, string>
     */
    public function getTxtRecords(string $host): array;
}
