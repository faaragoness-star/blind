<?php

declare(strict_types=1);

namespace G3D\ValidateSign\Domain;

use DateInterval;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;

class Expiry
{
    private int $defaultTtlDays;

    public function __construct(int $defaultTtlDays = 30)
    {
        $this->defaultTtlDays = $defaultTtlDays;
    }

    public function calculate(?int $ttlDays = null, ?DateTimeImmutable $now = null): DateTimeImmutable
    {
        $days = $ttlDays ?? $this->defaultTtlDays;
        $reference = $now ?? new DateTimeImmutable('now', new DateTimeZone('UTC'));

        return $reference->add(new DateInterval('P' . $days . 'D'));
    }

    public function format(DateTimeImmutable $expiresAt): string
    {
        return $expiresAt->setTimezone(new DateTimeZone('UTC'))->format(DateTimeInterface::ATOM);
    }

    public function isExpired(DateTimeImmutable $expiresAt, ?DateTimeImmutable $now = null): bool
    {
        $reference = $now ?? new DateTimeImmutable('now', new DateTimeZone('UTC'));

        return $expiresAt <= $reference;
    }
}
