<?php

declare(strict_types=1);

namespace Bouteg\PayfipPlugin\Bridge\Models;

use Payum\Core\Request\Capture;
use Payum\Core\Security\TokenInterface;

interface RecupererDetailPaiementSecuriseRequestInterface extends XmlModelInterface
{

    public function populate($idop): void;

}
