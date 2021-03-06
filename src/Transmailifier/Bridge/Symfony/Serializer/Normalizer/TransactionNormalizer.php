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

namespace Dkarlovi\Transmailifier\Bridge\Symfony\Serializer\Normalizer;

use Dkarlovi\Transmailifier\Transaction;
use Symfony\Component\Serializer\Normalizer\PropertyNormalizer;

/**
 * Class TransactionNormalizer.
 */
class TransactionNormalizer extends PropertyNormalizer
{
    /**
     * @param array       $data
     * @param string      $class
     * @param null|string $format
     *
     * @return object
     */
    public function denormalize($data, $class, $format = null, array $context = [])
    {
        $data = array_map('trim', $data);

        if ( ! ($data['amount'] ?? false)) {
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
            $data['amount'] = $amount;
        } else {
            $data['amount'] = (float) $data['amount'];
        }

        if ($context['datetime_format'] ?? false) {
            $time = \DateTime::createFromFormat($context['datetime_format'], $data['time']);
        } else {
            $time = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($data['time']);
        }

        if (false === $time) {
            throw new \RuntimeException(sprintf('Invalid date %1$s given', $data['time']));
        }
        $data['time'] = $time->format(\DateTimeInterface::RFC3339);

        $matchers = $context['matchers'] ?? [];
        foreach ($matchers as $matcher) {
            if (0 !== preg_match($matcher['match'], $data['note'])) {
                $data = array_replace($data, $matcher['values']);
                break;
            }
        }

        return parent::denormalize($data, $class, $format, $context);
    }

    /**
     * @param array       $data
     * @param string      $type
     * @param null|string $format
     */
    public function supportsDenormalization($data, $type, $format = null): bool
    {
        return Transaction::class === $type;
    }
}
