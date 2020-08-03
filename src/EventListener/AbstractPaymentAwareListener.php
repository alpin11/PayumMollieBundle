<?php


namespace CoreShop\Payum\MollieBundle\EventListener;


use CoreShop\Component\Core\Model\OrderInterface;
use CoreShop\Component\Core\Model\OrderItemInterface;
use CoreShop\Component\Order\Model\OrderDocumentItemInterface;
use CoreShop\Component\Order\Model\OrderPaymentInterface;
use CoreShop\Component\Payment\Model\PaymentInterface;
use CoreShop\Component\Payment\Repository\PaymentRepositoryInterface;

abstract class AbstractPaymentAwareListener
{
    /**
     * @var PaymentRepositoryInterface
     */
    protected $paymentRepository;

    public function __construct(PaymentRepositoryInterface $paymentRepository)
    {
        $this->paymentRepository = $paymentRepository;
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

    /**
     * @param OrderDocumentItemInterface $orderDocumentItem
     *
     * @return int|null
     */
    protected function resolverOrderItemId(OrderDocumentItemInterface $orderDocumentItem)
    {
        return $orderDocumentItem->getOrderItem() instanceof OrderItemInterface ? $orderDocumentItem->getOrderItem()->getId() : null;
    }
}