<?php

class Itella_Shipping_Adminhtml_ServicesController extends Mage_Adminhtml_Controller_Action {
    
    private function _collectPostData($post_key = null) {
        return $this->getRequest()->getPost($post_key);
    }
    
    public function SaveServicesAction() {
        $order_id = $this->_collectPostData('order_id');
        $services = $this->_collectPostData('itella_services');
        if (!$services){
            $services = array();
        }
        $parcel_count = $this->_collectPostData('parcel_count');
        $order = Mage::getModel('sales/order')->load($order_id);
        $order->setItellaServices(json_encode(array('services'=>$services, 'parcel_count' => $parcel_count)));
        $order->save();
        //var_dump($order->getItellaServices());
        //exit;
        $this->_redirectReferer();
        return;
    }
}