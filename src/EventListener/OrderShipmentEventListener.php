<?php


namespace CoreShop\Payum\MollieBundle\EventListener;

use CoreShop\Bundle\PayumBundle\Factory\GetStatusFactoryInterface;
use CoreShop\Component\Core\Model\CarrierInterface;
use CoreShop\Component\Core\Model\OrderInterface;
use CoreShop\Component\Core\Model\OrderItemInterface;
use CoreShop\Component\Core\Model\OrderShipmentInterface;
use CoreShop\Component\Core\Model\PaymentProviderInterface;
use CoreShop\Component\Order\Model\OrderPaymentInterface;
use CoreShop\Component\Payment\Model\PaymentInterface;
use CoreShop\Component\Payment\Repository\PaymentRepositoryInterface;
use CoreShop\Payum\MollieBundle\Factory\CreateShipmentFactoryInterface;
use Payum\Core\Registry\RegistryInterface;
use Payum\Core\Security\TokenInterface;
use Payum\Core\Storage\StorageInterface;
use Pimcore\Logger;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Workflow\Event\Event;

class OrderShipmentEventListener
{
    /**
     * @var PaymentRepositoryInterface
     */
    protected $paymentRepository;
    /**
     * @var CreateShipmentFactoryInterface
     */
    protected $createShipmentFactory;
    /**
     * @var RegistryInterface
     */
    protected $payum;
    /**
     * @var GetStatusFactoryInterface
     */
    protected $getStatusFactory;

    public function __construct(
        PaymentRepositoryInterface $paymentRepository,
        CreateShipmentFactoryInterface $createShipmentFactory,
        RegistryInterface $payum,
        GetStatusFactoryInterface $getStatusFactory
    ) {
        $this->paymentRepository = $paymentRepository;
        $this->createShipmentFactory = $createShipmentFactory;
        $this->payum = $payum;
        $this->getStatusFactory = $getStatusFactory;
    }

    /**
     * @param Event $event
     */
    public function onEnterShipped(Event $event)
    {
        $object = $event->getSubject();

        if (!$object instanceof OrderShipmentInterface) {
            return;
        }

        $order = $object->getOrder();

        if (!$object instanceof OrderInterface) {
            return;
        }

        $lines = [];

        foreach ($object->getItems() as $orderShipmentItem) {
            if (!($orderItem = $orderShipmentItem->getOrderItem()) instanceof OrderItemInterface) {
                continue;
            }

            $lines[] = [
                'orderItemId' => $orderItem->getId(),
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

        $createShipmentRequest = $this->createShipmentFactory->createNewWithModel($payment, $lines, $tracking);
        $this->payum->getGateway($paymentProvider->getGatewayConfig()->getGatewayName())->execute($createShipmentRequest);

        $getStatusRequest = $this->getStatusFactory->createNewWithModel($payment);
        $this->payum->getGateway($paymentProvider->getGatewayConfig()->getGatewayName())->execute($getStatusRequest);
    }

    /**
     * @param OrderInterface $order
     *
     * @return PaymentInterface|null
     */
    protected function findFirstValidPayment(OrderInterface $order)
    {
        foreach ($this->paymentRepository->findForPayable($order) as $payment) {
            if (in_array($payment->getState(), [OrderPaymentInterface::STATE_AUTHORIZED, OrderPaymentInterface::STATE_COMPLETED], true)) {
                return $payment;
            }
        }

        return null;
    }
}
