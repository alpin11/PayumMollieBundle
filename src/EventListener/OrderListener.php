<?php


namespace CoreShop\Payum\MollieBundle\EventListener;


use CoreShop\Bundle\PayumBundle\Factory\GetStatusFactoryInterface;
use CoreShop\Bundle\PayumBundle\Model\GatewayConfig;
use CoreShop\Component\Core\Model\OrderInterface;
use CoreShop\Component\Core\Model\PaymentProviderInterface;
use CoreShop\Component\Payment\Model\PaymentInterface;
use CoreShop\Component\Payment\Repository\PaymentRepositoryInterface;
use CoreShop\Payum\MollieBundle\Factory\ShipAllFactoryInterface;
use Payum\Core\Registry\RegistryInterface;
use Pimcore\Logger;
use Symfony\Component\Workflow\Event\Event;

class OrderListener extends AbstractPaymentAwareListener
{

    /**
     * @var ShipAllFactoryInterface
     */
    private $shipAllFactory;
    /**
     * @var RegistryInterface
     */
    private $payum;
    /**
     * @var GetStatusFactoryInterface
     */
    private $getStatusFactory;

    public function __construct(
        PaymentRepositoryInterface $paymentRepository,
        ShipAllFactoryInterface $shipAllFactory,
        RegistryInterface $payum,
        GetStatusFactoryInterface $getStatusFactory
    )
    {
        $this->shipAllFactory = $shipAllFactory;
        $this->payum = $payum;
        $this->getStatusFactory = $getStatusFactory;

        parent::__construct($paymentRepository);
    }

    /**
     * @param Event $event
     */
    public function onShipped(Event $event)
    {
        $object = $event->getSubject();

        if (!$object instanceof OrderInterface) {
            return;
        }

        $payment = $this->findFirstValidPayment($object);

        if (!$payment instanceof PaymentInterface) {
            Logger::err('could not find valid payment for order.', [
                'order' => $object
            ]);

            return;
        }

        /** @var PaymentProviderInterface $paymentProvider */
        $paymentProvider = $payment->getPaymentProvider();

        if (!$paymentProvider instanceof PaymentProviderInterface) {
            Logger::log('Not able to determine the gateway without payment provider');

            return;
        }

        if (!$paymentProvider->getGatewayConfig() instanceof GatewayConfig) {
            return;
        }

        if ($paymentProvider->getGatewayConfig()->getFactoryName() != 'mollie') {
            Logger::info("not a mollie payment. skipping actions.");

            return;
        }

        $createShipmentRequest = $this->shipAllFactory->createNewWithModel($payment);
        $this->payum->getGateway($paymentProvider->getGatewayConfig()->getGatewayName())->execute($createShipmentRequest);

        $getStatusRequest = $this->getStatusFactory->createNewWithModel($payment);
        $this->payum->getGateway($paymentProvider->getGatewayConfig()->getGatewayName())->execute($getStatusRequest);
    }
}