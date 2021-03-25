<?php

/**
 * This Software is the property of OXID eSales and is protected
 * by copyright law - it is NOT Freeware.
 *
 * Any unauthorized use of this software without a valid license key
 * is a violation of the license agreement and will be prosecuted by
 * civil and criminal law.
 *
 * @category      module
 * @author        OXID Professional services
 * @link          http://www.oxid-esales.com
 * @copyright (C) OXID eSales AG 2003-2020
 */

namespace OxidProfessionalServices\ModulesConfig\Controller\Admin;

/**
 * ShopMain.
 */
class ShopMain extends ShopMain_parent
{
    /**
     * Check if Shop can be created.
     *
     * @param string                                   $shopId
     * @param \OxidEsales\Eshop\Application\Model\Shop $shop
     *
     * @return bool
     */
    public function checkCreateShop($shopId, $shop)
    {
        return $this->canCreateShop($shopId, $shop);
    }

    /**
     * Update shop information in DB and Config.
     *
     * @param \OxidEsales\Eshop\Core\Config            $config
     * @param \OxidEsales\Eshop\Application\Model\Shop $shop
     * @param string                                   $shopId
     */
    public function updateShopInfo($config, $shop, $shopId)
    {
        $this->updateShopInformation($config, $shop, $shopId);
    }
}
