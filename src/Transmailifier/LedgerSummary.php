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
 * Class LedgerSummary.
 */
class LedgerSummary
{
    /**
     * @var string
     */
    private $currency;

    /**
     * @var Transaction[]
     */
    private $incomeTransactions = [];

    /**
     * @var Transaction[]
     */
    private $expenseTransactions = [];

    /**
     * @var Transaction
     */
    private $initialTransaction;

    /**
     * @var Transaction
     */
    private $finalTransaction;

    public function __construct(Ledger $ledger)
    {
        $this->summarizeLedger($ledger);
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function getInitialAmount(): float
    {
        $initialTransaction = $this->getInitialTransaction();
        $initialAmount = $initialTransaction->getState();
        if ($initialTransaction->isExpense()) {
            $initialAmount += $initialTransaction->getExpense();
        } else {
            $initialAmount -= $initialTransaction->getIncome();
        }

        return $initialAmount;
    }

    public function getFinalAmount(): float
    {
        return $this->getFinalTransaction()->getState();
    }

    public function getInitialTransaction(): Transaction
    {
        return $this->initialTransaction;
    }

    public function getFinalTransaction(): Transaction
    {
        return $this->finalTransaction;
    }

    public function getTotalAmount(): float
    {
        return $this->getIncomeAmount() - $this->getExpenseAmount();
    }

    public function getIncomeAmount(): float
    {
        $amount = 0;

        foreach ($this->incomeTransactions as $transaction) {
            $amount += $transaction->getIncome();
        }

        return $amount;
    }

    public function getExpenseAmount(): float
    {
        $amount = 0;

        foreach ($this->expenseTransactions as $transaction) {
            $amount += $transaction->getExpense();
        }

        return $amount;
    }

    public function getTransactionCount(): int
    {
        return $this->getIncomeTransactionCount() + $this->getExpenseTransactionCount();
    }

    public function getIncomeTransactionCount(): int
    {
        return \count($this->incomeTransactions);
    }

    public function getExpenseTransactionCount(): int
    {
        return \count($this->expenseTransactions);
    }

    private function summarizeLedger(Ledger $ledger): void
    {
        $this->currency = $ledger->getCurrency();

        foreach ($ledger as $transaction) {
            if (null === $this->initialTransaction || $this->initialTransaction->getTime() > $transaction->getTime()) {
                $this->initialTransaction = $transaction;
            }

            if (null === $this->finalTransaction || $this->finalTransaction->getTime() <= $transaction->getTime()) {
                $this->finalTransaction = $transaction;
            }

            if ($transaction->isIncome()) {
                $this->incomeTransactions[] = $transaction;
            } elseif ($transaction->isExpense()) {
                $this->expenseTransactions[] = $transaction;
            } else {
                throw new \RuntimeException('Transaction neither an income or an expense');
            }
        }
    }
}
