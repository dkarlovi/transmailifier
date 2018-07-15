<?php

declare(strict_types=1);

namespace Dkarlovi\Transmailifier;

use Symfony\Component\Serializer\SerializerInterface;

/**
 * Class Mailer.
 */
class Mailer
{
    /**
     * @var string
     */
    private $from;

    /**
     * @var \Swift_Mailer
     */
    private $mailer;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @param string              $from
     * @param \Swift_Mailer       $mailer
     * @param SerializerInterface $serializer
     */
    public function __construct(string $from, \Swift_Mailer $mailer, SerializerInterface $serializer)
    {
        $this->from = $from;
        $this->mailer = $mailer;
        $this->serializer = $serializer;
    }

    /**
     * @param array  $transactions
     * @param string $description
     * @param array  $addresses
     */
    public function notify(array $transactions, string $description, array $addresses): void
    {
        $file = tempnam(sys_get_temp_dir(), 'transmailifier');
        rename($file, $file .= '.csv');
        $content = $this->serializer->serialize($transactions, 'csv');
        file_put_contents($file, $content);

        /** @var \Swift_Message $message */
        $message = $this->mailer->createMessage();
        $message->setFrom($this->from);
        $message->setSubject($description);
        foreach ($addresses as $notificationAddress) {
            $message->addTo($notificationAddress);
        }
        $message->attach(\Swift_Attachment::fromPath($file, 'text/csv'));
        try {
            $this->mailer->send($message);
        } finally {
            unlink($file);
        }
    }
}
