<?php

declare(strict_types=1);

namespace Bouteg\PayfipPlugin\Bridge;

use Bouteg\PayfipPlugin\Bridge\Exception\PayfipApiException;
use Bouteg\PayfipPlugin\Bridge\Models\XmlModel;
use Bouteg\PayfipPlugin\Bridge\Models\CreerPaiementSecuriseRequestInterface;
use Bouteg\PayfipPlugin\Bridge\Models\PayfipApiInterface;
use Bouteg\PayfipPlugin\Bridge\Models\RecupererDetailPaiementSecuriseRequestInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Payum\Core\Request\Capture;
use Payum\Core\Security\TokenInterface;

final class PayfipApi implements PayfipApiInterface
{

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

    /** @var CreerPaiementSecuriseRequestInterface */
    private $creerPaiementSecuriseRequest;

    /** @var RecupererDetailPaiementSecuriseRequestInterface */
    private $recupererDetailPaiementSecuriseRequest;

    public function __construct(
        Client $client, 
        string $urlEndpoint, 
        string $urlPaiement, 
        CreerPaiementSecuriseRequestInterface $creerPaiementSecuriseRequest, 
        RecupererDetailPaiementSecuriseRequestInterface $recupererDetailPaiementSecuriseRequest
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