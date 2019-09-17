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

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Exception;
use Xezilaires\Bridge\PhpSpreadsheet\Spreadsheet;
use Xezilaires\Denormalizer;
use Xezilaires\Metadata\ColumnReference;
use Xezilaires\Metadata\HeaderReference;
use Xezilaires\Metadata\Mapping;
use Xezilaires\Metadata\Reference;
use Xezilaires\SpreadsheetIterator;

/**
 * Class XlsLedger.
 */
class XlsLedger implements Ledger
{
    /**
     * @var Spreadsheet
     */
    private $spreadsheet;

    /**
     * @var array
     */
    private $profile;

    /**
     * @var Denormalizer
     */
    private $denormalizer;

    /**
     * @var \Iterator
     */
    private $iterator;

    /**
     * @var LedgerSummary
     */
    private $summary;

    public function __construct(\SplFileObject $file, array $profile, Denormalizer $denormalizer)
    {
        $this->spreadsheet = new Spreadsheet($file);
        $this->profile = $profile;
        $this->denormalizer = $denormalizer;
    }

    /**
     * {@inheritdoc}
     */
    public function getCurrency(): string
    {
        return $this->profile['config']['currency'];
    }

    /**
     * {@inheritdoc}
     */
    public function getSummary(): LedgerSummary
    {
        if (null === $this->summary) {
            $this->summary = new LedgerSummary($this);
        }

        return $this->summary;
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription(): string
    {
        return $this->profile['config']['validator']['value'];
    }

    /**
     * {@inheritdoc}
     */
    public function getNotificationAddresses(): array
    {
        return $this->profile['mails'];
    }

    /**
     * @return null|float|int|string
     */
    public function fetch(string $coordinates)
    {
        try {
            [$columnName, $rowIndex] = Coordinate::coordinateFromString($coordinates);
        } catch (Exception $exception) {
            return null;
        }
        $row = $this->spreadsheet->getRow((int) $rowIndex);

        return $row[$columnName] ?? null;
    }

    /**
     * {@inheritdoc}
     */
    public function current(): Transaction
    {
        /** @var Transaction $transaction */
        $transaction = $this->getIterator()->current();

        return $transaction;
    }

    /**
     * {@inheritdoc}
     */
    public function next(): void
    {
        $this->getIterator()->next();
    }

    /**
     * {@inheritdoc}
     */
    public function key(): int
    {
        return $this->getIterator()->key();
    }

    /**
     * {@inheritdoc}
     */
    public function valid(): bool
    {
        return $this->getIterator()->valid();
    }

    /**
     * {@inheritdoc}
     */
    public function rewind(): void
    {
        $this->getIterator()->rewind();
    }

    private function getIterator(): \Iterator
    {
        if (null === $this->iterator) {
            $mapping = [
                'time' => $this->buildReference($this->profile['data']['columns']['time']),
            ];

            foreach (['state', 'amount', 'expense', 'income', 'currency', 'note'] as $column) {
                if ( ! ($reference = $this->profile['data']['columns'][$column] ?? false)) {
                    continue;
                }
                $mapping[$column] = $this->buildReference($reference);
            }

            $this->iterator = new SpreadsheetIterator(
                $this->spreadsheet,
                new Mapping(
                    Transaction::class,
                    $mapping,
                    [
                        'start' => $this->profile['data']['rows']['start'] ?? 1,
                        'header' => $this->profile['data']['header'] ?? null,
                        'reverse' => $this->profile['config']['reverse'] ?? false,
                    ]
                ),
                $this->denormalizer,
                [
                    'datetime_format' => $this->profile['data']['columns']['time']['format'] ?? null,
                    'matchers' => $this->profile['config']['matchers'] ?? [],
                ]
            );
        }

        return $this->iterator;
    }

    private function buildReference(array $spec): Reference
    {
        if (isset($spec['column'])) {
            return new ColumnReference($spec['column']);
        }

        if (isset($spec['header'])) {
            return new HeaderReference($spec['header']);
        }
    }
}
