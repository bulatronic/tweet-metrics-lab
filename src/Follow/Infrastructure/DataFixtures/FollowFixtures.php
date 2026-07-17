<?php

declare(strict_types=1);

namespace App\Follow\Infrastructure\DataFixtures;

use App\Shared\Infrastructure\DataFixtures\BatchInsertHelper;
use App\User\Infrastructure\DataFixtures\UserFixtures;
use App\User\Infrastructure\Persistence\DoctrineUser;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\DBAL\Exception;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Random\RandomException;
use Symfony\Component\Uid\Uuid;

/**
 * ~20000 follows with power-law: influencers get many followers, regular users few.
 * Uses DBAL batch inserts (no per-row ORM flush).
 */
final class FollowFixtures extends Fixture implements DependentFixtureInterface
{
    public const int TARGET_FOLLOWS = 20_000;

    public function __construct(
        private readonly BatchInsertHelper $batchInsert,
    ) {
    }

    /**
     * @throws RandomException
     * @throws Exception
     */
    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create();
        /** @var array<string, true> $pairs */
        $pairs = [];
        $rows = [];

        $addFollow = function (DoctrineUser $follower, DoctrineUser $followee) use (&$pairs, &$rows, $faker): bool {
            if ($follower->getId()->toRfc4122() === $followee->getId()->toRfc4122()) {
                return false;
            }

            $key = $follower->getId()->toRfc4122().'|'.$followee->getId()->toRfc4122();
            if (isset($pairs[$key])) {
                return false;
            }

            $pairs[$key] = true;
            $createdAt = \DateTimeImmutable::createFromMutable(
                $faker->dateTimeBetween('-1 year', 'now'),
            );
            $rows[] = [
                Uuid::v7()->toRfc4122(),
                $follower->getId()->toRfc4122(),
                $followee->getId()->toRfc4122(),
                $createdAt->format('Y-m-d H:i:s'),
            ];

            return true;
        };

        // Influencers: power-law inbound follows
        for ($i = 0; $i < UserFixtures::INFLUENCER_COUNT; ++$i) {
            if (\count($pairs) >= self::TARGET_FOLLOWS) {
                break;
            }

            /** @var DoctrineUser $influencer */
            $influencer = $this->getReference(UserFixtures::INFLUENCER_REFERENCE.$i, DoctrineUser::class);
            $target = min(
                random_int(100, 5000),
                self::TARGET_FOLLOWS - \count($pairs),
                UserFixtures::USER_COUNT - 1,
            );

            $attempts = 0;
            $added = 0;
            while ($added < $target && $attempts < $target * 5) {
                ++$attempts;
                $followerIndex = random_int(0, UserFixtures::USER_COUNT - 1);
                /** @var DoctrineUser $follower */
                $follower = $this->getReference(UserFixtures::USER_REFERENCE.$followerIndex, DoctrineUser::class);
                if ($addFollow($follower, $influencer)) {
                    ++$added;
                }
            }
        }

        // Regular users: small outbound follow counts
        for ($userIndex = UserFixtures::INFLUENCER_COUNT; $userIndex < UserFixtures::USER_COUNT; ++$userIndex) {
            if (\count($pairs) >= self::TARGET_FOLLOWS) {
                break;
            }

            /** @var DoctrineUser $follower */
            $follower = $this->getReference(UserFixtures::USER_REFERENCE.$userIndex, DoctrineUser::class);
            $target = min(
                random_int(0, 50),
                self::TARGET_FOLLOWS - \count($pairs),
            );

            $attempts = 0;
            $added = 0;
            while ($added < $target && $attempts < max(10, $target * 5)) {
                ++$attempts;
                $followeeIndex = random_int(0, UserFixtures::USER_COUNT - 1);
                /** @var DoctrineUser $followee */
                $followee = $this->getReference(UserFixtures::USER_REFERENCE.$followeeIndex, DoctrineUser::class);
                if ($addFollow($follower, $followee)) {
                    ++$added;
                }
            }
        }

        // Top up to TARGET: with only 400 users an influencer can have at most 399
        // unique followers, so the power-law phase alone stays below 20k — fill the
        // remainder with random unique pairs biased toward following influencers.
        $fillAttempts = 0;
        $maxFillAttempts = self::TARGET_FOLLOWS * 10;
        while (\count($pairs) < self::TARGET_FOLLOWS && $fillAttempts < $maxFillAttempts) {
            ++$fillAttempts;
            $followerIndex = random_int(0, UserFixtures::USER_COUNT - 1);
            $followeeIndex = random_int(0, 100) < 70
                ? random_int(0, UserFixtures::INFLUENCER_COUNT - 1)
                : random_int(0, UserFixtures::USER_COUNT - 1);

            /** @var DoctrineUser $follower */
            $follower = $this->getReference(UserFixtures::USER_REFERENCE.$followerIndex, DoctrineUser::class);
            /** @var DoctrineUser $followee */
            $followee = $this->getReference(UserFixtures::USER_REFERENCE.$followeeIndex, DoctrineUser::class);
            $addFollow($follower, $followee);
        }

        $this->batchInsert->insert(
            'follows',
            ['id', 'follower_id', 'followee_id', 'created_at'],
            $rows,
            1000,
        );
    }

    public function getDependencies(): array
    {
        return [UserFixtures::class];
    }
}
