<?php

/* Reseller
 * @Author soumik.karmakar@forsaaa.se
 *  add get Simples of configurable
 *
 * */

class Forsaaa_Reseller_Block_Reseller extends Forsaaa_Reseller_Block_Abstract {

    protected $_resellerCollection;

    protected function _getResellerCollection() {
        if (is_null($this->_resellerCollection)) {
            if (Mage::getSingleton('customer/session')->isLoggedIn()) {
                $customerData = Mage::getSingleton('customer/session')->getCustomer();
                $groupId = $customerData['group_id'];
            }
            if ($groupId == 5) {
                $agentId = $customerData['agent'];
                $user = Mage::getModel('customer/customer')
                        ->getCollection()
                        ->addAttributeToSelect('*')
                        ->addFieldToFilter('group_id', 3)
                        ->setPageSize(1000)
                        ->addFieldToFilter('agent', $agentId);
            } elseif ($groupId == 4) {
                $user = Mage::getModel('customer/customer')->getCollection()
                        ->addAttributeToSelect('*')
                        ->addFieldToFilter('group_id', 3);
            } else {
                $user = Mage::getModel('customer/customer')->getCollection()
                        ->addAttributeToSelect('*')
                        ->setPageSize(1000)
                        ->addFieldToFilter('group_id', 3);
            }
            $currentUrl = Mage::helper('core/url')->getCurrentUrl();
            $data = explode('?',$currentUrl);
            if($data [1] == 'custno' ){
                $finalUser = $user->setOrder('prefix','DESC');
                $this->_resellerCollection = $finalUser;
            }elseif($data [1] == 'firstname' ){
                $finalUser = $user->setOrder('firstname','DESC');
                $this->_resellerCollection = $finalUser;
            }elseif($data [1] == 'seller'){
                 $finalUser = $user->setOrder('seller','DESC');
                $this->_resellerCollection = $finalUser;
            }elseif($data [1] == 'agent'){
                 $finalUser = $user->setOrder('agent','DESC');
                $this->_resellerCollection = $finalUser;
            }
            else{
                $this->_resellerCollection = $user;
            }
            
        }
        return $this->_resellerCollection;
    }

    public function getResellerFilterCollection() {
        $sellerId = $this->getRequest()->getParam('searchSeller');
        $collection = "No record found.";
        if (empty($sellerId)) {
            $filter = $this->getRequest()->getParam('searchKeys');
            $custEntityIds = array();
            $user1 = Mage::getModel('customer/customer')
                    ->getCollection()
                    ->addAttributeToSelect('*')
                    ->addFieldToFilter('group_id', 3)
                    ->addAttributeToFilter('firstname', array('like' => "%$filter%"))
                    ->setPageSize(50);

            $read = Mage::getSingleton('core/resource')->getConnection('core_read');
            $sql = "SELECT `entity_id` FROM `customer_entity` where `group_id` = 3 AND`email` like '%" . $filter . "%'";
            $entityid = $read->fetchAll($sql);
            $user2 = array();
            foreach ($entityid as $key) {
                foreach ($key as $key1 => $value) {
                    $user2[] = $value;
                }
            }

            if (!empty($user1)) {
                foreach ($user1 as $user) {
                    $custEntityIds[] = $user->getEntityId();
                }
            }
            if (!empty($user2)) {
                foreach ($user2 as $user) {
                    $custEntityIds[] = $user;
                }
            }

            $custEntityIds = array_unique($custEntityIds);

            $collection = Mage::getModel('customer/customer')->getCollection()->addAttributeToSelect('*');
            $collection->addFieldToFilter('entity_id', $custEntityIds);
        } else {
            $collection = Mage::getModel('customer/customer')
                    ->getCollection()
                    ->addAttributeToSelect('*')
                    ->addFieldToFilter('seller', $sellerId)
                    ->setPageSize(1000);
        }
        return $collection;
    }

    public function getLoadedResellerCollection() {
        return $this->_getResellerCollection();
    }

}
