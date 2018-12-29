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

    public function processUnprocessedTransactions(Ledger $ledger): void
    {
        $transactions = $this->filterProcessedTransactions($ledger);

        $this->mailer->notify($transactions, $ledger->getDescription(), $ledger->getNotificationAddresses());

        $this->storage->markTransactionsProcessed($transactions);
    }

    /**
     * @return Transaction[]
     */
    public function filterProcessedTransactions(Ledger $ledger): array
    {
        return $this->storage->filterProcessedTransactions($ledger);
    }
}
