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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\CartManager;

use Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\CartPriceModificator\ICartPriceModificator;
use Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\IModificatedPrice;

/**
 * Interface ICartPriceCalculator
 */
interface ICartPriceCalculator
{
    public function __construct($config, ICart $cart);

    /**
     * calculates cart sums and saves results
     *
     * @return void
     */
    public function calculate();

    /**
     * reset calculations
     *
     * @return void
     */
    public function reset();

    /**
     * returns sub total of cart
     *
     * @return \Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\IPrice $price
     */
    public function getSubTotal();

    /**
     * returns all price modifications which apply for this cart
     *
     * @return IModificatedPrice[] $priceModification
     */
    public function getPriceModifications();

    /**
     * returns grand total of cart
     *
     * @return \Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\IPrice $price
     */
    public function getGrandTotal();

    /**
     * manually add a modificator to this cart. by default they are loaded from the configuration
     *
     * @param ICartPriceModificator $modificator
     *
     * @return ICartPriceCalculator
     */
    public function addModificator(ICartPriceModificator $modificator);

    /**
     * returns all modificators
     *
     * @return \Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\CartPriceModificator\ICartPriceModificator[]
     */
    public function getModificators();

    /**
     * manually remove a modificator from this cart.
     *
     * @param ICartPriceModificator $modificator
     *
     * @return ICartPriceCalculator
     */
    public function removeModificator(ICartPriceModificator $modificator);
}
