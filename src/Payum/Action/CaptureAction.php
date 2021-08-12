<?php

declare(strict_types=1);

namespace Bouteg\PayfipPlugin\Payum\Action;

use Bouteg\PayfipPlugin\Bridge\PayfipApi;
use Payum\Core\Action\ActionInterface;
use Payum\Core\ApiAwareInterface;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\Exception\UnsupportedApiException;
use Payum\Core\Request\Capture;
use Payum\Core\Security\TokenInterface;
use Payum\Core\Reply\HttpRedirect;
use Payum\Core\Payum;
use Psr\Log\LoggerInterface;
use Sylius\Component\Core\Model\PaymentInterface as SyliusPaymentInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

final class CaptureAction implements ActionInterface, ApiAwareInterface
{

    /**
     * @var Payum
     */
    private $payum;

    /**
     * @var LoggerInterface
     */
    private $logger;
    
    /**
     * @var SessionInterface
     */
    protected $session;

    /** 
     * @var PayfipApi
     */
    private $api;

    public function __construct(Payum $payum, LoggerInterface $logger, SessionInterface $session)
    {
        $this->payum = $payum;
        $this->logger = $logger;
        $this->session = $session;
    }

    public function execute($request): void
    {

        RequestNotSupportedException::assertSupports($this, $request);

        /** @var TokenInterface $token */
        $token = $request->getToken();

        /** @var SyliusPaymentInterface $payment */
        $payment = $request->getModel();

        $details = $payment->getDetails();

        $notifyToken = $this->createNotifyToken($token->getGatewayName(), $token->getDetails());

        try {

            $details[PayfipApi::DETAILS_IDOP] = $this->api->creerPaiementSecurise($request, $notifyToken);
            $details[PayfipApi::DETAILS_STATUS] = PayfipApi::STATUS_CREATED;

            $payment->setDetails($details);

        } catch (\Exception $e) {

            $this->logger->critical("Payfip payment creation failed : " . $e->getMessage());

            $this->addErrorFlash();
 
            throw new HttpRedirect($token->getAfterUrl());

        }

        throw new HttpRedirect($this->api->generateUrlPaiement($details[PayfipApi::DETAILS_IDOP]));
   
    }

    public function supports($request): bool
    {
        return
            $request instanceof Capture &&
            $request->getModel() instanceof SyliusPaymentInterface
        ;
    }

    public function setApi($api): void
    {
        if (!$api instanceof PayfipApi) {
            throw new UnsupportedApiException('Not supported. Expected an instance of ' . PayfipApi::class);
        }

        $this->api = $api;
    }

    private function createNotifyToken($gatewayName, $model)
    {
        return $this->payum->getTokenFactory()->createNotifyToken(
            $gatewayName,
            $model
        );
    }

    private function addErrorFlash(): void
    {
        /** @var FlashBagInterface $flashBag */
        $flashBag = $this->session->getBag('flashes');
        $flashBag->add('error', 'bouteg.payfip_plugin.creation_failed');
    }

}