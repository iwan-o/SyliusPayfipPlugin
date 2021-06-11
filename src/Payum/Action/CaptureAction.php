<?php

declare(strict_types=1);

namespace Bouteg\PayfipPlugin\Payum\Action;

use Bouteg\PayfipPlugin\Payum\PayfipApi;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Payum\Core\Action\ActionInterface;
use Payum\Core\ApiAwareInterface;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\Exception\UnsupportedApiException;
use Sylius\Component\Core\Model\PaymentInterface as SyliusPaymentInterface;
use Payum\Core\Request\Capture;

final class CaptureAction implements ActionInterface, ApiAwareInterface
{
    /** @var Client */
    private $client;

    /** @var PayfipApi */
    private $api;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function execute($request): void
    {
        RequestNotSupportedException::assertSupports($this, $request);

        /** @var SyliusPaymentInterface $payment */
        $payment = $request->getModel();


        $client = new SoapClient('https://www.payfip.gouv.fr/tpa/services/mas_securite/contrat_paiement_securise/PaiementSecuriseService?wsdl');
        dump($client->__getFunctions());
        dump($client->__getTypes());
        die;
        try {
            $response = $this->client->request('POST', 'https://sylius-payment.free.beeceptor.com', [
                'body' => json_encode([
                    'price' => $payment->getAmount(),
                    'currency' => $payment->getCurrencyCode(),
                    'api_key' => $this->api->getClientNumber(),
                ]),
            ]);
        } catch (RequestException $exception) {
            $response = $exception->getResponse();
        } finally {
            $payment->setDetails(['status' => $response->getStatusCode()]);
        }
        echo '<pre>';
        var_dump($response);
        die;
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
}