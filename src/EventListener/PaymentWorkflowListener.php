<?php


namespace CoreShop\Payum\MollieBundle\EventListener;

use CoreShop\Bundle\PayumBundle\Factory\GetStatusFactoryInterface;
use CoreShop\Bundle\PayumBundle\Model\PaymentSecurityToken;
use CoreShop\Component\Core\Model\PaymentProviderInterface;
use CoreShop\Component\Payment\Model\PaymentInterface;
use CoreShop\Payum\MollieBundle\Factory\RefundArbitraryAmountFactoryInterface;
use Payum\Core\Registry\RegistryInterface;
use Payum\Core\Storage\StorageInterface;
use Pimcore\Logger;
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


    public function __construct(
        RegistryInterface $payum,
        RefundArbitraryAmountFactoryInterface $refundArbitraryAmountFactory,
        GetStatusFactoryInterface $getStatusRequestFactory
    )
    {
        $this->refundArbitraryAmountFactory = $refundArbitraryAmountFactory;
        $this->getStatusRequestFactory = $getStatusRequestFactory;
        $this->payum = $payum;
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


        /** @var PaymentProviderInterface $paymentProvider */
        $paymentProvider = $payment->getPaymentProvider();

        if (!$paymentProvider instanceof PaymentProviderInterface) {
            Logger::log('Not able to determine the gateway without payment provider');

            return;
        }

        $refundArbitraryAmount = $this->refundArbitraryAmountFactory->createNewWithModel($payment, $payment->getTotalAmount());
        $this->payum->getGateway($paymentProvider->getGatewayConfig()->getGatewayName())->execute($refundArbitraryAmount);

        $getStatus = $this->getStatusRequestFactory->createNewWithModel($payment);
        $this->payum->getGateway($paymentProvider->getGatewayConfig()->getGatewayName())->execute($getStatus);
    }
}
