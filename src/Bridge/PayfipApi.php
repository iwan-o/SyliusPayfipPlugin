<?php

declare(strict_types=1);

namespace Bouteg\PayfipPlugin\Bridge;

use Bouteg\PayfipPlugin\Bridge\Exception\PayfipApiException;
use Bouteg\PayfipPlugin\Bridge\Models\XmlModel;
use Bouteg\PayfipPlugin\Bridge\Models\CreerPaiementSecuriseRequest;
use Bouteg\PayfipPlugin\Bridge\Models\RecupererDetailPaiementSecuriseRequest;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Payum\Core\Request\Capture;
use Payum\Core\Security\TokenInterface;
use Sylius\Component\Core\Model\PaymentInterface as SyliusPaymentInterface;

final class PayfipApi
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

    /** @var Client */
    private $client;

    /** @var string */
    private $clientNumber;

    /** @var string */
    private $environment;

    /** @var string */
    private $urlEndpoint;

    /** @var string */
    private $urlPaiement;

    /** @var CreerPaiementSecuriseRequest */
    private $creerPaiementSecuriseRequest;

    /** @var RecupererDetailPaiementSecuriseRequest */
    private $recupererDetailPaiementSecuriseRequest;

    public function __construct(
        Client $client, 
        string $urlEndpoint, 
        string $urlPaiement, 
        CreerPaiementSecuriseRequest $creerPaiementSecuriseRequest, 
        RecupererDetailPaiementSecuriseRequest $recupererDetailPaiementSecuriseRequest
        )
    {
        $this->client = $client;
        $this->urlEndpoint = $urlEndpoint;
        $this->urlPaiement = $urlPaiement;
        $this->creerPaiementSecuriseRequest = $creerPaiementSecuriseRequest;
        $this->recupererDetailPaiementSecuriseRequest = $recupererDetailPaiementSecuriseRequest;
    }

    public function setConfig(string $clientNumber, string $environment): void 
    {
        $this->clientNumber = $clientNumber;
        $this->environment = $environment;
    }

    public function creerPaiementSecurise(Capture $request, TokenInterface $notifyToken): ?string
    {

        $idOp = null;

        $this->creerPaiementSecuriseRequest->populate($this->clientNumber, $this->environment, $request, $notifyToken);

        $body = $this->creerPaiementSecuriseRequest->getXml();

        try {

            $response = $this->client->post($this->urlEndpoint, ['body' => $body]);

            $xmlResponse = (new XmlModel($response->getBody()->getContents()))->getDomDocument();

            $idOp = $xmlResponse->getElementsByTagName('idOp')->item(0)->nodeValue;

        } catch (RequestException $e) {

            if( null !== $e->getResponse() ) {

                throw new PayfipApiException($e->getResponse()->getBody()->getContents(), $e->getCode());

            } else {


                throw new \Exception($e->getMessage(), $e->getCode());

            }
        }

        return $idOp;

    }

    public function recupererDetailPaiementSecurise(string $idOp): ?string
    {

        $staus = null;

        $this->recupererDetailPaiementSecuriseRequest->populate($idOp);

        $body = $this->recupererDetailPaiementSecuriseRequest->getXml();

        try {

            $response = $this->client->post($this->urlEndpoint, ['body' => $body]);

            $xmlResponse = (new XmlModel($response->getBody()->getContents()))->getDomDocument();

            $staus = $xmlResponse->getElementsByTagName('resultrans')->item(0)->nodeValue;
            
        } catch (RequestException $e) {

            if( null !== $e->getResponse() ) {

                $payfipApiException = new PayfipApiException($e->getResponse()->getBody()->getContents(), $e->getCode());

                if($payfipApiException->getErrorCode() !== 'P5') {

                    throw $payfipApiException;
                }

            } else {

                throw new \Exception($e->getMessage(), $e->getCode());

            }

        }

        return $staus;

    }

    public function generateUrlPaiement(string $idop): string
    {
        return $this->urlPaiement . $idop;  
    }

}