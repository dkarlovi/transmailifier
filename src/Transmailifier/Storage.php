<?php

declare(strict_types=1);

/*
 * This file is part of the transmailifier project.
 *
 * (c) Dalibor Karlović <dalibor@flexolabs.io>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Dkarlovi\Transmailifier;

/**
 * Class Storage.
 */
class Storage
{
    /**
     * @var string
     */
    private $storagePath;

    /**
     * @var \PDO
     */
    private $connection;

    public function __construct(string $storagePath)
    {
        $this->storagePath = $storagePath;
    }

    /**
     * @return Transaction[]
     */
    public function filterProcessedTransactions(iterable $ledger): array
    {
        $unprocessed = [];

        /** @var Transaction $transaction */
        foreach ($ledger as $transaction) {
            if (false === $this->isTransactionProcessed($transaction)) {
                $unprocessed[] = $transaction;
            }
        }

        return $unprocessed;
    }

    /**
     * @param Transaction[] $transactions
     */
    public function markTransactionsProcessed(array $transactions): void
    {
        $connection = $this->getConnection();

        $connection->beginTransaction();

        $statement = $connection->prepare(
            'INSERT INTO `transactions`(`id`, `created_at`, `processed_at`) VALUES(:id, :created_at, DATETIME(\'now\'))'
        );

        foreach ($transactions as $transaction) {
            $statement->execute([
                'id' => $transaction->getIdentifier(),
                'created_at' => $transaction->getTime()->format('c'),
            ]);
        }

        $connection->commit();
    }

    private function isTransactionProcessed(Transaction $transaction): bool
    {
        $statement = $this->getConnection()->prepare(
            'SELECT COUNT(*) AS cnt FROM `transactions` WHERE `id` = :id AND `processed_at` IS NOT NULL'
        );
        $statement->execute([
            'id' => $transaction->getIdentifier(),
        ]);

        return (bool) $statement->fetchColumn();
    }

    private function getConnection(): \PDO
    {
        if (null === $this->connection) {
            shell_exec('mkdir -p '.\dirname($this->storagePath));
            $this->connection = new \PDO('sqlite://'.$this->storagePath);

            $this->ensureSchema($this->connection);
        }

        return $this->connection;
    }

    private function ensureSchema(\PDO $connection): void
    {
        $schema = [
            'CREATE TABLE IF NOT EXISTS `transactions` (`id` CHAR(32) PRIMARY KEY, `created_at` DATETIME NOT NULL, `processed_at` DATETIME)',
        ];

        foreach ($schema as $query) {
            $connection->exec($query);
        }
    }
}
