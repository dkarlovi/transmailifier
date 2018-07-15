<?php

declare(strict_types=1);

namespace Dkarlovi\Transmailifier;

use Doctrine\DBAL\Connection;

/**
 * Class Storage.
 */
class Storage
{
    /**
     * @var string
     */
    private $path;

    /**
     * @var Connection
     */
    private $connection;

    /**
     * @param string $path
     */
    public function __construct(string $path)
    {
        $this->path = $path;
    }

    /**
     * @param Ledger $ledger
     *
     * @return array
     */
    public function filterProcessedTransactions(Ledger $ledger): array
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

    /**
     * @param Transaction $transaction
     *
     * @return bool
     */
    private function isTransactionProcessed(Transaction $transaction): bool
    {
        $result = $this->getConnection()->executeQuery(
            'SELECT COUNT(*) AS cnt FROM `transactions` WHERE `id` = :id AND `processed_at` IS NOT NULL',
            [
                'id' => $transaction->getIdentifier(),
            ]
        );

        return (bool) $result->fetchColumn();
    }

    /**
     * @throws \Doctrine\DBAL\DBALException
     *
     * @return Connection
     */
    private function getConnection(): Connection
    {
        if (null === $this->connection) {
            shell_exec('mkdir -p '.\dirname($this->path));
            $this->connection = \Doctrine\DBAL\DriverManager::getConnection(['url' => 'sqlite:///'.$this->path]);

            $this->ensureSchema($this->connection);
        }

        return $this->connection;
    }

    /**
     * @param Connection $connection
     *
     * @throws \Doctrine\DBAL\DBALException
     */
    private function ensureSchema(Connection $connection): void
    {
        $schema = [
            'CREATE TABLE IF NOT EXISTS `transactions` (`id` CHAR(32) PRIMARY KEY, `created_at` DATETIME NOT NULL, `processed_at` DATETIME)',
        ];

        foreach ($schema as $query) {
            $connection->executeQuery($query);
        }
    }
}
