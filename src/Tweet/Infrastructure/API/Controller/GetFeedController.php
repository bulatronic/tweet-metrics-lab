<?php

declare(strict_types=1);

namespace App\Tweet\Infrastructure\API\Controller;

use ApiKit\Controller\AbstractApiController;
use App\Shared\Infrastructure\Messenger\HandleTrait;
use App\Tweet\Application\DTO\FeedPageDTO;
use App\Tweet\Application\Query\GetFeedQuery;
use App\Tweet\Infrastructure\API\Request\CursorQueryDto;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

final class GetFeedController extends AbstractApiController
{
    use HandleTrait;

    public function __construct(
        #[Autowire(service: 'query.bus')]
        private readonly MessageBusInterface $queryBus,
    ) {
    }

    #[Route('/api/feed', name: 'api_feed', methods: ['GET'])]
    public function __invoke(#[MapQueryString] CursorQueryDto $query): JsonResponse
    {
        /** @var FeedPageDTO $page */
        $page = $this->handle($this->queryBus, new GetFeedQuery(
            cursor: $query->cursor,
            limit: $query->limit,
        ));

        return $this->respondSuccess($page);
    }
}
