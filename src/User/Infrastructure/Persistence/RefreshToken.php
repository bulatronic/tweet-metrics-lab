<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Persistence;

use Doctrine\ORM\Mapping as ORM;
use Gesdinet\JWTRefreshTokenBundle\Entity\RefreshToken as BaseRefreshToken;

/**
 * Doctrine persistence model for JWT refresh tokens (gesdinet/jwt-refresh-token-bundle).
 * Not a Domain entity — infrastructure-only storage for Lexik JWT refresh flow.
 */
#[ORM\Entity]
#[ORM\Table(name: 'refresh_tokens')]
class RefreshToken extends BaseRefreshToken
{
}
