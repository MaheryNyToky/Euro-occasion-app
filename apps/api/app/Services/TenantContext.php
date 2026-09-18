<?php

namespace App\Services;

class TenantContext
{
    private static ?string $currentTenantId = null;

    public static function setTenantId(?string $tenantId): void
    {
        self::$currentTenantId = $tenantId;
    }

    public static function getTenantId(): ?string
    {
        return self::$currentTenantId;
    }

    public static function clear(): void
    {
        self::$currentTenantId = null;
    }
}
