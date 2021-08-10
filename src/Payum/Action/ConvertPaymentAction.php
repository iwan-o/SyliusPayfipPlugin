<?php

declare(strict_types=1);

namespace Bouteg\PayfipPlugin\Payum\Action;

use Payum\Core\Action\ActionInterface;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\Request\Convert;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\PaymentInterface as SyliusPaymentInterface;

final class ConvertPaymentAction implements ActionInterface
{
    /**
     * {@inheritDoc}
     *
     * @param Convert $request
     */
    public function execute($request): void
    {
        RequestNotSupportedException::assertSupports($this, $request);

        /** @var SyliusPaymentInterface $payment */
        $payment = $request->getSource();
        /** @var OrderInterface $order */
        $order = $payment->getOrder();

        $details['merchantReference'] = $order->getNumber() . "-" . $payment->getId();
        $details['paymentAmount'] = $payment->getAmount();
        $details['shopperEmail'] = $order->getCustomer()->getEmail();
        $details['currencyCode'] = $order->getCurrencyCode();
        $details['shopperReference'] = $order->getCustomer()->getId();
        $details['shopperLocale'] = $order->getLocaleCode();
        $details['countryCode'] = null !== $order->getShippingAddress() ? $order->getShippingAddress()->getCountryCode() : null;

        $request->setResult((array) $details);
    }

    /**
     * {@inheritdoc}
     */
    public function supports($request): bool
    {
        return
            $request instanceof Convert &&
            $request->getSource() instanceof SyliusPaymentInterface &&
            $request->getTo() === 'array'
        ;
    }
}
