<?php

declare(strict_types=1);

namespace Bouteg\PayfipPlugin\Bridge\Models;

use Payum\Core\Request\Capture;
use Payum\Core\Security\TokenInterface;
use Sylius\Component\Core\Model\PaymentInterface as SyliusPaymentInterface;

class CreerPaiementSecuriseRequest extends XmlModel implements CreerPaiementSecuriseRequestInterface
{

    public function populate(string $clientNumber, string $environment, Capture $request, TokenInterface $notifyToken): void
    {

        /** @var TokenInterface $token */
        $token = $request->getToken();

        /** @var SyliusPaymentInterface $payment */
        $payment = $request->getModel();

        $this->domDocument->getElementsByTagName('exer')->item(0)->nodeValue = date('Y');
        $this->domDocument->getElementsByTagName('mel')->item(0)->nodeValue = $payment->getOrder()->getCustomer()->getEmail();
        $this->domDocument->getElementsByTagName('montant')->item(0)->nodeValue = $payment->getAmount();
        $this->domDocument->getElementsByTagName('numcli')->item(0)->nodeValue = $clientNumber;
        $this->domDocument->getElementsByTagName('refdet')->item(0)->nodeValue = $payment->getOrder()->getNumber();
        $this->domDocument->getElementsByTagName('saisie')->item(0)->nodeValue = $environment;
        $this->domDocument->getElementsByTagName('urlnotif')->item(0)->nodeValue = $notifyToken->getTargetUrl();
        $this->domDocument->getElementsByTagName('urlredirect')->item(0)->nodeValue = $token->getAfterUrl();

    }
}