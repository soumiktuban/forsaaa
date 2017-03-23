<?php
class Forsaaa_Reseller_Model_Observer {
    public function sendEmail($observer) {
        try {
            $order = $observer->getEvent()->getOrder();
            $order->sendNewOrderEmail();
        } catch (Exception $e) {
            Mage::logException($e->getMessage());
        }
    }
}
