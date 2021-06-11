<?php

declare(strict_types=1);

namespace Bouteg\PayfipPlugin\Payum;

use Bouteg\PayfipPlugin\Payum\Action\StatusAction;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\GatewayFactory;

final class SyliusPaymentGatewayFactory extends GatewayFactory
{
    protected function populateConfig(ArrayObject $config): void
    {
        $config->defaults([
            'payum.factory_name' => 'bouteg_payfip_payment',
            'payum.factory_title' => 'Payfip payment',
            'payum.action.status' => new StatusAction(),
        ]);

        $config['payum.api'] = function (ArrayObject $config) {
            return new PayfipApi($config['client_number'], $config['environment']);
        };
    }
}
