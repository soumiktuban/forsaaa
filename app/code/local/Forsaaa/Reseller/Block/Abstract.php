<?php
class Forsaaa_Reseller_Block_Abstract extends Mage_Core_Block_Template
{

    public function _getSession()
    {
        return Mage::getSingleton('customer/session');
    }

}
