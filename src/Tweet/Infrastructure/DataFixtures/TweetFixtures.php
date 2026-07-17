<?php

declare(strict_types=1);

namespace App\Tweet\Infrastructure\DataFixtures;

use App\Shared\Infrastructure\DataFixtures\BatchInsertHelper;
use App\Shared\Infrastructure\DataFixtures\WeightedRandom;
use App\Tweet\Infrastructure\Persistence\DoctrineTweet;
use App\User\Infrastructure\DataFixtures\UserFixtures;
use App\User\Infrastructure\Persistence\DoctrineUser;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\DBAL\Exception;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Symfony\Component\Uid\Uuid;

/**
 * 4000 tweets with uneven authorship: ~60% of tweets from ~20% of users.
 */
final class TweetFixtures extends Fixture implements DependentFixtureInterface
{
    public const string TWEET_REFERENCE = 'tweet_';
    public const int TWEET_COUNT = 4000;

    /** Top 20% of users get higher authorship weight (includes influencers 0..9). */
    private const int HEAVY_AUTHOR_COUNT = 80;
    private const int HEAVY_WEIGHT = 6;
    private const int LIGHT_WEIGHT = 1;

    public function __construct(
        private readonly BatchInsertHelper $batchInsert,
    ) {
    }

    /**
     * @throws Exception
     */
    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create();
        $authorWeights = [];
        for ($i = 0; $i < UserFixtures::USER_COUNT; ++$i) {
            $authorWeights[$i] = $i < self::HEAVY_AUTHOR_COUNT ? self::HEAVY_WEIGHT : self::LIGHT_WEIGHT;
        }

        $rows = [];

        for ($i = 0; $i < self::TWEET_COUNT; ++$i) {
            $authorIndex = WeightedRandom::pick($authorWeights);
            /** @var DoctrineUser $author */
            $author = $this->getReference(UserFixtures::USER_REFERENCE.$authorIndex, DoctrineUser::class);

            $id = Uuid::v7();
            $text = mb_substr($faker->realText(200), 0, 280);
            $createdAt = \DateTimeImmutable::createFromMutable(
                $faker->dateTimeBetween('-6 months', 'now'),
            );

            $tweet = new DoctrineTweet(
                $id,
                $author->getId(),
                $text,
                $createdAt,
                0,
            );
            $this->addReference(self::TWEET_REFERENCE.$i, $tweet);

            $rows[] = [
                $id->toRfc4122(),
                $author->getId()->toRfc4122(),
                $text,
                $createdAt->format('Y-m-d H:i:s'),
                0,
            ];
        }

        $this->batchInsert->insert(
            'tweets',
            ['id', 'author_id', 'text', 'created_at', 'likes_count'],
            $rows,
        );
    }

    public function getDependencies(): array
    {
        return [UserFixtures::class];
    }
}
