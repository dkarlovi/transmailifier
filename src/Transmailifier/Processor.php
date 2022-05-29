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
 * Class Processor.
 */
class Processor
{
    /**
     * @var Reader
     */
    private $reader;

    /**
     * @var Storage
     */
    private $storage;

    /**
     * @var Mailer
     */
    private $mailer;

    public function __construct(Reader $reader, Storage $storage, Mailer $mailer)
    {
        $this->reader = $reader;
        $this->storage = $storage;
        $this->mailer = $mailer;
    }

    public function read(\SplFileObject $file, string $profile): Ledger
    {
        return $this->reader->read($file, $profile);
    }

    /**
     * @return Transaction[]
     */
    public function findUnprocessedTransactionsBeforeTime(Ledger $ledger, \DateTimeInterface $beforeDatetime): array
    {
        $transactions = [];

        /** @var Transaction $transaction */
        foreach ($ledger as $transaction) {
            if ($transaction->isBefore($beforeDatetime)) {
                $transactions[] = $transaction;
            }
        }

        return $this->storage->filterProcessedTransactions($transactions);
    }

    /**
     * @param Transaction[] $transactions
     */
    public function markTransactionsProcessed(array $transactions): void
    {
        $this->storage->markTransactionsProcessed($transactions, true);
    }

    public function processUnprocessedTransactions(Ledger $ledger, bool $reprocess): void
    {
        $transactions = $this->filterProcessedTransactions($ledger, $reprocess);

        // 1. try to mark transactions as processed
        $connection = $this->storage->markTransactionsProcessed($transactions);
        // 2. try to notify via mailer
        $this->mailer->notify($transactions, $ledger->getDescription(), $ledger->getNotificationAddresses());
        // 3. if *both* succeeded, flush into the database
        $this->storage->flush($connection);
    }

    /**
     * @return Transaction[]
     */
    public function filterProcessedTransactions(Ledger $ledger, bool $reprocess = false): array
    {
        return $this->storage->filterProcessedTransactions($ledger, $reprocess);
    }
}
