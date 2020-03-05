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
use Dkarlovi\Transmailifier\Transaction;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableStyle;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\StyleInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ProcessCommand extends Command
{
    /**
     * @var string
     */
    protected static $defaultName = 'process';

    /**
     * @var Processor
     */
    private $processor;

    public function __construct(Processor $processor)
    {
        parent::__construct('process');

        $this->setDescription('Process a ledger, mark transactions as processed, send a CSV to specified addresses');

        $this->processor = $processor;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->addArgument('profile', InputArgument::REQUIRED, 'Processing profile to use')
            ->addArgument('path', InputArgument::REQUIRED, 'File to processUnprocessedTransactions')
            ->addOption('all', 'a', InputOption::VALUE_NONE, 'Preview all transactions instead just a selected few')
            ->addOption('skip', null, InputOption::VALUE_OPTIONAL, 'Mark all transactions before this date as skipped');
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
        /** @var bool $all */
        $all = $input->getOption('all');
        /** @var string $profile */
        $skip = $input->getOption('skip');

        $ledger = $this->processor->read(new \SplFileObject($path), $profile);

        $style = new SymfonyStyle($input, $output);
        $formatter = new \NumberFormatter('hr', \NumberFormatter::CURRENCY);
        $currencyFormatter = function (float $amount) use ($formatter, $ledger) {
            return $formatter->formatCurrency($amount, $ledger->getCurrency());
        };

        if (null !== $skip) {
            $date = new \DateTime($skip);
            if (false === $date) {
                throw new InvalidArgumentException('Invalid "skip" date');
            }

            $markProcessed = $this->processor->findUnprocessedTransactionsBeforeTime($ledger, $date);
            if ($markProcessed) {
                $this->previewMarkProcessedTransactions($output, $style, $currencyFormatter, $markProcessed, $all ? \count($markProcessed) : 10);

                if (true === $style->confirm(\sprintf('Mark these %1$d transactions as processed?', \count($markProcessed)))) {
                    $this->processor->markTransactionsProcessed($markProcessed);
                }
            }
        }

        // TODO: header with profile name, where to send, etc

        $unprocessedTransactions = $this->processor->filterProcessedTransactions($ledger);
        $unprocessedTransactionsCount = \count($unprocessedTransactions);

        if (0 === $unprocessedTransactionsCount) {
            $style->success('All the transactions have already been processed.');

            return;
        }

        $uncategorizedTransactions = [];
        foreach ($unprocessedTransactions as $unprocessedTransaction) {
            if (false === $unprocessedTransaction->hasCategory()) {
                $uncategorizedTransactions[] = $unprocessedTransaction;
            }
        }

        $this->previewUncategorizedTransactions($output, $style, $currencyFormatter, $uncategorizedTransactions);
        $uncategorizedTransactionsCount = \count($uncategorizedTransactions);

        if (0 === $uncategorizedTransactionsCount || true === $style->confirm(\sprintf('Proceed with %1$d uncategorized transactions?', $uncategorizedTransactionsCount))) {
            $this->previewUnprocessedTransactions($output, $style, $currencyFormatter, $unprocessedTransactions, ($all ? \count($unprocessedTransactions) : 10));

            if (true === $style->confirm(\sprintf('Process these %1$d transactions?', $unprocessedTransactionsCount))) {
                $this->processor->processUnprocessedTransactions($ledger);

                $style->success(\sprintf('Successfully processed %1$d new transactions.', $unprocessedTransactionsCount));
            } else {
                $style->warning('Processing aborted.');
            }
        } else {
            $style->warning('Processing aborted.');
        }
    }

    private function previewUncategorizedTransactions(OutputInterface $output, StyleInterface $style, callable $formatter, array $uncategorizedTransactions): void
    {
        $uncategorizedTransactionsCount = \count($uncategorizedTransactions);

        if ($uncategorizedTransactionsCount > 0) {
            $style->note(\sprintf('Found %1$d uncategorized transactions', $uncategorizedTransactionsCount));

            $this->renderTransactions($output, $formatter, $uncategorizedTransactions);
        }
    }

    private function previewMarkProcessedTransactions(OutputInterface $output, StyleInterface $style, callable $formatter, array $markProcessed, int $markProcessedDisplayLimit = 10): void
    {
        $markProcessedCount = \count($markProcessed);
        $style->note(\sprintf('Found %1$d new transactions to mark as processed', $markProcessedCount));

        $transactionsToDisplay = $markProcessed;
        if ($markProcessedCount > $markProcessedDisplayLimit) {
            $localLimit = (int) floor($markProcessedDisplayLimit / 2);
            $transactionsToDisplay = \array_merge(\array_slice($markProcessed, 0, $localLimit), [null], \array_slice($markProcessed, -$localLimit));

            $style->note(\sprintf('(displaying selected %1$d transactions)', $markProcessedDisplayLimit));
        }

        $this->renderTransactions($output, $formatter, $transactionsToDisplay);
    }

    private function previewUnprocessedTransactions(OutputInterface $output, StyleInterface $style, callable $formatter, array $unprocessedTransactions, int $unprocessedDisplayLimit = 10): void
    {
        $unprocessedTransactionsCount = \count($unprocessedTransactions);
        $style->note(\sprintf('Found %1$d new transactions', $unprocessedTransactionsCount));

        $transactionsToDisplay = $unprocessedTransactions;
        if ($unprocessedTransactionsCount > $unprocessedDisplayLimit) {
            $localLimit = (int) floor($unprocessedDisplayLimit / 2);

            $transactionsToDisplay = \array_merge(\array_slice($unprocessedTransactions, 0, $localLimit), [null], \array_slice($unprocessedTransactions, -$localLimit));

            $style->note(\sprintf('(displaying selected %1$d transactions)', $unprocessedDisplayLimit));
        }

        $this->renderTransactions($output, $formatter, $transactionsToDisplay);
    }

    /**
     * @param Transaction[] $transactions
     */
    private function renderTransactions(OutputInterface $output, callable $formatter, array $transactions): void
    {
        $headers = ['Date', 'Amount', 'Category', 'Payee', 'Note'];
        $separator = array_fill(0, \count($headers), '...');

        $rightAligned = new TableStyle();
        $rightAligned->setPadType(STR_PAD_LEFT);

        $table = new Table($output);
        $table->setStyle('box');
        $table->setHeaders($headers);
        $table->setColumnWidths([10, 15, 15, 20]);
        $table->setColumnStyle(1, $rightAligned);
        $table->setColumnStyle(2, $rightAligned);

        foreach ($transactions as $transaction) {
            if ($transaction === null) {
                $table->addRow($separator);

                continue;
            }

            $table->addRow(
                [
                    $transaction->getTime()->format('Y-m-d'),
                    $formatter($transaction->getAmount()),
                    // $formatter($transaction->getState()),
                    $transaction->getCategory(),
                    $transaction->getPayee(),
                    $this->truncate($transaction->getNote(), 40),
                ]
            );
        }
        $table->render();
    }

    private function truncate(string $string, int $length): string
    {
        if (\mb_strlen($string) > $length) {
            $string = mb_substr($string, 0, $length).'...';
        }

        return $string;
    }
}
