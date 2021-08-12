<?php

declare(strict_types=1);

namespace Bouteg\PayfipPlugin\Bridge;

use Bouteg\PayfipPlugin\Bridge\Exception\PayfipApiException;
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

    public function __construct(Client $client, string $urlEndpoint, string $urlPaiement, string $xmlBodyCreerPaiementSecurise)
    {
        $this->client = $client;
        $this->urlEndpoint = $urlEndpoint;
        $this->urlPaiement = $urlPaiement;
        $this->domDocumentCreerPaiementSecurise = $this->stringToDOMDocument($xmlBodyCreerPaiementSecurise);
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

            if(null === $xmlResponse ) {
                throw new \Exception('Invalid xml data : ' . $response);
            }

            $idOp = $xmlResponse->getElementsByTagName('idOp')->item(0)->nodeValue;

        } catch (RequestException $e) {
            if( null !== $e->getResponse() ) {
                throw new PayfipApiException($e->getResponse()->getBody()->getContents());
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

    public function paymentNotify(string $response, string $idOp): string
    {

        $xmlResponse = $this->stringToDOMDocument($response);

        if(null === $xmlResponse ) {

            throw new \Exception('Invalid xml data : ' . $response);

        } else if( 0 !== $xmlResponse->getElementsByTagName('FonctionnelleErreur')->length && $xmlResponse->getElementsByTagName('code')->item(0)->nodeValue == 'P5') {
            
            return self::STATUS_CREATED;

        } else if( 0 !== $xmlResponse->getElementsByTagName('recupererDetailPaiementSecuriseResponse')->length ) {

            if( $idOp == $xmlResponse->getElementsByTagName('idop')->item(0)->nodeValue ) {

                return $xmlResponse->getElementsByTagName('resultrans')->item(0)->nodeValue;

            } else  {

                throw new \Exception('Invalid idOp');
            }

        } else {

            throw new \Exception('Unable to parse xml data');
        }

    }

    public function generateUrlPaiement(string $idop): string
    {
        return $this->urlPaiement . $idop;  
    }

    private function stringToDOMDocument($text): ?\DOMDocument
    {

        $xml = new \DOMDocument();

        if ( @$xml->loadXML($text) === false ) {
            return null;
        } else {
            return $xml;
        }

    }

}