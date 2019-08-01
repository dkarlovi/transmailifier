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

use Xezilaires\Denormalizer;

/**
 * Class Reader.
 */
class Reader
{
    /**
     * @var array
     */
    private $readerConfig;

    /**
     * @var Denormalizer
     */
    private $denormalizer;

    public function __construct(array $readerConfig, Denormalizer $denormalizer)
    {
        $this->readerConfig = $readerConfig;
        $this->denormalizer = $denormalizer;
    }

    public function read(\SplFileObject $file, string $profileName): Ledger
    {
        if (false === \array_key_exists($profileName, $this->readerConfig['profiles'])) {
            $message = sprintf('No such profile "%1$s"', $profileName);
            throw new \RuntimeException($message);
        }

        $profile = $this->readerConfig['profiles'][$profileName];
        $ledger = new XlsLedger($file, $profile, $this->denormalizer);

        if (isset($profile['config']['validator'])) {
            $profileValue = $profile['config']['validator']['value'];
            $fileValue = $ledger->fetch($profile['config']['validator']['cell']);

            if ($profileValue !== $fileValue) {
                $message = sprintf('Profile / file mismatch: profile expects "%1$s", file contains "%2$s".', $profileValue, $fileValue);

                foreach ($this->readerConfig['profiles'] as $candidateProfileName => $candidateProfile) {
                    if ($candidateProfile['config']['validator']['value'] === $fileValue) {
                        $message .= sprintf("\n\n".'Did you mean to use "%1$s" profile instead?', $candidateProfileName);
                        break;
                    }
                }

                throw new \RuntimeException($message);
            }
        }

        return $ledger;
    }
}
