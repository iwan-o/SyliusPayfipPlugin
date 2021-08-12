<?php

declare(strict_types=1);

namespace Bouteg\PayfipPlugin\Bridge\Exception;

use Doctrine\ORM\Query\Expr\Func;

class PayfipApiException extends \Exception
{

    /** @var string */
    private $errorCode;

    /** @var string */
    private $errorLibelle;

    public function __construct(string $response, $code)
    {

        $this->initErrorInfos($response);

        if( null !== $this->errorCode && null !== $this->errorLibelle ) {

            $message = 'Code : ' . $this->errorCode . ', libelle : ' . $this->errorLibelle;

        } else {

            $message = $response;

        }

        parent::__construct($message, $code);
        
    }

    private function initErrorInfos(string $response): void 
    {
        $xmlResponse = $this->parseResponse($response);

        if( null !== $xmlResponse && 0 !== $xmlResponse && $xmlResponse->getElementsByTagName('code')->length ) {

            $this->errorCode =  $xmlResponse->getElementsByTagName('code')->item(0)->nodeValue;
            $this->errorLibelle =  $xmlResponse->getElementsByTagName('libelle')->item(0)->nodeValue;
        
        } else if( null !== $xmlResponse && 0 !== $xmlResponse && $xmlResponse->getElementsByTagName('faultcode')->length ) {

            $this->errorCode =  $xmlResponse->getElementsByTagName('faultcode')->item(0)->nodeValue;
            $this->errorLibelle =  $xmlResponse->getElementsByTagName('faultstring')->item(0)->nodeValue;

        }
    }

    private function parseResponse($response): ?\DOMDocument 
    {

        $xmlResponse = new \DOMDocument();

        if (@$xmlResponse->loadXML($response) === false) {
            return null;
        } else {
            return $xmlResponse;
        }

    }

    public function getErrorCode(): ?string
    {
        return $this->errorCode;
    }

    public function getErrorLibelle(): ?string
    {
        return $this->errorLibelle;
    }
    
}