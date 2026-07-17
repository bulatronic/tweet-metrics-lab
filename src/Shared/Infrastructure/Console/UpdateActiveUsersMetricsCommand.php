<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Console;

use App\Shared\Domain\MetricsRegistryInterface;
use App\Shared\Infrastructure\Metrics\ActiveUsersTracker;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Refreshes active_users_5m gauge. Schedule every ~60s (supervisor/cron).
 */
#[AsCommand(
    name: 'app:metrics:active-users',
    description: 'Update active_users_5m Prometheus gauge from Redis activity set',
)]
final class UpdateActiveUsersMetricsCommand extends Command
{
    public function __construct(
        private readonly ActiveUsersTracker $activeUsersTracker,
        private readonly MetricsRegistryInterface $metricsRegistry,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $count = $this->activeUsersTracker->countActive();
        $this->metricsRegistry->setActiveUsers5m($count);
        $output->writeln(sprintf('active_users_5m=%d', $count));

        return Command::SUCCESS;
    }
}
