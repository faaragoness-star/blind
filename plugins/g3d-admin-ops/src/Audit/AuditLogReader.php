<?php

declare(strict_types=1);

namespace G3D\AdminOps\Audit;

/**
 * @psalm-type AuditEvent=array{
 *     actor_id:string,
 *     action:string,
 *     what:string,
 *     occurred_at:string,
 *     context:array<string,mixed>
 * }
 */
interface AuditLogReader
{
    /**
     * @return list<AuditEvent>
     */
    public function getEvents(): array;
}
