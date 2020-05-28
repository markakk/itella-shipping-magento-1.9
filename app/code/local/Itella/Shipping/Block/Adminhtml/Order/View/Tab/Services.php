<?php
class Itella_Shipping_Block_Adminhtml_Order_View_Tab_Services extends Mage_Adminhtml_Block_Template implements Mage_Adminhtml_Block_Widget_Tab_Interface
{    
    public function _construct()
    {
        parent::_construct();
        $this->setTemplate('itella_shipping/sales/order/view/tab/services.phtml');

    }

    public function getTabLabel() {
        return $this->__('Itella services');
    }

    public function getTabTitle() {
        return $this->__('Itella services');
    }

    public function canShowTab() {
        return $this->isItellaMethod($this->getOrder());
    }

    public function isHidden() {
        return false;
    }

    public function getOrder(){
        return Mage::registry('current_order');
    }
    
    public function getServices(){
        return array('3101'=>__("Cash On Delivery"),
        '3166' => __("Call before Delivery"),
        '3174' => __("Oversized"),
        '3104' => __("Fragile"),
        '3102' => __("Multi Parcel"));
    }
    
    public function isItellaMethod($order){
        $_ItellaMethods      = array(
          //'Itella_PARCEL_TERMINAL',
          'itella_COURIER'
        );
        $order_shipping_method = $order->getData('shipping_method');
        return in_array($order_shipping_method, $_ItellaMethods);
    }
}