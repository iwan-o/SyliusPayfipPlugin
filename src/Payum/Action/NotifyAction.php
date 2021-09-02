<?php

declare(strict_types=1);

namespace Bouteg\PayfipPlugin\Payum\Action;

use Bouteg\PayfipPlugin\Bridge\PayfipApiInterface;
use Payum\Core\Action\ActionInterface;
use Payum\Core\ApiAwareInterface;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\Exception\UnsupportedApiException;
use Payum\Core\Reply\HttpResponse;
use Payum\Core\Request\Notify;
use Sylius\Component\Core\Model\PaymentInterface;
use Sylius\Component\Core\Model\PaymentInterface as SyliusPaymentInterface;
use Webmozart\Assert\Assert;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;

final class NotifyAction implements ActionInterface, ApiAwareInterface
{

    /** 
     * @var PayfipApiInterface
     */
    private $api;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * {@inheritDoc}
     */
    public function execute($request)
    {
        /** @var $request Notify */
        RequestNotSupportedException::assertSupports($this, $request);

        /** @var SyliusPaymentInterface $payment */
        $payment = $request->getFirstModel();

        Assert::isInstanceOf($payment, PaymentInterface::class);

        $details = $payment->getDetails();

        try {

            $status = $this->api->recupererDetailPaiementSecurise($details[PayfipApiInterface::DETAILS_IDOP]);

            if( null !== $status) {

                $details[PayfipApiInterface::DETAILS_STATUS] = $status;

                $payment->setDetails($details);
                
            }
        
        } catch (\Exception $exception){

            $this->logger->critical("Payfip payment notify error : {$exception->getMessage()} ({$exception->getCode()})");

        }

        throw new HttpResponse(null, Response::HTTP_OK);

    }

    /**
     * {@inheritDoc}
     */
    public function setApi($api): void
    {
        if (!$api instanceof PayfipApiInterface) {
            throw new UnsupportedApiException('Not supported. Expected an instance of ' . PayfipApiInterface::class);
        }

        $this->api = $api;
    }

    /**
     * {@inheritDoc}
     */
    public function supports($request)
    {
        return 
            $request instanceof Notify &&
            $request->getModel() instanceof SyliusPaymentInterface
        ;
    }
}