<?php

declare(strict_types=1);

namespace G3D\VendorBase\Time;

final class FixedClock implements Clock
{
    public function __construct(private \DateTimeImmutable $fixed)
    {
    }

    public function now(): \DateTimeImmutable
    {
        return $this->fixed;
    }

    public function advance(\DateInterval $delta): void
    {
        $this->fixed = $this->fixed->add($delta);
    }
}
