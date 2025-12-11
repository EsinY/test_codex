<?php

namespace PHPMailer\PHPMailer;

/**
 * Минимальная реализация PHPMailer для отправки писем через mail().
 * Поддерживает только базовый функционал, необходимый для отправки формы.
 */
class PHPMailer
{
    public string $CharSet = 'UTF-8';
    public string $Subject = '';
    public string $Body = '';
    public string $AltBody = '';

    /** @var array<int, array{address: string, name: string}> */
    private array $to = [];

    /** @var array{address: string, name: string}|null */
    private ?array $from = null;

    /** @var array<int, array{address: string, name: string}> */
    private array $replyTo = [];

    private bool $isHtml = false;

    public function isHTML(bool $isHtml = true): void
    {
        $this->isHtml = $isHtml;
    }

    public function setFrom(string $address, string $name = ''): void
    {
        $this->from = ['address' => $address, 'name' => $name];
    }

    public function addAddress(string $address, string $name = ''): void
    {
        $this->to[] = ['address' => $address, 'name' => $name];
    }

    public function addReplyTo(string $address, string $name = ''): void
    {
        $this->replyTo[] = ['address' => $address, 'name' => $name];
    }

    /**
     * Отправка письма через функцию mail().
     */
    public function send(): bool
    {
        if (empty($this->to)) {
            throw new Exception('Не указан получатель письма.');
        }

        $toHeader = implode(', ', array_map(fn ($item) => $this->formatAddress($item), $this->to));
        $headers = $this->buildHeaders();

        $body = $this->Body ?: $this->AltBody;
        $subject = $this->Subject ?: '(без темы)';

        return mail($toHeader, $subject, $body, implode("\r\n", $headers));
    }

    /**
     * @param array{address: string, name: string} $item
     */
    private function formatAddress(array $item): string
    {
        $name = trim($item['name']);
        $address = trim($item['address']);

        return $name !== '' ? sprintf('%s <%s>', $name, $address) : $address;
    }

    /**
     * @return array<int, string>
     */
    private function buildHeaders(): array
    {
        $headers = [];

        if ($this->from) {
            $headers[] = 'From: ' . $this->formatAddress($this->from);
        }

        foreach ($this->replyTo as $reply) {
            $headers[] = 'Reply-To: ' . $this->formatAddress($reply);
        }

        $headers[] = 'MIME-Version: 1.0';
        if ($this->isHtml) {
            $headers[] = 'Content-type: text/html; charset=' . $this->CharSet;
        } else {
            $headers[] = 'Content-type: text/plain; charset=' . $this->CharSet;
        }

        return $headers;
    }
}
