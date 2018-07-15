<?php

declare(strict_types=1);

namespace Dkarlovi\Transmailifier\Infrastructure\PhpSpreadsheet;

use PhpOffice\PhpSpreadsheet\Worksheet\RowIterator;

/**
 * Class ReverseRowIterator.
 */
class ReverseRowIterator implements \Iterator
{
    /**
     * @var RowIterator
     */
    private $iterator;

    /**
     * @var int
     */
    private $startRow;

    /**
     * @var int
     */
    private $endRow;

    /**
     * @var int
     */
    private $key;

    /**
     * @param RowIterator $iterator
     * @param int         $startRow
     * @param int         $endRow
     */
    public function __construct(RowIterator $iterator, int $startRow, int $endRow)
    {
        $this->iterator = $iterator;
        $this->startRow = $startRow;
        $this->endRow = $endRow;

        $this->rewind();
    }

    /**
     * @return mixed
     */
    public function current()
    {
        return $this->iterator->current();
    }

    /**
     * {@inheritdoc}
     */
    public function next(): void
    {
        ++$this->key;

        $this->iterator->prev();
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
        return $this->iterator->key() >= $this->startRow;
    }

    /**
     * {@inheritdoc}
     */
    public function rewind(): void
    {
        $this->key = 0;

        $this->iterator->seek($this->endRow);
    }
}
