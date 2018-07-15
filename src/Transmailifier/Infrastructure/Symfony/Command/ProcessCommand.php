<?php

declare(strict_types=1);

namespace Dkarlovi\Transmailifier\Infrastructure\Symfony\Command;

use Dkarlovi\Transmailifier\Processor;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableStyle;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\StyleInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Class ProcessCommand.
 */
class ProcessCommand extends Command
{
    /**
     * @var Processor
     */
    private $processor;

    /**
     * @param Processor $processor
     */
    public function __construct(Processor $processor)
    {
        parent::__construct('processUnprocessedTransactions');

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
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $style = new SymfonyStyle($input, $output);
        $ledger = $this->processor->read(
            new \SplFileObject($input->getArgument('path')),
            $input->getArgument('profile')
        );

        // TODO: header with profile name, where to send, etc

        $unprocessedTransactions = $this->processor->filterProcessedTransactions($ledger);
        $unprocessedTransactionsCount = \count($unprocessedTransactions);

        if (0 === $unprocessedTransactionsCount) {
            $style->success('All the transactions have already been processed.');

            return;
        }

        $formatter = new \NumberFormatter('hr', \NumberFormatter::CURRENCY);
        $currencyFormatter = function (float $amount) use ($formatter, $ledger) {
            return $formatter->formatCurrency($amount, $ledger->getCurrency());
        };

        $this->previewUnprocessedTransactions($output, $style, $currencyFormatter, $unprocessedTransactions, 5);

        if (true === $style->confirm(\sprintf('Process these %1$d transactions?', $unprocessedTransactionsCount))) {
            $this->processor->processUnprocessedTransactions($ledger);
        }

        $style->success(\sprintf('Successfully processed %1$d new transactions.', $unprocessedTransactionsCount));
    }

    /**
     * @param OutputInterface $output
     * @param StyleInterface  $style
     * @param callable        $formatter
     * @param array           $unprocessedTransactions
     * @param int             $unprocessedDisplayLimit
     */
    private function previewUnprocessedTransactions(OutputInterface $output, StyleInterface $style, callable $formatter, array $unprocessedTransactions, int $unprocessedDisplayLimit = 10): void
    {
        $unprocessedTransactionsCount = \count($unprocessedTransactions);
        $style->note(\sprintf('Found %1$d new transactions', $unprocessedTransactionsCount));

        $transactionsToDisplay = $unprocessedTransactions;
        if ($unprocessedTransactionsCount > $unprocessedDisplayLimit) {
            $transactionsToDisplay = \array_slice($unprocessedTransactions, -$unprocessedDisplayLimit);

            $style->note(\sprintf('(displaying latest %1$d transactions)', $unprocessedDisplayLimit));
        }

        $rightAligned = new TableStyle();
        $rightAligned->setPadType(STR_PAD_LEFT);

        $table = new Table($output);
        $table->setStyle('box');
        $table->setHeaders(['Date', 'Amount', 'New state', 'Note']);
        $table->setColumnWidths([10, 15, 15, 20]);
        $table->setColumnStyle(1, $rightAligned);
        $table->setColumnStyle(2, $rightAligned);

        /*
         * @var Transaction
         */
        foreach ($transactionsToDisplay as $transaction) {
            $table->addRow([
                $transaction->getTime()->format('Y-m-d'),
                $formatter($transaction->getAmount()),
                $formatter($transaction->getState()),
                $transaction->getNote(),
            ]);
        }
        $table->render();
    }
}
