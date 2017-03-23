<?php

/* Reseller
 * @Author Larry@forsaaa.se
 *  add get Simples of configurable
 *
 * */

class Forsaaa_Reseller_Block_Product extends Forsaaa_Reseller_Block_Abstract {

    protected $_products;
    protected $_defaultToolbarBlock = 'catalog/product_list_toolbar';
    protected $_productCollection;

    public function __construct() {
        parent::__construct();
        if (!$this->hasData('category')) {
            $this->setData('category', Mage::registry('category'));
        }
        if (!$this->hasData('sku')) {
            $this->setData('sku', Mage::registry('sku'));
        }
        if (!$this->hasData('name')) {
            $this->setData('name', Mage::registry('name'));
        }
        if (!$this->hasData('type_name')) {
            $this->setData('type_name', Mage::registry('type_name'));
        }
        if (!$this->hasData('subcate')) {
            $this->setData('subcate', Mage::registry('subcate'));
        }
        $storeId = Mage::app()->getStore()->getStoreId();
        $collection = Mage::getResourceModel('catalog/product_collection')
                ->addStoreFilter($storeId)
                ->addAttributeToFilter('status', 1)
                ->addAttributeToFilter('visibility', array('in' => array(2, 3, 4)))
                ->addAttributeToSelect('*');
        
        if ($this->getData('type_name') && $this->getData('name')) {
            $collection->addAttributeToFilter($this->getData('type_name'), array('like' => '%' . $this->getData('name') . '%'));
        } elseif ($this->getData('name')) {
            $collection->addAttributeToFilter(
                array(
                    array('attribute' => 'sku', 'like' => '%' . $this->getData('name') . '%'),
                    array('attribute' => 'name', 'like' => '%' . $this->getData('name') . '%'),
                    array('attribute' => 'description', 'like' => '%' . $this->getData('name') . '%')
                )
            );
        }

        Mage::getSingleton('cataloginventory/stock')->addInStockFilterToCollection($collection);
        $this->setCollection($collection);
    }

    protected function _getProductCollection() {
        if (is_null($this->_productCollection)) {
            $layer = $this->getLayer();
            /* @var $layer Mage_Catalog_Model_Layer */
            if ($this->getShowRootCategory()) {
                $this->setCategoryId(Mage::app()->getStore()->getRootCategoryId());
            }
            // if this is a product view page
            if (Mage::registry('product')) {
                // get collection of categories this product is associated with
                $categories = Mage::registry('product')->getCategoryCollection()
                        ->setPage(1, 1)
                        ->load();
                // if the product is associated with any category
                if ($categories->count()) {
                    // show products from this category
                    $this->setCategoryId(current($categories->getIterator()));
                }
            }
            $origCategory = null;
            if ($this->getCategoryId()) {
                $category = Mage::getModel('catalog/category')->load($this->getCategoryId());
                if ($category->getId()) {
                    $origCategory = $layer->getCurrentCategory();
                    $layer->setCurrentCategory($category);
                }
            }
            $this->_productCollection = $layer->getProductCollection();
            $this->prepareSortableFieldsByCategory($layer->getCurrentCategory());
            if ($origCategory) {
                $layer->setCurrentCategory($origCategory);
            }
        }

        return $this->_productCollection;
    }

    protected function _beforeToHtml() {
        $toolbar = $this->getToolbarBlock();
        // called prepare sortable parameters
        $collection = $this->_getProductCollection();
        // use sortable parameters
        if ($orders = $this->getAvailableOrders()) {
            $toolbar->setAvailableOrders($orders);
        }
        if ($sort = $this->getSortBy()) {
            $toolbar->setDefaultOrder($sort);
        }
        if ($dir = $this->getDefaultDirection()) {
            $toolbar->setDefaultDirection($dir);
        }
        if ($modes = $this->getModes()) {
            $toolbar->setModes($modes);
        }
        // set collection to toolbar and apply sort
        $toolbar->setCollection($collection);

        $this->setChild('toolbar', $toolbar);
        Mage::dispatchEvent('catalog_block_product_list_collection', array(
            'collection' => $this->_getProductCollection()
        ));
        $this->_getProductCollection()->load();

        return parent::_beforeToHtml();
    }

    public function getLayer() {
        $layer = Mage::registry('current_layer');
        if ($layer) {
            return $layer;
        }

        return Mage::getSingleton('catalog/layer');
    }

    public function getLoadedProductCollection() {
        return $this->_getProductCollection();
    }

    public function getToolbarBlock() {
        if ($blockName = $this->getToolbarBlockName()) {
            if ($block = $this->getLayout()->getBlock($blockName)) {
                return $block;
            }
        }
        $block = $this->getLayout()->createBlock($this->_defaultToolbarBlock, microtime());

        return $block;
    }

    public function getMode() {
        return $this->getChild('toolbar')->getCurrentMode();
    }

    public function getToolbarHtml() {
        return $this->getChildHtml('toolbar');
    }

    public function getProducts() {
        return $this->_products;
    }

    public function setCollection($collection) {
        $this->_productCollection = $collection;

        return $this;
    }

    public function getForm($product) {
        $form = Array();
        if ($product->getTypeId() == 'configurable') {
            // Get the child products
            $attributes = $product->getTypeInstance(true)->getConfigurableAttributesAsArray($product);
            $children = $product->getTypeInstance()->getUsedProducts();
            foreach ($children as $child) {
                if ($child->getVisibility() && $child->getStatus() == 1) {
                    $name = "";
                    foreach ($attributes as $attribute) {
                        $name.= $child->getAttributeText($attribute['attribute_code']) . " ";
                    }
                    $qty = 0;
                    $form[] = Array(
                        'id' => $child->getId(),
                        'sku' => $child->getSku(),
                        'name' => $name,
                        'qty' => $qty,
                        'bookable' => $this->_getBookable($child->getId(), 0, $qty),
                    );
                }
            }
        } else {
            // Get the product
            $form[] = Array(
                'id' => $product->getId(),
                'sku' => $product->getSku(),
                'name' => $this->__('Quantity'),
                'qty' => 1,
                'until' => $product->getTeamsalesAvailableUntil()
            );
        }

        return $form;
    }

    private function _getQty($product_id = null, $order_id = null) {
        if ($order_id == null) {
            return 0;
        }
        $orderitems = Mage::getModel('scteamsales/orderitem')->getCollection()
                ->addFieldToFilter('order_id', $order_id)
                ->addFieldToFilter('product_id', $product_id);
        if ($orderitems) {
            foreach ($orderitems as $orderitem) {
                return $orderitem->getQty();
            }
        }

        return 0;
    }

    private function _getBookable($product_id = null, $order_id = null, $alreadybooked = null) {
        $stock = (int) Mage::getModel('cataloginventory/stock_item')->loadByProduct($product_id)->getQty();
        if ($order_id == null) {
            //Just check how many can be booked
            return $stock - $booked;
        } else {
            return $stock;
        }

        return 0;
    }

    public function getNumberOfProductsBaseCategory($category) {
        $prodCollection = Mage::getResourceModel('catalog/product_collection')
                ->addStoreFilter(Mage::app()->getStore()->getStoreId())
                ->addAttributeToFilter('visibility', array('in' => array(2, 3, 4)))
                ->addAttributeToFilter('status', 1)
                ->addAttributeToSelect('*')
                ->addCategoryFilter($category);

        return $prodCollection->count();
    }

    public function getPriceHtml($product) {
        $price = $this->getLayout()->createBlock('catalog/product_price');
        $price->setTemplate('catalog/product/price.phtml');
        $price->setProduct($product);
        return $price->toHtml();
    }

}
