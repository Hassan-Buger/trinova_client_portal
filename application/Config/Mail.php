<?php

namespace Application\Config;

class Mail
{
    public static function getConfig(): array
    {
        return [
            'driver'       => $_ENV['MAIL_DRIVER'] ?? 'smtp',
            'host'         => $_ENV['MAIL_HOST'] ?? 'smtp.mailtrap.io',
            'port'         => $_ENV['MAIL_PORT'] ?? 2525,
            'username'     => $_ENV['MAIL_USERNAME'] ?? '',
            'password'     => $_ENV['MAIL_PASSWORD'] ?? '',
            'from_address' => $_ENV['MAIL_FROM_ADDRESS'] ?? 'no-reply@trinova.co.uk',
            'from_name'    => $_ENV['MAIL_FROM_NAME'] ?? 'TriNova Accounting',
        ];
    }
}
