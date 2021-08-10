<?php

declare(strict_types=1);

namespace Bouteg\PayfipPlugin\Bridge\Exception;

class PayfipApiException extends \Exception
{

    public function __construct(string $response)
    {

        $xmlResponse = $this->parseResponse($response);

        if( null !== $xmlResponse && 0 !== $xmlResponse && $xmlResponse->getElementsByTagName('code')->length ) {

            $message = 'Code : ' . $xmlResponse->getElementsByTagName('code')->item(0)->nodeValue . ', libelle : ' . $xmlResponse->getElementsByTagName('libelle')->item(0)->nodeValue;
        
        } else if( null !== $xmlResponse && 0 !== $xmlResponse && $xmlResponse->getElementsByTagName('faultcode')->length ) {

            $message = 'Code : ' . $xmlResponse->getElementsByTagName('faultcode')->item(0)->nodeValue . ', libelle : ' . $xmlResponse->getElementsByTagName('faultstring')->item(0)->nodeValue;
        
        } else  {

            $message = $response;
        }

        parent::__construct($message, 400);
        
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
    
}