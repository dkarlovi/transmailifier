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

namespace Dkarlovi\Transmailifier\Bridge\Symfony\Command;

use Dkarlovi\Transmailifier\Processor;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\TableCell;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class FindInitialAmountCommand extends Command
{
    /**
     * @var string
     */
    protected static $defaultName = 'find-initial-amount';

    /**
     * @var Processor
     */
    private $processor;

    public function __construct(Processor $processor)
    {
        parent::__construct('find-initial-amount');

        $this->setDescription('Find the initial amount to set the account to if starting from this import');

        $this->processor = $processor;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->addArgument('profile', InputArgument::REQUIRED, 'Processing profile to use')
            ->addArgument('path', InputArgument::REQUIRED, 'File to processUnprocessedTransactions');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        /** @var string $path */
        $path = $input->getArgument('path');
        /** @var string $profile */
        $profile = $input->getArgument('profile');
        $ledger = $this->processor->read(new \SplFileObject($path), $profile);
        $summary = $ledger->getSummary();

        $formatter = new \NumberFormatter(setlocale(LC_MONETARY, '0'), \NumberFormatter::CURRENCY);

        $initialAmount = $summary->getInitialAmount();
        $finalAmount = $summary->getFinalAmount();

        // validate that the initial amount gets changed to the final amount
        $incrementedInitialAmount = $initialAmount + $summary->getTotalAmount();
        if (abs($incrementedInitialAmount - $finalAmount) > 0.000001) {
            throw new \LogicException(
                \sprintf(
                    'Initial amount %1$s incremented by difference %2$s expected to be %3$s, but %4$s received',
                    $formatter->formatCurrency($initialAmount, $summary->getCurrency()),
                    $formatter->formatCurrency($summary->getTotalAmount(), $summary->getCurrency()),
                    $formatter->formatCurrency($incrementedInitialAmount, $summary->getCurrency()),
                    $formatter->formatCurrency($finalAmount, $summary->getCurrency())
                )
            );
        }

        $style = new SymfonyStyle($input, $output);
        $style->section('Overall');
        $style->table([], [
            $this->row('Currency', $summary->getCurrency()),
            $this->row('Transactions', $summary->getTransactionCount()),
            $this->row('From - to', $summary->getInitialTransaction()->getTime()->format('Y-m-d').' - '.$summary->getFinalTransaction()->getTime()->format('Y-m-d')),
            $this->row('Initial amount', \sprintf('<error>%1$s</error>', $formatter->formatCurrency($initialAmount, $summary->getCurrency()))),
            $this->row('Final amount', $formatter->formatCurrency($finalAmount, $summary->getCurrency())),
            $this->row('Amount difference', $formatter->formatCurrency($summary->getTotalAmount(), $summary->getCurrency())),
            $this->row('Total amount', $formatter->formatCurrency($summary->getIncomeAmount() + $summary->getExpenseAmount(), $summary->getCurrency())),
        ]);

        $style->section('Income');
        $style->table([], [
            $this->row('Transactions', $summary->getIncomeTransactionCount()),
            $this->row('Total amount', $formatter->formatCurrency($summary->getIncomeAmount(), $summary->getCurrency())),
        ]);

        $style->section('Expense');
        $style->table([], [
            $this->row('Transactions', $summary->getExpenseTransactionCount()),
            $this->row('Total amount', $formatter->formatCurrency($summary->getExpenseAmount(), $summary->getCurrency())),
        ]);
    }

    /**
     * @param int|string $value
     */
    private function row(string $label, $value): array
    {
        return [
            \sprintf('<info>%1$s</info>', $label),
            $this->span($value),
        ];
    }

    /**
     * @param array|int|string $values
     *
     * @return string|TableCell
     */
    private function span($values)
    {
        if (false === \is_array($values)) {
            return new TableCell((string) $values);
        }

        return new TableCell(\implode("\n", $values), ['rowspan' => \count($values)]);
    }
}
