<?php
/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\Condition;

class Sold extends AbstractOrder implements \Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\ICondition
{
    /**
     * @var int
     */
    protected $count;

    /**
     * @var int[]
     */
    protected $currentSoldCount = [];

    /**
     * @var bool
     */
    protected $countCart = false;

    /**
     * @param \Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\IEnvironment $environment
     *
     * @return bool
     */
    public function check(\Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\IEnvironment $environment)
    {
        $rule = $environment->getRule();
        if ($rule) {
            $cartUsedCount = 0;

            if ($this->isCountCart()) {
                if ($environment->getCart() && $environment->getCartItem()) {
                    // cart view
                    $cartUsedCount = $this->getCartRuleCount($environment->getCart(), $rule, $environment->getCartItem());
                } elseif (!$environment->getCart()) {
                    // product view
                    $cart = $this->getCart();
                    $cartUsedCount = $this->getCartRuleCount($cart, $rule);
                }
            }

            return ($this->getSoldCount($rule) + $cartUsedCount) < $this->getCount();
        } else {
            return false;
        }
    }

    /**
     * @return string
     */
    public function toJSON()
    {
        // basic
        $json = [
            'type' => 'Sold', 'count' => $this->getCount(), 'countCart' => $this->isCountCart()
        ];

        return json_encode($json);
    }

    /**
     * @param string $string
     *
     * @return \Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\ICondition
     */
    public function fromJSON($string)
    {
        $json = json_decode($string);

        $this->setCount($json->count);
        $this->setCountCart((bool)$json->countCart);

        return $this;
    }

    /**
     * @return int
     */
    public function getCount()
    {
        return $this->count;
    }

    /**
     * @param int $count
     */
    public function setCount($count)
    {
        $this->count = (int)$count;
    }

    /**
     * @return bool
     */
    public function isCountCart()
    {
        return $this->countCart;
    }

    /**
     * @param bool $countCart
     *
     * @return $this
     */
    public function setCountCart($countCart)
    {
        $this->countCart = (bool)$countCart;

        return $this;
    }

    /**
     * @return \Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\ICart|null
     */
    protected function getCart()
    {
        // use this in your own implementation
    }

    /**
     * return a count how often the rule is already uses in the cart
     *
     * @param \Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\ICart          $cart
     * @param \Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\IRule  $rule
     * @param \Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\ICartItem|null $cartItem
     *
     * @return int
     */
    protected function getCartRuleCount(\Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\ICart $cart, \Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\IRule $rule, \Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\ICartItem $cartItem = null)
    {
        // init
        $counter = 0;

        foreach ($cart->getItems() as $item) {
            $rules = [];

            if ($cartItem && $item->getItemKey() == $cartItem) {
                // skip self if we are on a cartItem
            } else {
                // get rules
                $priceInfo = $item->getPriceInfo();
                if ($priceInfo instanceof \Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\IPriceInfo) {
                    if (($cartItem && $priceInfo->hasRulesApplied()) || $cartItem === null) {
                        $rules = $priceInfo->getRules();
                    }
                }
            }

            // search for current rule
            foreach ($rules as $r) {
                if ($r->getId() == $rule->getId()) {
                    $counter++;
                    break;
                }
            }
        }

        return $counter;
    }
}
