<?php

declare(strict_types=1);

namespace Bouteg\PayfipPlugin\Payum;

final class PayfipApi
{
    const ENV_TEST = 'T';
    const ENV_ACTIVATION = 'X';
    const ENV_PRODUCTION = 'W';

    /** @var string */
    private $clientNumber;

    /** @var string */
    private $environment;

    public function __construct(string $clientNumber, string $environment = self::ENV_TEST)
    {
        $this->clientNumber = $clientNumber;
        $this->environment = $environment;
    }

    public function getClientNumber(): string
    {
        return $this->clientNumber;
    }

    public function getEnvironment(): string
    {
        return $this->environment;
    }
}