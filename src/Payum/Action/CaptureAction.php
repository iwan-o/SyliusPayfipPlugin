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

    const PAYFIP_URL = 'https://www.payfip.gouv.fr/tpa/paiement.web?idop=cc0cb210-1cd4-11ea-8cca-0213ad91a10';

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function execute($request): void
    {
        RequestNotSupportedException::assertSupports($this, $request);

        /** @var SyliusPaymentInterface $payment */
        $payment = $request->getModel();

        $options = [
            'soap_version'=>  SOAP_1_1, 
            'exceptions'=> true, 
            'trace'=> 1, 
            'cache_wsdl'=> WSDL_CACHE_NONE,
        ];

        $client = new \SoapClient('https://www.tipi.budget.gouv.fr/tpa/services/mas_securite/contrat_paiement_securise/PaiementSecuriseService?wsdl', $options);

        /*
        $functions = $client->__getFunctions();
        $types = $client->__getTypes();

        echo "FUNCTIONS<pre>";
        print_r($functions);
        echo "</pre>";
        
        echo "TYPE<pre>";
        print_r($types);
        echo "</pre>";
        */

        $data = [
            new \SoapParam('2021', 'exer'),
            new \SoapParam('test@mon-site.com', 'mel'),
            new \SoapParam('6000', 'montant'),
            new \SoapParam('049836', 'numcli'),
            new \SoapParam('Commande du mois de juin', 'objet'),
            new \SoapParam('#000001', 'refdet'),
            new \SoapParam('T', 'saisie'),
            new \SoapParam('http://mon-site.com/notification', 'urlnotif'),
            new \SoapParam('http://mon-site.com/redirection-usager', 'urlredirect'),
            /*
            'exer'=> '2021',
            'mel'=> 'test@mon-site.com',
            'montant'=> '6000',
            'numcli'=> '049836',
            'objet'=> 'Commande du mois de juin',
            'refdet'=> '#000001',
            'saisie'=> 'T',
            'urlnotif'=> 'http://mon-site.com/notification',
            'urlredirect'=> 'http://mon-site.com/redirection-usager',
            */

        ];

        $xml ='<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:pai="http://securite.service.tpa.cp.finances.gouv.fr/services/mas_securite/contrat_paiement_securise/PaiementSecuriseService">';
        $xml .='
    <soapenv:Header/>
    <soapenv:Body>
        <pai:creerPaiementSecurise>
            <arg0>
                <exer>2021</exer>
                <mel>test@mon-site.com</mel>
                <montant>6000</montant>
                <numcli>049836</numcli>
                <objet>Commande du mois de juin</objet>
                <refdet>000001</refdet>
                <saisie>T</saisie>
                <urlnotif>http://mon-site.com/notification</urlnotif>
                <urlredirect>http://mon-site.com/redirection-usager</urlredirect>
            </arg0>
        </pai:creerPaiementSecurise>
    </soapenv:Body>
</soapenv:Envelope>';

$data = new \SoapVar($xml, \XSD_ANYXML);

        $response = $client->__soapCall('creerPaiementSecurise', [$data]);
        
        try {
            $response = $client->__soapCall('creerPaiementSecurise', [$data]);
        } catch (\SoapFault $exception) {
            echo $exception->getMessage(); echo '<br>';
            echo $exception->getCode(); echo '<br>';
            echo $client->__getLastRequest(); echo '<br>';
            var_dump($exception->getTraceAsString());
        }

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