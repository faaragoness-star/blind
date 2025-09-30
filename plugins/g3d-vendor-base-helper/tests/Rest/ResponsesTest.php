<?php

declare(strict_types=1);

namespace G3D\VendorBase\Tests\Rest;

use G3D\VendorBase\Rest\Responses;
use PHPUnit\Framework\TestCase;

final class ResponsesTest extends TestCase
{
    public function testOkReturnsOkFlagWhenNoDataProvided(): void
    {
        self::assertSame(['ok' => true], Responses::ok());
    }

    public function testOkMergesAdditionalPayload(): void
    {
        $result = Responses::ok(['foo' => 'bar']);

        self::assertSame([
            'ok' => true,
            'foo' => 'bar',
        ], $result);
    }

    public function testOkKeepsOkTrueWhenCallerProvidesKey(): void
    {
        $result = Responses::ok(['ok' => false, 'extra' => 1]);

        self::assertSame([
            'ok' => true,
            'extra' => 1,
        ], $result);
    }

    public function testErrorReturnsCanonicalShape(): void
    {
        $result = Responses::error('E_SAMPLE', 'sample_reason', 'Sample detail');

        self::assertSame([
            'ok' => false,
            'code' => 'E_SAMPLE',
            'reason_key' => 'sample_reason',
            'detail' => 'Sample detail',
        ], $result);
    }
}
