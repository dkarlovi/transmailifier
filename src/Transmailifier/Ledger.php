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
 * Interface Ledger.
 */
interface Ledger extends \Iterator
{
    public function getCurrency(): string;

    public function getSummary(): LedgerSummary;

    public function getDescription(): string;

    public function getNotificationAddresses(): array;
}
