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
    private $note;

    /**
     * @param float              $state
     * @param float              $amount
     * @param string             $currency
     * @param \DateTimeInterface $time
     * @param string|null        $note
     */
    public function __construct(float $state, float $amount, string $currency, \DateTimeInterface $time, string $note = null)
    {
        $this->state = $state;
        $this->amount = $amount;
        $this->currency = $currency;
        $this->time = $time;
        $this->note = $note;
    }

    /**
     * @return string
     */
    public function getIdentifier(): string
    {
        return md5($this->time->format('Y-m-d').'_'.$this->currency.'_'.$this->amount.'_'.$this->state.'_'.$this->note);
    }

    /**
     * @return float
     */
    public function getState(): float
    {
        return $this->state;
    }

    /**
     * @return string
     */
    public function getCurrency(): string
    {
        return $this->currency;
    }

    /**
     * @return float
     */
    public function getAmount(): float
    {
        return $this->amount;
    }

    /**
     * @return bool
     */
    public function isExpense(): bool
    {
        return $this->amount < 0;
    }

    /**
     * @return float
     */
    public function getExpense(): float
    {
        if ($this->isExpense()) {
            return abs($this->amount);
        }

        return 0;
    }

    /**
     * @return bool
     */
    public function isIncome(): bool
    {
        return $this->amount > 0;
    }

    /**
     * @return float
     */
    public function getIncome(): float
    {
        if ($this->isIncome()) {
            return $this->amount;
        }

        return 0;
    }

    /**
     * @return \DateTimeInterface
     */
    public function getTime(): \DateTimeInterface
    {
        return $this->time;
    }

    /**
     * @return null|string
     */
    public function getNote(): ?string
    {
        return $this->note;
    }
}
