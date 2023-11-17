<?php


namespace CoreShop\Payum\MollieBundle\EventListener;

use CoreShop\Bundle\PayumBundle\Factory\GetStatusFactoryInterface;
use CoreShop\Component\Core\Model\CarrierInterface;
use CoreShop\Component\Core\Model\OrderInterface;
use CoreShop\Component\Core\Model\OrderShipmentInterface;
use CoreShop\Component\Core\Model\PaymentProviderInterface;
use CoreShop\Component\Payment\Model\PaymentInterface;
use CoreShop\Component\Payment\Repository\PaymentRepositoryInterface;
use CoreShop\Payum\MollieBundle\Factory\CreateShipmentFactoryInterface;
use Payum\Core\Model\GatewayConfigInterface;
use Payum\Core\Payum;
use Pimcore\Logger;
use Symfony\Component\Workflow\Event\Event;

class OrderShipmentListener extends AbstractPaymentAwareListener
{


    public function __construct(
        PaymentRepositoryInterface               $paymentRepository,
        protected CreateShipmentFactoryInterface $createShipmentFactory,
        protected Payum                          $payum,
        protected GetStatusFactoryInterface      $getStatusFactory
    )
    {
        parent::__construct($paymentRepository);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'workflow.coreshop_shipment.enter.shipped' => 'onEnterShipped'
        ];
    }

    /**
     * @param Event $event
     */
    public function onEnterShipped(Event $event): void
    {
        $object = $event->getSubject();

        if (!$object instanceof OrderShipmentInterface) {
            return;
        }

        $order = $object->getOrder();

        if (!$order instanceof OrderInterface) {
            return;
        }

        $lines = [];

        foreach ($object->getItems() as $orderShipmentItem) {
            $orderItemId = $this->resolverOrderItemId($orderShipmentItem);

            if (null == $orderItemId) {
                Logger::info('Not able to find order item id for shipment', [
                    'shipment' => $orderShipmentItem
                ]);

                continue;
            }

            $lines[] = [
                'orderItemId' => $orderItemId,
                'quantity' => $orderShipmentItem->getQuantity()
            ];
        }

        $tracking = [];

        if ($object->getCarrier() instanceof CarrierInterface && !empty($object->getTrackingCode())) {
            $tracking = [
                'carrier' => $object->getCarrier()->getTitle(),
                'code' => $object->getTrackingCode()
            ];

            if ($object->getCarrier()->getTrackingUrl()) {
                $tracking['url'] = $object->getCarrier()->getTrackingUrl() . $object->getTrackingCode();
            }
        }

        $payment = $this->findFirstValidPayment($order);

        if (!$payment instanceof PaymentInterface) {
            Logger::err('could not find valid payment for order.', [
                'order' => $order,
                'shipment' => $object
            ]);

            return;
        }

        /** @var PaymentProviderInterface $paymentProvider */
        $paymentProvider = $payment->getPaymentProvider();

        if (!$paymentProvider instanceof PaymentProviderInterface) {
            Logger::log('Not able to determine the gateway without payment provider');

            return;
        }

        if (!$paymentProvider->getGatewayConfig() instanceof GatewayConfigInterface) {
            return;
        }

        if ($paymentProvider->getGatewayConfig()->getFactoryName() != 'mollie') {
            Logger::info("not a mollie payment. skipping actions.");

            return;
        }

        $createShipmentRequest = $this->createShipmentFactory->createNewWithModel($payment, $lines, $tracking);
        $this->payum->getGateway($paymentProvider->getGatewayConfig()->getGatewayName())->execute($createShipmentRequest);

        $getStatusRequest = $this->getStatusFactory->createNewWithModel($payment);
        $this->payum->getGateway($paymentProvider->getGatewayConfig()->getGatewayName())->execute($getStatusRequest);
    }
}
