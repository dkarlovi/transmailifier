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
 * Class Transaction.
 */
class Transaction
{
    /**
     * @var float
     */
    private $state;

    /**
     * @var float
     */
    private $amount;

    /**
     * @var string
     */
    private $currency;

    /**
     * @var \DateTimeInterface
     */
    private $time;

    /**
     * @var null|string
     */
    private $category;

    /**
     * @var null|string
     */
    private $payee;

    /**
     * @var null|string
     */
    private $note;

    /**
     * @var bool
     */
    private $uncategorized = false;

    public function getIdentifier(): string
    {
        return md5($this->time->format('Y-m-d').'_'.$this->currency.'_'.$this->amount.'_'.$this->state.'_'.$this->note);
    }

    public function getState(): float
    {
        return $this->state;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function isExpense(): bool
    {
        return $this->amount < 0;
    }

    public function getExpense(): float
    {
        if ($this->isExpense()) {
            return abs($this->amount);
        }

        return 0;
    }

    public function isIncome(): bool
    {
        return $this->amount > 0;
    }

    public function getIncome(): float
    {
        if ($this->isIncome()) {
            return $this->amount;
        }

        return 0;
    }

    public function getTime(): \DateTimeInterface
    {
        return $this->time;
    }

    public function hasCategory(): bool
    {
        return null !== $this->category && false === $this->uncategorized;
    }

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function getPayee(): ?string
    {
        return $this->payee;
    }

    public function getNote(): ?string
    {
        return $this->note;
    }
}
