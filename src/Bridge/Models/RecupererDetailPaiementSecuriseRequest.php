<?php

declare(strict_types=1);

namespace Bouteg\PayfipPlugin\Bridge\Models;

class RecupererDetailPaiementSecuriseRequest extends XmlModel implements RecupererDetailPaiementSecuriseRequestInterface
{

    public function populate($idop): void
    {

        $this->domDocument->getElementsByTagName('idOp')->item(0)->nodeValue = $idop;

    }
}