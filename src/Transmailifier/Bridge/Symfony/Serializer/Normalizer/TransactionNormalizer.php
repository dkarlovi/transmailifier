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

        $time = \DateTime::createFromFormat($context['datetime_format'], $data['time']);
        if (false === $time) {
            throw new \RuntimeException(sprintf('Invalid date %1$s given', $data['time']));
        }
        $data['time'] = $time;

        // TODO: temporary until StaticReference
        $data['currency'] = $context['currency'];

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
