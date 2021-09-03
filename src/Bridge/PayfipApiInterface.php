<?php

declare(strict_types=1);

namespace Bouteg\PayfipPlugin\Bridge;

use Bouteg\PayfipPlugin\Bridge\Models\CreerPaiementSecuriseRequestInterface;
use Bouteg\PayfipPlugin\Bridge\Models\RecupererDetailPaiementSecuriseRequestInterface;
use GuzzleHttp\Client;
use Payum\Core\Request\Capture;
use Payum\Core\Security\TokenInterface;

interface PayfipApiInterface
{
    public const STORAGE_BOOLEAN = 'boolean';
    public const ENV_TEST = 'T';
    public const ENV_ACTIVATION = 'X';
    public const ENV_PRODUCTION = 'W';

    public const DETAILS_IDOP = 'idOp';
    public const DETAILS_STATUS = 'status';

    public const STATUS_CREATED = 'C';
    public const STATUS_PAID_CB = 'P';
    public const STATUS_PAID_DIRECT_DEBIT = 'V';
    public const STATUS_CANCELED_CB = 'A';
    public const STATUS_FAILED_CB = 'R';
    public const STATUS_FAILED_DIRECT_DEBIT = 'Z';

    public function __construct(
        Client $client, 
        string $urlEndpoint, 
        string $urlPaiement, 
        CreerPaiementSecuriseRequestInterface $creerPaiementSecuriseRequest, 
        RecupererDetailPaiementSecuriseRequestInterface $recupererDetailPaiementSecuriseRequest
    );

    public function setConfig(string $clientNumber, string $environment): void;

    public function creerPaiementSecurise(Capture $request, TokenInterface $notifyToken): ?string;

    public function recupererDetailPaiementSecurise(string $idOp): ?string;

    public function generateUrlPaiement(string $idop): string;

}
