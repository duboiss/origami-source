<?php

declare(strict_types=1);

namespace App\Command\Services;

class PhpCommand extends AbstractServiceCommand
{
    /** @var string */
    private const COMMAND_SERVICE_NAME = 'php';

    /** @var string */
    private const COMMAND_USERNAME = 'www-data:www-data';

    /** {@inheritdoc} */
    protected static $defaultName = 'origami:php';

    /**
     * {@inheritdoc}
     */
    public function getServiceName(): string
    {
        return self::COMMAND_SERVICE_NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function getUsername(): string
    {
        return self::COMMAND_USERNAME;
    }
}