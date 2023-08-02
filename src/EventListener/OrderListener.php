<?php


namespace CoreShop\Payum\MollieBundle\EventListener;


use CoreShop\Bundle\PayumBundle\Factory\GetStatusFactoryInterface;
use CoreShop\Component\Core\Model\OrderInterface;
use CoreShop\Component\Core\Model\PaymentProviderInterface;
use CoreShop\Component\Payment\Model\PaymentInterface;
use CoreShop\Component\Payment\Repository\PaymentRepositoryInterface;
use CoreShop\Payum\MollieBundle\Factory\ShipAllFactoryInterface;
use Payum\Core\Model\GatewayConfigInterface;
use Payum\Core\Payum;
use Pimcore\Logger;
use Symfony\Component\Workflow\Event\Event;

class OrderListener extends AbstractPaymentAwareListener
{
    public function __construct(
        PaymentRepositoryInterface          $paymentRepository,
        protected ShipAllFactoryInterface   $shipAllFactory,
        protected Payum                     $payum,
        protected GetStatusFactoryInterface $getStatusFactory
    )
    {
        parent::__construct($paymentRepository);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'workflow.coreshop_order_shipment.enter.shipped' => 'onShipped'
        ];
    }

    public function onShipped(Event $event): void
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
            Logger::warn('Not able to determine the gateway without payment provider');

            return;
        }

        if (!$paymentProvider->getGatewayConfig() instanceof GatewayConfigInterface) {
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