<?php


namespace CoreShop\Payum\MollieBundle\EventListener;


use CoreShop\Component\Core\Model\OrderInterface;
use CoreShop\Component\Core\Model\OrderItemInterface;
use CoreShop\Component\Order\Model\OrderDocumentItemInterface;
use CoreShop\Component\Payment\Model\PaymentInterface;
use CoreShop\Component\Payment\Repository\PaymentRepositoryInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

abstract class AbstractPaymentAwareListener implements EventSubscriberInterface
{
    public function __construct(protected PaymentRepositoryInterface $paymentRepository)
    {

    }

    /**
     * @param OrderInterface $order
     *
     * @return PaymentInterface|null
     */
    protected function findFirstValidPayment(OrderInterface $order): ?PaymentInterface
    {
        foreach ($this->paymentRepository->findForPayable($order) as $payment) {
            if (in_array($payment->getState(), [PaymentInterface::STATE_AUTHORIZED, PaymentInterface::STATE_COMPLETED], true)) {
                return $payment;
            }
        }

        return null;
    }

    /**
     * @param OrderDocumentItemInterface $orderDocumentItem
     *
     * @return int|null
     */
    protected function resolverOrderItemId(OrderDocumentItemInterface $orderDocumentItem): ?int
    {
        return $orderDocumentItem->getOrderItem() instanceof OrderItemInterface ? $orderDocumentItem->getOrderItem()->getId() : null;
    }
}