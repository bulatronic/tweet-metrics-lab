<?php

declare(strict_types=1);

namespace App\User\Infrastructure\DataFixtures;

use App\Shared\Infrastructure\DataFixtures\BatchInsertHelper;
use App\User\Domain\Service\PasswordHasherInterface;
use App\User\Infrastructure\Persistence\DoctrineUser;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Exception;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Symfony\Component\Uid\Uuid;

final class UserFixtures extends Fixture
{
    public const string USER_REFERENCE = 'user_';
    public const string INFLUENCER_REFERENCE = 'influencer_';
    public const int USER_COUNT = 400;
    public const int INFLUENCER_COUNT = 10;
    public const string DEFAULT_PASSWORD = 'password';

    /**
     * Influencers kept for local fixture bookkeeping; other modules must use
     * {@see INFLUENCER_REFERENCE} via getReference(), not this collection.
     *
     * @var ArrayCollection<int, DoctrineUser>
     */
    public ArrayCollection $influencers;

    public function __construct(
        private readonly BatchInsertHelper $batchInsert,
        private readonly PasswordHasherInterface $passwordHasher,
    ) {
        $this->influencers = new ArrayCollection();
    }

    /**
     * @throws Exception
     */
    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create();
        $passwordHash = $this->passwordHasher->hash(self::DEFAULT_PASSWORD)->toString();
        $rows = [];

        for ($i = 0; $i < self::USER_COUNT; ++$i) {
            $id = Uuid::v7();
            $username = sprintf('user_%d', $i);
            $email = sprintf('user_%d@example.com', $i);
            $createdAt = \DateTimeImmutable::createFromMutable(
                $faker->dateTimeBetween('-1 year', '-1 day'),
            );

            $user = new DoctrineUser(
                $id,
                $email,
                $passwordHash,
                $username,
                $createdAt,
            );

            $this->addReference(self::USER_REFERENCE.$i, $user);

            if ($i < self::INFLUENCER_COUNT) {
                $this->addReference(self::INFLUENCER_REFERENCE.$i, $user);
                $this->influencers->add($user);
            }

            $rows[] = [
                $id->toRfc4122(),
                $email,
                $passwordHash,
                $username,
                $createdAt->format('Y-m-d H:i:s'),
            ];
        }

        $this->batchInsert->insert(
            'users',
            ['id', 'email', 'password_hash', 'username', 'created_at'],
            $rows,
        );
    }
}
