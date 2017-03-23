<?php

class Forsaaa_Reseller_Helper_Data extends Mage_Core_Helper_Abstract
{

    /**
     * Array of available orders to be used for sort by
     *
     * @return array
     */
    public function getAvailableOrders()
    {
        return array(
            // attribute name => label to be used
            'price' => $this->__('Price')
        );
    }

    /**
     * Return product collection to be displayed by our list block
     *
     * @return Mage_Catalog_Model_Resource_Product_Collection
     */
    public function getProductCollection()
    {
        if (Mage::app()->getRequest()->getParam('categoryFilter')) {
            if (Mage::app()->getRequest()->getParam('categoryFilter') !== "undefined") {
                $rootCategoryId = Mage::app()->getRequest()->getParam('categoryFilter');
            } else {
                $rootCategoryId = Mage::app()->getStore()->getRootCategoryId();
            }
        } else {
            $rootCategoryId = Mage::app()->getStore()->getRootCategoryId();
        }

        if (Mage::app()->getRequest()->getParam('searchFilter')) {
            $searchFilter = Mage::app()->getRequest()->getParam('searchFilter');
            $searchBy = Mage::app()->getRequest()->getParam('searchby');
//            $rootCategoryId = Mage::app()->getStore()->getRootCategoryId();
        }
        if (Mage::app()->getRequest()->getParam('attributeFilter')) {
            $attributeFilter = Mage::app()->getRequest()->getParam('attributeFilter');
            $attributeCode = Mage::app()->getRequest()->getParam('attributeCode');
        }
        if (Mage::app()->getRequest()->getParam('attributeFilterColor')) {
            $attributeFilterColor = Mage::app()->getRequest()->getParam('attributeFilterColor');
            $attributeCodeColor = Mage::app()->getRequest()->getParam('attributeCodeColor');
        }
        if (Mage::app()->getRequest()->getParam('sortby')) {
            $sortby = Mage::app()->getRequest()->getParam('sortby');
        }
        if ($sortby !== "stock") {
            $collection = Mage::getModel('catalog/category')
                ->load($rootCategoryId)
                ->getProductCollection()
                ->addAttributeToSelect('stock_status')
                ->addAttributeToSelect(Mage::getSingleton('catalog/config')->getProductAttributes())
                ->addMinimalPrice()
                ->addFinalPrice()
                ->addTaxPercents()
                ->addUrlRewrite($rootCategoryId);
            if ($searchFilter) {
                $collection->addFieldToFilter(array(
                    array('attribute' => 'sku', 'like' => '%' . $searchFilter . '%'),
                    array('attribute' => 'name', 'like' => '%' . $searchFilter . '%'),
                ));
            }
            if ($attributeFilter) {
                $attributeFilter = explode(",", $attributeFilter);
                $finalAttr = array();
                foreach ($attributeFilter as $attribute) {
                    $finalAttr[] = array('finset' => array($attribute));
                }
                $collection->addAttributeToFilter($attributeCode, $finalAttr);
            }
            if ($attributeFilterColor) {
                $attributeFilterColor = explode(",", $attributeFilterColor);
                $finalAttr = array();
                foreach ($attributeFilterColor as $attribute) {
                    $finalAttr[] = array('finset' => array($attribute));
                }
                $collection->addAttributeToFilter($attributeCodeColor, $finalAttr);
            }
        }
        if ($sortby) {
            if ($sortby == "latest") {
                $collection->addAttributeToSort('created_at', 'desc');
            } else if ($sortby == "name") {
                $collection->addAttributeToSort("name", 'ASC');
            } else if ($sortby == "stock") {
                $read = Mage::getSingleton('core/resource')->getConnection('core_read');
                $sql = "SELECT `entity_id` FROM `catalog_product_entity_varchar` where `attribute_id` = '233' and `value` = 1";
                $entityid = $read->fetchAll($sql);
                $entityids = array();
                foreach ($entityid as $key) {
                    foreach ($key as $key1 => $value) {

                        $entityids[] = $value;
                    }
                }
                $collection = Mage::getModel('catalog/product')
                    ->getCollection()
                    ->addAttributeToSelect('*')
                    ->addAttributeToFilter('entity_id', array('in' => $entityids))
                    ->joinField('category_id', 'catalog/category_product', 'category_id', 'product_id = entity_id', null, 'left')
                    ->addAttributeToFilter('category_id', array(
                            array('finset' => $rootCategoryId))
                    );
                if ($attributeFilter) {
                    $attributeFilter = explode(",", $attributeFilter);
                    $finalAttr = array();
                    foreach ($attributeFilter as $attribute) {
                        $finalAttr[] = array('finset' => array($attribute));
                    }
                    $collection->addAttributeToFilter($attributeCode, $finalAttr);
                }
                if ($attributeFilterColor) {
                    $attributeFilterColor = explode(",", $attributeFilterColor);
                    $finalAttr = array();
                    foreach ($attributeFilterColor as $attribute) {
                        $finalAttr[] = array('finset' => array($attribute));
                    }
                    $collection->addAttributeToFilter($attributeCodeColor, $finalAttr);
                }
            } else {

            }
        }
        $collection->addFieldToFilter('visibility', Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH);
        Mage::getSingleton('cataloginventory/stock')->addInStockFilterToCollection($collection);
        $collection->setOrder('created_at', 'desc');

        return $collection;
    }

    public function checkResellerIsLoggedIn()
    {
        if (Mage::getSingleton("customer/session")->isLoggedIn()) {
            return true;
        }

        return false;
    }
    public function sendOrderEmail($incrementId)
    {
        $store = Mage::app()->getStore();
        $templateId = Mage::getStoreConfig('sales_email/order/template', $store);
        $email = Mage::getStoreConfig('sales_email/order/copy_to', $store);
        $sender = array(
            'name' => Mage::getStoreConfig('trans_email/ident_support/name', $store),
            'email' => Mage::getStoreConfig('trans_email/ident_support/email', $store)
        );

        $myOrder = Mage::getModel('sales/order')->loadByIncrementId($incrementId);
        $recepientEmail = $myOrder->getCustomerEmail();
        $recepientName = $myOrder->getCustomerName();

        $vars = array(
            'customer' => $recepientName,
            'ordeIds' => $incrementId,
        );
        if ($email) {
            $emailAll = explode(',', $email);
        }
        // Send to all copy to email
        foreach ($emailAll as $eachMail) {
            Mage::getModel('core/email_template')
                ->sendTransactional($templateId, $sender, $eachMail, $eachMail, $vars, $store->getId());
        }
        // Send to customer
        Mage::getModel('core/email_template')
            ->sendTransactional($templateId, $sender, $recepientEmail, $recepientName, $vars, $store->getId());
    }
    public function createResellerOrder($quote, $haveToLoggedIn = true)
    {
        // set $haveToLoggedIn = false if exec with command
        if (!$this->checkResellerIsLoggedIn() && $haveToLoggedIn) {
            return;
        }
        // Split order
        $quoteCollection = $this->splitOrder($quote);
        foreach ($quoteCollection as $_quote) {
            $service = Mage::getModel('sales/service_quote', $_quote);
            try {
                $service->submitAll();
                $incrementId = $service->getOrder()->getRealOrderId();
                $this->sendOrderEmail($incrementId);
                $_quote->setIsActive(0)->save();
            } catch (Exception $e) {
                Mage::logException($e);
            }
        }

        return;
    }

    public function splitOrder(&$quote)
    {
        if (!$this->haveToSplitOrder($quote)) {
            return array($quote);
        }
        // Create new order
        $customer = Mage::getModel('customer/customer')->load($quote->getCustomerId());
        $newQuote = Mage::getModel('sales/quote');
        $newQuote->setStore(Mage::getModel('core/store')->load($quote->getStoreId()));
        // Add items to new quote if stock_status >= 2
        $quoteItems = $quote->getAllVisibleItems();

        foreach ($quoteItems as $_item) {
            // Get product
            $product = Mage::getModel('catalog/product')->load($_item->getProductId());
            $buyRequest = $_item->getBuyRequest();
            if ($_item->getProductType() === 'configurable') {
                // Get childProduct to check stock_status
                $childProduct = $this->getProductFromSuperAttribute($buyRequest->getData('super_attribute'), $product);
            } else {
                // Only simple, group product type
                $childProduct = $product;
            }
            // Check stock_status with $product
            if ($childProduct->getData('stock_status') > 1) {
                // Add product to new quote
                $newQuote->addProduct($product, $buyRequest);
                // Remove product out of quote
                $quote->removeItem($_item->getId())->save();
            }
        }

        if (count($newQuote->getAllVisibleItems())) {
            // Assign customer
            $newQuote->assignCustomer($customer);
            // Set shipping method
            $newQuote->getShippingAddress()->setShippingMethod('freeshipping_freeshipping');
            $newQuote->getShippingAddress()->setShippingDescription($this->__('Freeshipping - Free'));
            $newQuote->getShippingAddress()->setCollectShippingRates(true);
            // Set payment method to quote
            $newQuote->collectTotals()
                ->getPayment()->setMethod('checkmo');

            $newQuote->setTotalsCollectedFlag(false);
            $newQuote->collectTotals()->save();

            $quote->setTotalsCollectedFlag(false);
            $quote->getShippingAddress()->unsetData('cached_items_all');
            $quote->getShippingAddress()->unsetData('cached_items_nominal');
            $quote->getShippingAddress()->unsetData('cached_items_nonnominal');
            $quote->collectTotals()->save();

            return array($quote, $newQuote);
        }
        // Free memory
        $newQuote = null;

        $quote->setTotalsCollectedFlag(false);
        $quote->getShippingAddress()->unsetData('cached_items_all');
        $quote->getShippingAddress()->unsetData('cached_items_nominal');
        $quote->getShippingAddress()->unsetData('cached_items_nonnominal');
        $quote->collectTotals()->save();

        return array($quote);
    }

    public function haveToSplitOrder($quote)
    {
        $initStockStatus = 0;
        $quoteItems = $quote->getAllVisibleItems();
        foreach ($quoteItems as $_item) {
            // Get product
            $product = Mage::getModel('catalog/product')->load($_item->getProductId());
            $buyRequest = $_item->getBuyRequest();
            if ($_item->getProductType() === 'configurable') {
                // Get childProduct to check stock_status
                $childProduct = $this->getProductFromSuperAttribute($buyRequest->getData('super_attribute'), $product);
            } else {
                // Only simple, group product type
                $childProduct = $product;
            }
            // Only first item
            if ($initStockStatus === 0) {
                $initStockStatus = $childProduct->getData('stock_status');
            }
            // Check stock_status with $product
            if ($childProduct->getData('stock_status') != $initStockStatus) {
                return true;
            }
        }

        return false;
    }
    public function getProductFromSuperAttribute($superAttribute, $product)
    {
        $product = Mage::getModel('catalog/product_type_configurable')->getProductByAttributes($superAttribute, $product);

        return Mage::getModel('catalog/product')->load($product->getId());
    }
}