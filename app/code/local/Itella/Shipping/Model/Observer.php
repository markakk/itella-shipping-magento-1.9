<?php
class Itella_Shipping_Model_Observer
{
  public function saveItellaPickupPoint(Varien_Event_Observer $observer)
  {
    $param = Mage::app()->getRequest()->getParam('itella_pickup_point', '');
    $quote = $observer->getQuote();
    $quote->setData('itella_pickup_point',(string) $param);
    $quote->save();
  }
  
  public function saveItellaPickupPointToOrder(Varien_Event_Observer $observer)
  {
    $quote = $observer->getQuote();
    $order = $observer->getOrder();
    $pickup_point = $quote->getData('itella_pickup_point');
    $order->setData('itella_pickup_point',(string) $pickup_point);
    $order->save();
    
    
  }
  public function addMassAction($observer)
  {
    $block = $observer->getEvent()->getBlock();
    if (get_class($block) == 'Mage_Adminhtml_Block_Widget_Grid_Massaction' && $block->getRequest()->getControllerName() == 'sales_order') {
      $block->addItem('itellashipment', array(
        'label' => Mage::helper('shipping')->__('Generate Itella labels'),
        'url' => Mage::app()->getStore()->getUrl('itella_shipping/adminhtml_label/CreateShipment')
      ));
      $block->addItem('itellamanifest', array(
        'label' => Mage::helper('shipping')->__('Print Itella manifest'),
        'url' => Mage::app()->getStore()->getUrl('itella_shipping/adminhtml_label/CreateManifest')
      ));
    }
  }
  public function callItellaButton($observer)
    {   
        $container = $observer->getBlock();
        if(null !== $container && $container->getType() == 'adminhtml/sales_order') {
            $data = array(
                'label'     => Mage::helper('shipping')->__('Call Itella'),
                'class'     => '',
                'onclick'   => "callItella('".Mage::helper("adminhtml")->getUrl('itella_shipping/adminhtml_label/CallItella')."')",
            );
            $container->addButton('unique-identifier', $data);
        }

        return $this;
    }
}