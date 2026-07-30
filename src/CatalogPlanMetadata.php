<?php

declare(strict_types=1);

namespace OrcaRail;

final class CatalogPlanMetadata
{
    /** @param array<string, mixed>|OrcaRailObject|null $metadata */
    public static function parse(array|OrcaRailObject|null $metadata): ?OrcaRailObject
    {
        if ($metadata === null) {
            return null;
        }

        return $metadata instanceof OrcaRailObject ? $metadata : new OrcaRailObject($metadata);
    }
}
