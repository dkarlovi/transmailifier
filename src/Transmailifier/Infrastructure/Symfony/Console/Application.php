<?php

declare(strict_types=1);

namespace Dkarlovi\Transmailifier\Infrastructure\Symfony\Console;

use Dkarlovi\Transmailifier\Infrastructure\Symfony\Command\FindInitialAmountCommand;
use Dkarlovi\Transmailifier\Infrastructure\Symfony\Command\ProcessCommand;
use Dkarlovi\Transmailifier\Mailer;
use Dkarlovi\Transmailifier\Processor;
use Dkarlovi\Transmailifier\Reader;
use Dkarlovi\Transmailifier\Storage;
use Symfony\Component\Console\Application as BaseApplication;
use Symfony\Component\Serializer\Encoder\CsvEncoder;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Yaml\Yaml;

/**
 * Class Application.
 */
class Application extends BaseApplication
{
    public function __construct()
    {
        parent::__construct('Transmailifier');
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Symfony\Component\Console\Exception\LogicException
     */
    protected function getDefaultCommands(): array
    {
        // TODO: temporary, move to a proper path
        // TODO: validate config
        $config = Yaml::parseFile('config.yaml');

        $transporter = new \Swift_SmtpTransport(
            $config['mailer']['host'],
            $config['mailer']['port'],
            $config['mailer']['security']
        );
        $transporter
            ->setAuthMode($config['mailer']['auth']['mode'])
            ->setUsername($config['mailer']['auth']['username'])
            ->setPassword($config['mailer']['auth']['password']);

        // no DIC available for Symfony CLI commands :(
        $processor = new Processor(
            new Reader($config['reader']),
            new Storage(shell_exec('echo -n ${HOME}/.config/transmailifier/transactions.sqlite')),
            new Mailer(
                $config['mailer']['sender_address'],
                new \Swift_Mailer($transporter),
                new Serializer(
                    [new DateTimeNormalizer(\DateTime::ATOM), new ObjectNormalizer()],
                    [new CsvEncoder()]
                )
            )
        );

        /** @var \Symfony\Component\Console\Command\Command[] $commands */
        $commands = array_merge(
            parent::getDefaultCommands(),
            [
                new FindInitialAmountCommand($processor),
                new ProcessCommand($processor),
            ]
        );

        return $commands;
    }
}
