<?php

class Forsaaa_Reseller_Block_Cart extends Mage_Checkout_Block_Cart
{
    protected $collection;

    public function __construct()
    {
        parent::__construct();
    }

    protected function _prepareProductCollection()
    {
        $collection = Mage::getModel('sales/quote_item')->getCollection()
            ->setQuote($this->getQuote())
            ->addFieldToSelect('*')
            ->addOrder('sku', 'asc'); //change acc to your attribute
        $this->collection = $collection;

        return $this->collection;
    }

    public function getCollection()
    {
        return $this->collection;
    }

    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        $pager = $this->getLayout()->createBlock('page/html_pager', 'cart.pager')->setTemplate('forsaaa/page/html/pager.phtml');
        $pager->setCollection($this->_prepareProductCollection());
        $this->setChild('cart.pager', $pager);

        return $this;
    }
}
