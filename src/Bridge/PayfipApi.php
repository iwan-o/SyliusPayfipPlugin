<?php

declare(strict_types=1);

namespace Bouteg\PayfipPlugin\Bridge;

use Bouteg\PayfipPlugin\Bridge\Exception\PayfipApiException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Payum\Core\Request\Capture;
use Payum\Core\Security\TokenInterface;
use Sylius\Component\Core\Model\PaymentInterface as SyliusPaymentInterface;

class PayfipApi
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

    /** 
     * @var Client 
     */
    private $client;

    /** @var string */
    private $clientNumber;

    /** @var string */
    private $environment;

    /** 
     * @var string
     */
    private $urlEndpoint;

    /** 
     * @var string
     */
    private $urlPaiement;

    /** 
     * @var \DOMDocument 
     */
    private $domDocumentCreerPaiementSecurise;

    public function __construct(Client $client, string $urlEndpoint, string $urlPaiement, string $xmlBodyCreerPaiementSecurise, string $xmlBodyRecupererDetailPaiementSecurise)
    {
        $this->client = $client;
        $this->urlEndpoint = $urlEndpoint;
        $this->urlPaiement = $urlPaiement;
        $this->domDocumentCreerPaiementSecurise = $this->stringToDOMDocument($xmlBodyCreerPaiementSecurise);
        $this->domDocumentRecupererDetailPaiementSecurise = $this->stringToDOMDocument($xmlBodyRecupererDetailPaiementSecurise);
    }

    public function setConfig(string $clientNumber, string $environment): void 
    {
        $this->clientNumber = $clientNumber;
        $this->environment = $environment;
    }

    public function creerPaiementSecurise(Capture $request, TokenInterface $notifyToken): ?string
    {

        $idOp = null;

        $body = $this->prepareCreerPaiementSecurise($request, $notifyToken);

        try {

            $response = $this->client->post($this->urlEndpoint, ['body' => $body]);

            $xmlResponse = $this->stringToDOMDocument($response->getBody()->getContents());

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

    private function prepareCreerPaiementSecurise(Capture $request, TokenInterface $notifyToken): string
    {

        /** @var TokenInterface $token */
        $token = $request->getToken();

        /** @var SyliusPaymentInterface $payment */
        $payment = $request->getModel();

        $xml = $this->domDocumentCreerPaiementSecurise->cloneNode(true);

        $xml->getElementsByTagName('exer')->item(0)->nodeValue = date('Y');
        $xml->getElementsByTagName('mel')->item(0)->nodeValue = $payment->getOrder()->getCustomer()->getEmail();
        $xml->getElementsByTagName('montant')->item(0)->nodeValue = $payment->getAmount();
        $xml->getElementsByTagName('numcli')->item(0)->nodeValue = $this->clientNumber;
        $xml->getElementsByTagName('refdet')->item(0)->nodeValue = $payment->getOrder()->getNumber();
        $xml->getElementsByTagName('saisie')->item(0)->nodeValue = $this->environment;
        $xml->getElementsByTagName('urlnotif')->item(0)->nodeValue = $notifyToken->getTargetUrl();
        $xml->getElementsByTagName('urlredirect')->item(0)->nodeValue = $token->getAfterUrl();

        return $xml->saveXML();

    }

    public function recupererDetailPaiementSecurise(string $idOp): string
    {

        $staus = null;

        $body = $this->prepareRecupererDetailPaiementSecurise($idOp);

        try {

            $response = $this->client->post($this->urlEndpoint, ['body' => $body]);

            $xmlResponse = $this->stringToDOMDocument($response->getBody()->getContents());

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

    private function prepareRecupererDetailPaiementSecurise($idop): string
    {

        $xml = $this->domDocumentRecupererDetailPaiementSecurise->cloneNode(true);

        $xml->getElementsByTagName('idOp')->item(0)->nodeValue = $idop;

        return $xml->saveXML();

    }

    public function generateUrlPaiement(string $idop): string
    {
        return $this->urlPaiement . $idop;  
    }

    private function stringToDOMDocument($text): \DOMDocument
    {

        $xml = new \DOMDocument();

        if ( @$xml->loadXML($text) === false ) {
            
            throw new \Exception('Invalid xml data : ' . $text);

        } 
        
        return $xml;

    }

}