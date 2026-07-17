<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\API\Attribute;

/**
 * Hydrates a Request DTO from route attributes and runs Symfony Validator
 * (including api-kit EntityExists).
 */
#[\Attribute(\Attribute::TARGET_PARAMETER)]
final class MapRoutePayload
{
}
