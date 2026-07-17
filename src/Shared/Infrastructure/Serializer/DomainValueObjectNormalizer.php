<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Serializer;

use App\Shared\Domain\ValueObject\FromStringableValueObject;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Normalizes Domain value objects that implement FromStringableValueObject.
 */
#[AutoconfigureTag('serializer.normalizer', ['priority' => 100])]
#[AutoconfigureTag('serializer.denormalizer', ['priority' => 100])]
final class DomainValueObjectNormalizer implements NormalizerInterface, DenormalizerInterface
{
    public function normalize(mixed $data, ?string $format = null, array $context = []): \ArrayObject|array|string|int|float|bool|null
    {
        if (!$data instanceof FromStringableValueObject) {
            throw new \InvalidArgumentException(sprintf('Cannot normalize "%s".', get_debug_type($data)));
        }

        return $data->toString();
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof FromStringableValueObject;
    }

    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): mixed
    {
        if (!is_string($data) || !is_a($type, FromStringableValueObject::class, true)) {
            throw new \InvalidArgumentException(sprintf('Cannot denormalize to "%s".', $type));
        }

        /* @var class-string<FromStringableValueObject> $type */
        return $type::fromString($data);
    }

    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        return is_string($data) && is_a($type, FromStringableValueObject::class, true);
    }

    /**
     * @return array<class-string, true>
     */
    public function getSupportedTypes(?string $format): array
    {
        return [FromStringableValueObject::class => true];
    }
}
