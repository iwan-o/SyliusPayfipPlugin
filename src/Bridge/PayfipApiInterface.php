<?php

declare(strict_types=1);

namespace Bouteg\PayfipPlugin\Bridge\Models;

use Bouteg\PayfipPlugin\Bridge\Models\CreerPaiementSecuriseRequestInterface;
use Bouteg\PayfipPlugin\Bridge\Models\RecupererDetailPaiementSecuriseRequestInterface;
use GuzzleHttp\Client;
use Payum\Core\Request\Capture;
use Payum\Core\Security\TokenInterface;

interface PayfipApiInterface
{
    const ENV_TEST = 'T';
    const ENV_ACTIVATION = 'X';
    const ENV_PRODUCTION = 'W';

    const DETAILS_IDOP = 'idOp';
    const DETAILS_STATUS = 'status';

    const STATUS_CREATED = 'C';
    const STATUS_PAID = 'P';
    const STATUS_CANCELED = 'A';
    const STATUS_FAILED = 'R';

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
