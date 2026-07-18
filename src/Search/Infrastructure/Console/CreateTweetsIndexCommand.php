<?php

declare(strict_types=1);

namespace App\Search\Infrastructure\Console;

use App\Search\Infrastructure\Elasticsearch\TweetsIndex;
use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\Exception\ClientResponseException;
use Elastic\Elasticsearch\Exception\MissingParameterException;
use Elastic\Elasticsearch\Exception\ServerResponseException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:elasticsearch:create-index',
    description: 'Create the tweets Elasticsearch index with a minimal mapping',
)]
final class CreateTweetsIndexCommand extends Command
{
    public function __construct(
        private readonly Client $elasticsearch,
    ) {
        parent::__construct();
    }

    /**
     * @throws ClientResponseException
     * @throws ServerResponseException
     * @throws MissingParameterException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $exists = $this->elasticsearch->indices()->exists(['index' => TweetsIndex::NAME]);
        if ($exists->asBool()) {
            $io->warning(sprintf('Index "%s" already exists — nothing to do.', TweetsIndex::NAME));

            return Command::SUCCESS;
        }

        $this->elasticsearch->indices()->create([
            'index' => TweetsIndex::NAME,
            'body' => TweetsIndex::mapping(),
        ]);

        $io->success(sprintf('Index "%s" created.', TweetsIndex::NAME));

        return Command::SUCCESS;
    }
}
