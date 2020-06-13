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

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Class Mailer.
 */
class Mailer
{
    /**
     * @var MailerInterface
     */
    private $mailer;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    public function __construct(MailerInterface $mailer, SerializerInterface $serializer)
    {
        $this->mailer = $mailer;
        $this->serializer = $serializer;
    }

    public function notify(array $transactions, string $description, array $addresses): void
    {
        $file = tempnam(sys_get_temp_dir(), 'transmailifier');
        if (false === $file) {
            throw new \RuntimeException('Unable to create temporary CSV file');
        }

        rename($file, $file .= '.csv');
        $content = $this->serializer->serialize($transactions, 'csv');
        file_put_contents($file, $content);

        $message = new Email();
        $message->subject($description);
        foreach ($addresses as $notificationAddress) {
            $message->addTo($notificationAddress);
        }
        $message->attachFromPath($file, basename($file), 'text/csv');
        try {
            $this->mailer->send($message);
        } finally {
            unlink($file);
        }
    }
}
