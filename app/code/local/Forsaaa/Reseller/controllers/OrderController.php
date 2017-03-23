<?php

/* Reseller
 * @Author Larry@forsaaa.se
 * add multi cart
 *
 * */
include_once( Mage::getBaseDir() . '/app/code/core/Mage/Checkout/controllers/CartController.php' );

class Forsaaa_Reseller_OrderController extends Mage_Checkout_CartController {

    protected function _initProduct($id) {
        $productId = (int) $id;
        if ($productId) {
            $product = Mage::getModel('catalog/product')
                    ->setStoreId(Mage::app()->getStore()->getId())
                    ->load($productId);
            if ($product->getId()) {
                return $product;
            }
        }

        return false;
    }

    public function resellercartitemsaddAction() {
        $params = $this->getRequest()->getParams();
        $prodCartVal = Mage::getSingleton('core/session')->getResellerCartVal();
        if ($params['qty'] == '0') {
            unset($prodCartVal['products'][$params['childid']]);
        } else {
            $prodCartVal['products'][$params['childid']] = $params['qty'];
        }
        Mage::getSingleton('core/session')->setResellerCartVal($prodCartVal);

        $resellerCartVal = Mage::getSingleton('core/session')->getResellerCartVal();
        if ($resellerCartVal && count($resellerCartVal['products']) > 0) {
            echo $this->__('You have selected products that are not yet added to cart, do you still want to navigate away from this page?');
        }
    }

    public function resellercartitemscheckAction() {
        $resellerCartVal = Mage::getSingleton('core/session')->getResellerCartVal();
        if ($resellerCartVal && count($resellerCartVal['products']) > 0) {
            echo $this->__('You have selected products that are not yet added to cart, do you still want to navigate away from this page?');
        }
    }

    public function addAction() {
        
        $configProdId = Mage::app()->getRequest()->getParam('configProdId');
        $simpleqty = Mage::app()->getRequest()->getParam('simpleqty');
        $product = Mage::getModel('catalog/product')->load($configProdId);
        $cart = Mage::getModel("checkout/cart");
        if ($product->getTypeID() != "simple") {
            $attributeId = Mage::app()->getRequest()->getParam('attributeId');
            $sizeCode = Mage::app()->getRequest()->getParam('sizeCode');
            $options = array("product" => $configProdId, "super_attribute" => array($attributeId => $sizeCode), "qty" => $simpleqty);
            $cart->addProduct($product, $options);
            
        } else {
            $cart->addProduct($product, array(
               'product' => $configProdId,
               'qty' => $simpleqty,
            ));
        }
        $cart->save();
        Mage::getSingleton('checkout/session')->setCartWasUpdated(true);
        echo Mage::helper('checkout/cart')->getSummaryCount();
    }

    public function successAction()
    {
        Mage::getSingleton('checkout/session')->clear();
        $this->loadLayout();
        $this->renderLayout();
    }
}

?>
