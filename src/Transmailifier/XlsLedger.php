<?php

declare(strict_types=1);

namespace Dkarlovi\Transmailifier;

use Dkarlovi\Transmailifier\Infrastructure\PhpSpreadsheet\ReverseRowIterator;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Row;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Class XlsLedger.
 */
class XlsLedger implements Ledger
{
    /**
     * @var string
     */
    private $path;

    /**
     * @var array
     */
    private $profile;

    /**
     * @var Worksheet
     */
    private $sheet;

    /**
     * @var \Iterator
     */
    private $iterator;

    /**
     * @var int
     */
    private $key = 0;

    /**
     * @var LedgerSummary
     */
    private $summary;

    /**
     * @param string $path
     * @param array  $profile
     */
    public function __construct(string $path, array $profile)
    {
        $this->path = $path;
        $this->profile = $profile;
    }

    /**
     * @return string
     */
    public function getCurrency(): string
    {
        return $this->profile['data']['columns']['currency'];
    }

    /**
     * @return LedgerSummary
     */
    public function getSummary(): LedgerSummary
    {
        if (null === $this->summary) {
            $this->summary = new LedgerSummary($this);
        }

        return $this->summary;
    }

    public function getDescription(): string
    {
        return $this->profile['config']['validator']['value'];
    }

    /**
     * @return array
     */
    public function getNotificationAddresses(): array
    {
        return $this->profile['mails'];
    }

    /**
     * @param string $coordinates
     *
     * @return null|string|int|float
     */
    public function fetch(string $coordinates)
    {
        $sheet = $this->getSheet();
        try {
            $cell = $sheet->getCell($coordinates);
        } catch (\PhpOffice\PhpSpreadsheet\Exception $exception) {
            throw new \RuntimeException('Value not found at coordinates: '.$exception->getMessage());
        }

        if (null !== $cell) {
            return $cell->getValue();
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function current(): Transaction
    {
        $data = $this->readRow($this->getIterator()->current());

        if (false === ($data['income'] xor $data['expense'])) {
            throw new \RuntimeException('Either expense or income must be set, but not both');
        }

        switch (true) {
            case $data['expense']:
                if ($data['expense'] < 0) {
                    // this is actually income, but written as -10.00HRK expense
                    $amount = abs($data['expense']);
                } else {
                    $amount = -$data['expense'];
                }
                break;
            case $data['income']:
                if ($data['income'] < 0) {
                    // this is actually expense, but written as -10.00HRK income
                    $amount = -abs($data['income']);
                } else {
                    $amount = $data['income'];
                }
                break;
            default:
                throw new \RuntimeException('No income or expense set!');
        }

        $date = \DateTime::createFromFormat($this->profile['data']['columns']['date']['format'], $data['date']);
        if (false === $date) {
            throw new \RuntimeException(sprintf('Invalid date %1$s given', $data['date']));
        }

        return new Transaction($data['state'], $amount, $data['currency'], $date, $data['note']);
    }

    /**
     * {@inheritdoc}
     */
    public function next(): void
    {
        ++$this->key;

        $this->getIterator()->next();
    }

    /**
     * {@inheritdoc}
     */
    public function key(): int
    {
        return $this->key;
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
        $this->key = 0;

        $this->getIterator()->rewind();
    }

    /**
     * @return Worksheet
     */
    private function getSheet(): Worksheet
    {
        if (null === $this->sheet) {
            try {
                /**
                 * @var \PhpOffice\PhpSpreadsheet\Reader\IReader
                 */
                $reader = IOFactory::createReaderForFile($this->path);
                $spreadsheet = $reader->load($this->path);
                $this->sheet = $spreadsheet->getActiveSheet();
            } catch (\PhpOffice\PhpSpreadsheet\Reader\Exception $exception) {
                throw new \RuntimeException('Failed reading the XLS file: '.$exception->getMessage());
            } catch (\PhpOffice\PhpSpreadsheet\Exception $exception) {
                throw new \RuntimeException('Failed opening the XLS file active sheet: '.$exception->getMessage());
            }
        }

        return $this->sheet;
    }

    /**
     * @return \Iterator
     */
    private function getIterator(): \Iterator
    {
        if (null === $this->iterator) {
            $sheet = $this->getSheet();
            $iterator = $sheet->getRowIterator($this->profile['data']['rows']['start']);

            $reverse = (bool) ($this->profile['config']['reverse'] ?? false);
            if (true === $reverse) {
                $iterator = new ReverseRowIterator($iterator, $this->profile['data']['rows']['start'], $sheet->getHighestRow());
            }

            $this->iterator = $iterator;
        }

        return $this->iterator;
    }

    /**
     * @param Row $row
     *
     * @return array
     */
    private function readRow(Row $row): array
    {
        $data = [];
        $rowIndex = $row->getRowIndex();
        foreach ($this->profile['data']['columns'] as $name => $spec) {
            if (\is_array($spec)) {
                $data[$name] = $this->fetch(\sprintf('%1$s%2$d', $this->profile['data']['columns'][$name]['column'], $rowIndex));
            } else {
                $data[$name] = $spec;
            }
        }

        return $data;
    }
}
