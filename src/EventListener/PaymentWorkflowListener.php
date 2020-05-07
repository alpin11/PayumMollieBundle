<?php


namespace CoreShop\Payum\MollieBundle\EventListener;

use CoreShop\Bundle\PayumBundle\Factory\GetStatusFactoryInterface;
use CoreShop\Bundle\PayumBundle\Model\PaymentSecurityToken;
use CoreShop\Component\Payment\Model\PaymentInterface;
use CoreShop\Payum\MollieBundle\Factory\RefundArbitraryAmountFactoryInterface;
use Payum\Core\Registry\RegistryInterface;
use Payum\Core\Storage\StorageInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Workflow\Event\Event;

class PaymentWorkflowListener implements EventSubscriberInterface
{

    /**
     * @var RefundArbitraryAmountFactoryInterface
     */
    private $refundArbitraryAmountFactory;
    /**
     * @var GetStatusFactoryInterface
     */
    private $getStatusRequestFactory;
    /**
     * @var RegistryInterface
     */
    private $payum;
    /**
     * @var StorageInterface
     */
    private $tokenStorage;

    public function __construct(
        RegistryInterface $payum,
        StorageInterface $tokenStorage,
        RefundArbitraryAmountFactoryInterface $refundArbitraryAmountFactory,
        GetStatusFactoryInterface $getStatusRequestFactory
    ) {
        $this->refundArbitraryAmountFactory = $refundArbitraryAmountFactory;
        $this->getStatusRequestFactory = $getStatusRequestFactory;
        $this->payum = $payum;
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents()
    {
        return [
            'workflow.coreshop_payment.enter.refund' => 'onRefund'
        ];
    }

    /**
     * @param Event $event
     */
    public function onRefund(Event $event)
    {
        $payment = $event->getSubject();

        if (!$payment instanceof PaymentInterface) {
            return;
        }

        /** @var PaymentSecurityToken $token */
        $token = $this->tokenStorage->find($payment->getId());

        $refundArbitraryAmount = $this->refundArbitraryAmountFactory->createNewWithModel($payment, $payment->getTotalAmount());
        $this->payum->getGateway($token->getGatewayName())->execute($refundArbitraryAmount);

        $getStatus = $this->getStatusRequestFactory->createNewWithModel($payment);
        $this->payum->getGateway($token->getGatewayName())->execute($getStatus);
    }
}
