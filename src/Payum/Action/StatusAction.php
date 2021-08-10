<?php

declare(strict_types=1);

namespace Bouteg\PayfipPlugin\Payum\Action;

use Bouteg\PayfipPlugin\Bridge\PayfipApi;
use Payum\Core\Action\ActionInterface;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\Request\GetStatusInterface;
use Sylius\Component\Core\Model\PaymentInterface as SyliusPaymentInterface;

final class StatusAction implements ActionInterface
{
    public function execute($request): void
    {
        RequestNotSupportedException::assertSupports($this, $request);

        /** @var SyliusPaymentInterface $payment */
        $payment = $request->getFirstModel();

        $details = $payment->getDetails();

        if (!isset($details[PayfipApi::DETAILS_IDOP]) || !isset($details[PayfipApi::DETAILS_STATUS])) {
            $request->markNew();

            return;
        }

        if (PayfipApi::STATUS_CREATED === $details[PayfipApi::DETAILS_STATUS]) {
            $request->markPending();

            return;
        }

        if (PayfipApi::STATUS_PAID === $details[PayfipApi::DETAILS_STATUS]) {
            $request->markCaptured();

            return;
        }

        if (PayfipApi::STATUS_FAILED === $details[PayfipApi::DETAILS_STATUS]) {
            $request->markFailed();

            return;
        }

        if (PayfipApi::STATUS_CANCELED === $details[PayfipApi::DETAILS_STATUS]) {
            $request->markCanceled();

            return;
        }

    }

    public function supports($request): bool
    {
        return 
            $request instanceof GetStatusInterface && 
            $request->getModel() instanceof SyliusPaymentInterface
        ;
    }
}
