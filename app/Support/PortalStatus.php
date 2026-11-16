<?php

namespace App\Support;

/**
 * The portal's listing states, in the vocabulary the DGCP API used and the
 * app already stores, so a bid's status means the same whichever source
 * wrote it.
 */
final class PortalStatus
{
    private const MAP = [
        'Published' => 'Proceso publicado',
        'ClosedForReplies' => 'Proceso con etapa cerrada',
        'Canceled' => 'Cancelado',
        'Awarded' => 'Proceso adjudicado y celebrado',
        'NonAwarded' => 'Proceso desierto',
        'Suspended' => 'Suspendido',
    ];

    public static function toApp(?string $portalStatus): ?string
    {
        return self::MAP[$portalStatus ?? ''] ?? null;
    }

    public static function isOpenForOffers(?string $portalStatus): bool
    {
        return $portalStatus === null || $portalStatus === 'Published';
    }
}
