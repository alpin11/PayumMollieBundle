<?php


namespace CoreShop\Payum\MollieBundle\EventListener;

use CoreShop\Bundle\PayumBundle\Factory\GetStatusFactoryInterface;
use CoreShop\Bundle\RefundBundle\Model\CreditMemoInterface;
use CoreShop\Component\Core\Model\OrderInterface;
use CoreShop\Component\Core\Model\PaymentInterface;
use CoreShop\Component\Payment\Repository\PaymentRepositoryInterface;
use CoreShop\Payum\MollieBundle\Factory\RefundOrderLinesFactoryInterface;
use Payum\Core\Registry\RegistryInterface;
use Payum\Core\Security\TokenInterface;
use Payum\Core\Storage\StorageInterface;
use Pimcore\Logger;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Workflow\Event\Event;

class CreditMemoListener implements EventSubscriberInterface
{

    /**
     * @var RefundOrderLinesFactoryInterface
     */
    private $refundOrderLinesFactory;
    /**
     * @var PaymentRepositoryInterface
     */
    private $paymentRepository;
    /**
     * @var RegistryInterface
     */
    private $payum;
    /**
     * @var StorageInterface
     */
    private $tokenStorage;
    /**
     * @var GetStatusFactoryInterface
     */
    private $getStatusRequestFactory;

    public function __construct(
        RefundOrderLinesFactoryInterface $refundOrderLinesFactory,
        PaymentRepositoryInterface $paymentRepository,
        RegistryInterface $payum,
        StorageInterface $tokenStorage,
        GetStatusFactoryInterface $getStatusRequestFactory
    ) {
        $this->refundOrderLinesFactory = $refundOrderLinesFactory;
        $this->paymentRepository = $paymentRepository;
        $this->payum = $payum;
        $this->tokenStorage = $tokenStorage;
        $this->getStatusRequestFactory = $getStatusRequestFactory;
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

        $payment = $this->getFirstValidPayment($order);

        if (!$payment instanceof PaymentInterface) {
            return;
        }

        $itemsToRefund = [];

        foreach ($creditMemo->getItems() as $item) {
            $itemsToRefund[] = [
                'orderItemId' => $item->getOrderItem()->getId(),
                'quantity' => $item->getQuantity()
            ];
        }

        /** @var TokenInterface $paymentToken */
        $paymentToken = $this->tokenStorage->find($payment);

        if (!$paymentToken instanceof TokenInterface) {
            Logger::log('Not able to determine the gateway without a payment security token');

            return;
        }

        $refundOrderLines = $this->refundOrderLinesFactory->createNewWithModel($payment, $itemsToRefund);
        $this->payum->getGateway($paymentToken->getGatewayName())->execute($refundOrderLines);

        $getStatus = $this->getStatusRequestFactory->createNewWithModel($payment);
        $this->payum->getGateway($paymentToken->getGatewayName())->execute($getStatus);
    }

    /**
     * @param OrderInterface $order
     *
     * @return PaymentInterface|null
     */
    private function getFirstValidPayment(OrderInterface $order)
    {
        $payments = $this->paymentRepository->findForPayable($order);

        foreach ($payments as $payment) {
            if (in_array($payment->getState(), [PaymentInterface::STATE_AUTHORIZED, PaymentInterface::STATE_COMPLETED], true)) {
                return $payment;
            }
        }

        return null;
    }
}
