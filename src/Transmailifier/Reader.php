<?php

declare(strict_types=1);

namespace Dkarlovi\Transmailifier;

/**
 * Class Reader.
 */
class Reader
{
    /**
     * @var array
     */
    private $config;

    /**
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * @param \SplFileObject $file
     * @param string         $profileName
     *
     * @return Ledger
     */
    public function read(\SplFileObject $file, string $profileName): Ledger
    {
        if (false === array_key_exists($profileName, $this->config['profiles'])) {
            $message = sprintf('No such profile "%1$s"', $profileName);
            throw new \RuntimeException($message);
        }

        $profile = $this->config['profiles'][$profileName];
        $ledger = new XlsLedger($file->getRealPath(), $profile);

        if (isset($profile['config']['validator'])) {
            $profileValue = $profile['config']['validator']['value'];
            $fileValue = $ledger->fetch($profile['config']['validator']['cell']);

            if ($profileValue !== $fileValue) {
                $message = sprintf('Profile / file mismatch: profile expects "%1$s", file contains "%2$s".', $profileValue, $fileValue);

                foreach ($this->config['profiles'] as $candidateProfileName => $candidateProfile) {
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
