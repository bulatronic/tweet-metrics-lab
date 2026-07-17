<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\API\ValueResolver;

use App\Shared\Infrastructure\API\Attribute\MapRoutePayload;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final readonly class MapRoutePayloadValueResolver implements ValueResolverInterface
{
    public function __construct(
        private ValidatorInterface $validator,
    ) {
    }

    /**
     * @throws \ReflectionException
     */
    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        if (!$argument->getAttributesOfType(MapRoutePayload::class, ArgumentMetadata::IS_INSTANCEOF)) {
            return [];
        }

        $type = $argument->getType();
        if (null === $type || !class_exists($type)) {
            return [];
        }

        $reflection = new \ReflectionClass($type);
        $constructor = $reflection->getConstructor();
        if (null === $constructor) {
            throw new \LogicException(sprintf('Route payload DTO "%s" must have a constructor.', $type));
        }

        $args = [];
        foreach ($constructor->getParameters() as $parameter) {
            $name = $parameter->getName();
            if (!$request->attributes->has($name)) {
                if ($parameter->isDefaultValueAvailable()) {
                    $args[] = $parameter->getDefaultValue();
                    continue;
                }

                throw new \LogicException(sprintf('Route attribute "%s" required by "%s" is missing.', $name, $type));
            }

            $args[] = $request->attributes->get($name);
        }

        $dto = $reflection->newInstanceArgs($args);
        $violations = $this->validator->validate($dto);
        if ($violations->count() > 0) {
            throw new HttpException(422, implode("\n", array_map(static fn ($v) => (string) $v->getMessage(), iterator_to_array($violations))), new ValidationFailedException($dto, $violations));
        }

        return [$dto];
    }
}
