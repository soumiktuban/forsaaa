<?php

class Forsaaa_Reseller_IndexController extends Mage_Core_Controller_Front_Action {

    protected function _getSession() {
        return Mage::getSingleton('customer/session');
    }

    public function preDispatch() {
        // a brute-force protection here would be nice
        parent::preDispatch();
        if (!$this->getRequest()->isDispatched()) {
            return;
        }
        
        if (!$this->_getSession()->authenticate($this)) {
            Mage::app()->getFrontController()->getResponse()->setRedirect(Mage::getUrl('customer/account/login'));
        } elseif ($this->_getSession()->getCustomerGroupid() == 0) {
            Mage::app()->getFrontController()->getResponse()->setRedirect(Mage::getUrl('reseller/index/login'));
        } else {
            Mage::getSingleton('core/session')->setResellersession(1);
            if ($this->_getSession()->getCustomerGroupId() == 1 || $this->_getSession()->getCustomerGroupId() == 2) {
                Mage::app()->getFrontController()->getResponse()->setRedirect(Mage::getUrl(''));
            }
        }
    }

    public function indexAction() {
        $aParams = $this->getRequest()->getParams();
        $aProducts = array();
        if (isset($aParams['products']) && is_array($aParams['products'])) {
            foreach ($aParams['products'] as $id => $val) {
                if ($id > 0 && $val > 0) {
                    $aProducts[$id] = $val;
                }
            }
        }
        Mage::register('products', $aProducts);
        $iCategoryId = $this->getRequest()->getParam('cate');
        Mage::register('category', $iCategoryId);
        $sSku = $this->getRequest()->getParam('sku');
        Mage::register('sku', $sSku);
        $sName = $this->getRequest()->getParam('name');
        Mage::register('name', $sName);
        $sType = $this->getRequest()->getParam('type_name');
        Mage::register('type_name', $sType);
        $sSubcate = $this->getRequest()->getParam('subcate');
        Mage::register('subcate', $sSubcate);
        $this->loadLayout();
        $this->renderLayout();
    }

    public function filterAction() {
        $this->loadLayout();
        $this->renderLayout();
    }

    public function viewAction() {
        $productId = (int) $this->getRequest()->getParam('productId');
        Mage::helper('catalog/product')->initProduct($productId, $this);
        $this->loadLayout();
        $this->renderLayout();
    }

    public function orderAction() {
        $this->loadLayout();
        $this->renderLayout();
    }

    public function orderreviewAction() {
        $this->loadLayout();
        $this->renderLayout();
    }

    public function loginAction() {
        $this->loadLayout();
        $this->renderLayout();
    }

    public function customerpageAction() {
        Mage::getSingleton('core/session')->unsResellerId();
        $this->loadLayout();
        $this->renderLayout();
    }

    public function customerfilterAction() {
        $this->loadLayout();
        $this->renderLayout();
    }

    public function customerdetailsresellerpageAction() {
        $this->loadLayout();
        $this->renderLayout();
    }

    public function customerdetailspageAction() {
        $this->loadLayout();
        $this->renderLayout();
    }
	
    public function customerdetailspageallAction() {
        $this->loadLayout();
        $this->renderLayout();
    }
	public function reselleraddAction() {
        $this->loadLayout();
        $this->renderLayout();
    }

    public function setresellerAction() {
        $resellerId = Mage::app()->getRequest()->getParam('customerId');
        if ($resellerId) {
            Mage::getSingleton('core/session')->setResellerId($resellerId);
        }
    }
    
    public function itemcountAction() {
        echo $count = Mage::helper('checkout/cart')->getSummaryCount();
        return;
    }

    public function successAction()
    {
        $this->loadLayout();
        $this->renderLayout();
    }
}
