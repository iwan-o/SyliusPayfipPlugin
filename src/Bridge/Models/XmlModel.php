<?php

declare(strict_types=1);

namespace Bouteg\PayfipPlugin\Bridge\Models;

class XmlModel
{

    /** @var \DOMDocument */
    protected $domDocument;

    public function __construct(string $data)
    {
        $this->domDocument = $this->stringToDOMDocument($data);
    }

    public function getXml(): string
    {
        return $this->domDocument->saveXML();
    }

    public function getDomDocument(): \DOMDocument
    {
        return $this->domDocument;
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