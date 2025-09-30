<?php

declare(strict_types=1);

namespace G3D\VendorBase\Time;

interface Clock
{
    public function now(): \DateTimeImmutable;
}
