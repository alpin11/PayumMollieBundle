<?php

namespace CoreShop\Payum\MollieBundle\Provider;

use CoreShop\Component\Core\Model\OrderInterface;
use CoreShop\Component\Order\Model\AdjustmentInterface;
use CoreShop\Component\Order\Model\CartPriceRuleInterface;
use CoreShop\Component\Order\Model\ProposalCartPriceRuleItemInterface;
use CoreShop\Component\Payment\Model\PaymentInterface;
use Mollie\Api\Types\OrderLineType;

class PriceRulesProvider extends AbstractProvider
{
    public function getPriceRulesDetails(OrderInterface $order, PaymentInterface $payment): array
    {
        $lineItems = [];

        if ($order->hasPriceRules()) {
            foreach ($order->getPriceRuleItems()->getItems() as $priceRuleItem) {
                $priceRuleItemTransformed = $this->transformPriceRuleItemToLineItem($priceRuleItem, $payment->getCurrencyCode(), $order->getLocaleCode());

                if ($priceRuleItemTransformed === false) {
                    continue;
                }

                $lineItems[] = $priceRuleItemTransformed;
            }
        }

        return $lineItems;
    }

    protected function transformPriceRuleItemToLineItem(ProposalCartPriceRuleItemInterface $priceRuleItem, string $currencyCode, string $localeCode): bool|array
    {
        $discountGross = $priceRuleItem->getDiscount();
        $discountNet = $priceRuleItem->getDiscount(false);

        if ($discountNet == 0) {
            return false;
        }

        $vatAmount = $discountGross - $discountNet;
        $vatRate = round(($vatAmount / $discountNet) * 100);

        return [
            'type' => OrderLineType::TYPE_DISCOUNT,
            'name' => empty($priceRuleItem->getVoucherCode()) ? $priceRuleItem->getCartPriceRule()->getLabel($localeCode) : sprintf("%s voucher code", $priceRuleItem->getVoucherCode()),
            'quantity' => 1,
            'unitPrice' => $this->transformMoneyWithCurrency($discountGross, $currencyCode),
            'totalAmount' => $this->transformMoneyWithCurrency($discountGross, $currencyCode),
            'vatAmount' => $this->transformMoneyWithCurrency($vatAmount, $currencyCode),
            'vatRate' => sprintf("%01.2f", $vatRate),
            'metadata' => json_encode([
                'voucherCode' => $priceRuleItem->getVoucherCode(),
                'cartPriceRulePimcoreId' => $priceRuleItem->getCartPriceRule() instanceof CartPriceRuleInterface ? $priceRuleItem->getCartPriceRule()->getId() : null,
                'typeIdentifier' => AdjustmentInterface::CART_PRICE_RULE
            ])
        ];
    }
}