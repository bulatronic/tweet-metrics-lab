<?php

declare(strict_types=1);

namespace App\Like\Infrastructure\DataFixtures;

use App\Shared\Infrastructure\DataFixtures\BatchInsertHelper;
use App\Shared\Infrastructure\DataFixtures\WeightedRandom;
use App\Tweet\Infrastructure\DataFixtures\TweetFixtures;
use App\Tweet\Infrastructure\Persistence\DoctrineTweet;
use App\User\Infrastructure\DataFixtures\UserFixtures;
use App\User\Infrastructure\Persistence\DoctrineUser;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Random\RandomException;
use Symfony\Component\Uid\Uuid;

/**
 * ~30000 likes with power-law over tweets (earlier / "popular" tweets get more weight).
 * Uses DBAL batch inserts; refreshes tweets.likes_count at the end.
 */
final class LikeFixtures extends Fixture implements DependentFixtureInterface
{
    public const int TARGET_LIKES = 30_000;

    public function __construct(
        private readonly BatchInsertHelper $batchInsert,
        private readonly Connection $connection,
    ) {
    }

    /**
     * @throws RandomException
     * @throws Exception
     */
    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create();

        $tweetWeights = [];
        for ($i = 0; $i < TweetFixtures::TWEET_COUNT; ++$i) {
            // Zipf-like: lower index => higher weight (power-law popularity)
            $tweetWeights[$i] = 1 / (($i + 1) ** 1.2);
        }

        /** @var array<string, true> $pairs */
        $pairs = [];
        $rows = [];
        $attempts = 0;
        $maxAttempts = self::TARGET_LIKES * 8;

        while (\count($pairs) < self::TARGET_LIKES && $attempts < $maxAttempts) {
            ++$attempts;

            $tweetIndex = WeightedRandom::pick($tweetWeights);
            $userIndex = random_int(0, UserFixtures::USER_COUNT - 1);

            /** @var DoctrineTweet $tweet */
            $tweet = $this->getReference(TweetFixtures::TWEET_REFERENCE.$tweetIndex, DoctrineTweet::class);
            /** @var DoctrineUser $user */
            $user = $this->getReference(UserFixtures::USER_REFERENCE.$userIndex, DoctrineUser::class);

            $key = $tweet->getId()->toRfc4122().'|'.$user->getId()->toRfc4122();
            if (isset($pairs[$key])) {
                continue;
            }

            $pairs[$key] = true;
            $createdAt = \DateTimeImmutable::createFromMutable(
                $faker->dateTimeBetween($tweet->getCreatedAt()->format('Y-m-d H:i:s'), 'now'),
            );

            $rows[] = [
                Uuid::v7()->toRfc4122(),
                $tweet->getId()->toRfc4122(),
                $user->getId()->toRfc4122(),
                $createdAt->format('Y-m-d H:i:s'),
            ];
        }

        $this->batchInsert->insert(
            'likes',
            ['id', 'tweet_id', 'user_id', 'created_at'],
            $rows,
            1000,
        );

        $this->connection->executeStatement(
            <<<'SQL'
                UPDATE tweets t
                SET likes_count = COALESCE((
                    SELECT COUNT(*) FROM likes l WHERE l.tweet_id = t.id
                ), 0)
                SQL
        );
    }

    public function getDependencies(): array
    {
        return [UserFixtures::class, TweetFixtures::class];
    }
}
