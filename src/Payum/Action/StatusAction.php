<?php

declare(strict_types=1);

namespace Bouteg\PayfipPlugin\Payum\Action;

use Bouteg\PayfipPlugin\Bridge\PayfipApiInterface;
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

        if (!isset($details[PayfipApiInterface::DETAILS_IDOP]) || !isset($details[PayfipApiInterface::DETAILS_STATUS])) {
            $request->markNew();

            return;
        }

        if (PayfipApiInterface::STATUS_CREATED === $details[PayfipApiInterface::DETAILS_STATUS]) {
            $request->markPending();

            return;
        }

        if (PayfipApiInterface::STATUS_PAID === $details[PayfipApiInterface::DETAILS_STATUS]) {
            $request->markCaptured();

            return;
        }

        if (PayfipApiInterface::STATUS_FAILED === $details[PayfipApiInterface::DETAILS_STATUS]) {
            $request->markFailed();

            return;
        }

        if (PayfipApiInterface::STATUS_CANCELED === $details[PayfipApiInterface::DETAILS_STATUS]) {
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
