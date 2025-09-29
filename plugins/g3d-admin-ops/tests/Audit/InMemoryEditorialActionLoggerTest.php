<?php

declare(strict_types=1);

namespace G3D\AdminOps\Tests\Audit;

use G3D\AdminOps\Audit\InMemoryEditorialActionLogger;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class InMemoryEditorialActionLoggerTest extends TestCase
{
    public function testLogActionPersistsEventShape(): void
    {
        $logger = new InMemoryEditorialActionLogger();

        $logger->logAction('user-123', 'publish_snapshot', [
            'what' => 'snapshot:snap-2025',
            'snapshot_id' => 'snap-2025',
            'resultado' => 'ok',
            'latency_ms' => 120,
        ]);

        $events = $logger->getEvents();

        self::assertCount(1, $events);
        $event = $events[0];

        self::assertSame('user-123', $event['actor_id']);
        self::assertSame('publish_snapshot', $event['action']);
        self::assertSame('snapshot:snap-2025', $event['what']);
        self::assertArrayHasKey('occurred_at', $event);
        self::assertNotEmpty($event['occurred_at']);
        self::assertSame('snap-2025', $event['context']['snapshot_id'] ?? null);
        self::assertSame('ok', $event['context']['resultado'] ?? null);
        self::assertSame(120, $event['context']['latency_ms'] ?? null);
    }

    public function testLogActionRequiresWhatKey(): void
    {
        $logger = new InMemoryEditorialActionLogger();

        $this->expectException(InvalidArgumentException::class);
        $logger->logAction('user-123', 'publish_snapshot');
    }
}
