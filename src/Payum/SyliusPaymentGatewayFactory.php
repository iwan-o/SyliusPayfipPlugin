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
            'payum.http_client' => '@bouteg.bouteg_payfip_payment.payfip_api_bridge',
            'payum.action.status' => new StatusAction(),
        ]);

        if (false == $config['payum.api']) {

            $config['payum.default_options'] = [
                'client_number' => '',
                'environment' => '',
            ];

            $config['payum.required_options'] = ['client_number', 'environment'];

            $config['payum.api'] = function (ArrayObject $config) {

                $config->validateNotEmpty($config['payum.required_options']);

                $payfipApi = $config['payum.http_client'];

                $payfipApi->setConfig(
                    $config['client_number'],
                    $config['environment']
                );

                return $payfipApi;
            };

        }
    }
}
