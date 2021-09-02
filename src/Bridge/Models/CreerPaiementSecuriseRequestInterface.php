<?php

declare(strict_types=1);

namespace Bouteg\PayfipPlugin\Bridge\Models;

use Payum\Core\Request\Capture;
use Payum\Core\Security\TokenInterface;

interface CreerPaiementSecuriseRequestInterface extends XmlModelInterface
{

    public function populate(string $clientNumber, string $environment, Capture $request, TokenInterface $notifyToken): void;

}
