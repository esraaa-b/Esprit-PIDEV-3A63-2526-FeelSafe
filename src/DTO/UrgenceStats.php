<?php

namespace App\DTO;

class UrgenceStats
{
    public function __construct(
        private int $distinctCount,
        private int $totalCount
    ) {
    }

    public function getDistinctCount(): int
    {
        return $this->distinctCount;
    }

    public function getTotalCount(): int
    {
        return $this->totalCount;
    }
}