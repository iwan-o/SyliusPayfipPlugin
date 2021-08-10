<?php

declare(strict_types=1);

namespace Bouteg\PayfipPlugin\Payum\Action;

use Bouteg\PayfipPlugin\Bridge\PayfipApi;
use Payum\Core\Action\ActionInterface;
use Payum\Core\ApiAwareInterface;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\Exception\UnsupportedApiException;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\GatewayAwareTrait;
use Payum\Core\Reply\HttpResponse;
use Payum\Core\Request\GetHttpRequest;
use Payum\Core\Request\Notify;
use Sylius\Component\Core\Model\PaymentInterface;
use Sylius\Component\Core\Model\PaymentInterface as SyliusPaymentInterface;
use Webmozart\Assert\Assert;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * @author Patryk Drapik <patryk.drapik@bitbag.pl>
 */
final class NotifyAction implements ActionInterface, ApiAwareInterface, GatewayAwareInterface
{
    use GatewayAwareTrait;

    /**
     * @var MercanetBnpParibasBridgeInterface
     */
    private $mercanetBnpParibasBridge;

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

        $this->gateway->execute($httpRequest = new GetHttpRequest());

        try {

            $status = $this->api->paymentNotify($httpRequest->content, $details[PayfipApi::DETAILS_IDOP]);

            $details[PayfipApi::DETAILS_STATUS] = $status;
        
        } catch (\Exception $exception){

            $this->logger->error("Payfip payment notify error : {$exception->getMessage()} ({$exception->getCode()})");

            throw new HttpResponse($exception->getMessage(), Response::HTTP_BAD_REQUEST); 

        }

        $payment->setDetails($details);

        throw new HttpResponse(null, Response::HTTP_OK);

    }

    /**
     * {@inheritDoc}
     */
    public function setApi($api): void
    {
        if (!$api instanceof PayfipApi) {
            throw new UnsupportedApiException('Not supported. Expected an instance of ' . PayfipApi::class);
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