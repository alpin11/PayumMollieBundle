<?php


namespace CoreShop\Payum\MollieBundle\EventListener;

use CoreShop\Bundle\PayumBundle\Factory\GetStatusFactoryInterface;
use CoreShop\Bundle\RefundBundle\Model\CreditMemoInterface;
use CoreShop\Component\Core\Model\OrderInterface;
use CoreShop\Component\Core\Model\PaymentInterface;
use CoreShop\Component\Core\Model\PaymentProviderInterface;
use CoreShop\Component\Payment\Repository\PaymentRepositoryInterface;
use CoreShop\Payum\MollieBundle\Factory\RefundOrderLinesFactoryInterface;
use Payum\Core\Registry\RegistryInterface;
use Payum\Core\Security\TokenInterface;
use Payum\Core\Storage\StorageInterface;
use Pimcore\Logger;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Workflow\Event\Event;

class CreditMemoListener extends AbstractPaymentAwareListener implements EventSubscriberInterface
{
    /**
     * @var RefundOrderLinesFactoryInterface
     */
    protected $refundOrderLinesFactory;
    /**
     * @var RegistryInterface
     */
    protected $payum;
    /**
     * @var GetStatusFactoryInterface
     */
    protected $getStatusRequestFactory;

    public function __construct(
        RefundOrderLinesFactoryInterface $refundOrderLinesFactory,
        PaymentRepositoryInterface $paymentRepository,
        RegistryInterface $payum,
        GetStatusFactoryInterface $getStatusRequestFactory
    )
    {
        $this->refundOrderLinesFactory = $refundOrderLinesFactory;
        $this->payum = $payum;
        $this->getStatusRequestFactory = $getStatusRequestFactory;

        parent::__construct($paymentRepository);
    }

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents()
    {
        return [
            'workflow.coreshop_credit_memo.enter.complete' => ['onEnterComplete']
        ];
    }

    /**
     * @param Event $event
     */
    public function onEnterComplete(Event $event)
    {
        $creditMemo = $event->getSubject();

        if (!$creditMemo instanceof CreditMemoInterface) {
            return;
        }

        $order = $creditMemo->getOrder();

        if (!$order instanceof OrderInterface) {
            Logger::log('Not able to refund credit memo without related order');

            return;
        }

        $payment = $this->findFirstValidPayment($order);

        if (!$payment instanceof PaymentInterface) {
            Logger::log('found no valid payment');
            return;
        }

        $itemsToRefund = [];

        foreach ($creditMemo->getItems() as $item) {
            $orderItemId = $this->resolverOrderItemId($item);

            if (null == $orderItemId) {
                Logger::info('Not able to find order item id for credit memo', [
                    'shipment' => $item
                ]);

                continue;
            }

            $lines[] = [
                'orderItemId' => $orderItemId,
                'quantity' => $item->getQuantity()
            ];
        }

        /** @var PaymentProviderInterface $paymentProvider */
        $paymentProvider = $payment->getPaymentProvider();

        if (!$paymentProvider instanceof PaymentProviderInterface) {
            Logger::log('Not able to determine the gateway without payment provider');

            return;
        }

        $refundOrderLines = $this->refundOrderLinesFactory->createNewWithModel($payment, $itemsToRefund);
        $this->payum->getGateway($paymentProvider->getGatewayConfig()->getGatewayName())->execute($refundOrderLines);

        $getStatus = $this->getStatusRequestFactory->createNewWithModel($payment);
        $this->payum->getGateway($paymentProvider->getGatewayConfig()->getGatewayName())->execute($getStatus);
    }
}
