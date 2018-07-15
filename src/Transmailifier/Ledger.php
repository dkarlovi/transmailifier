<?php

declare(strict_types=1);

namespace Dkarlovi\Transmailifier;

/**
 * Interface Ledger.
 */
interface Ledger extends \Iterator
{
    /**
     * @return string
     */
    public function getCurrency(): string;

    /**
     * @return LedgerSummary
     */
    public function getSummary(): LedgerSummary;

    /**
     * @return string
     */
    public function getDescription(): string;

    /**
     * @return array
     */
    public function getNotificationAddresses(): array;
}
