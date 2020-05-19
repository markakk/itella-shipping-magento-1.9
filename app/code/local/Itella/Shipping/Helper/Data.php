<?php

class Itella_Shipping_Helper_Data extends Mage_Core_Helper_Abstract
{
  public $_itellaMethods = array('itella_PARCEL_TERMINAL','itella_COURIER');
   
  public function isItellaMethod($order)
  {
    $order_shipping_method = $order->getData('shipping_method');
    return in_array($order_shipping_method, $this->_itellaMethods);
  }
  public function getItellaAddress($order){
    $itella = Mage::getSingleton('Itella_Shipping_Model_Carrier');
     $shippingAddress = $order->getShippingAddress();
    $country = Mage::getModel('directory/country')->loadByCode($shippingAddress->country_id);
                $parcel_terminal_address = '';
                if ($order->getData('shipping_method') == 'itella_PARCEL_TERMINAL') {
                    $terminal_id = $order->getItellaPickupPoint();
                    $country = strtolower($order->getShippingAddress()->getCountryId());
                    $parcel_terminal = $itella->_getItellaTerminal($terminal_id, $country);
                    if ($parcel_terminal) {
                        $parcel_terminal_address =  $this->__("Pick up point:").' '.$parcel_terminal['publicName'] . ', ' . $parcel_terminal['address']['streetName'] . ' ' . $parcel_terminal['address']['streetNumber'] . ', ' . $parcel_terminal['address']['postalCode'] . ', ' . $parcel_terminal['address']['postalCodeName'];
                        
                    }
                }
                $client_address = $shippingAddress->firstname . ' ' . $shippingAddress->lastname . ', ' . $shippingAddress->street . ', ' . $shippingAddress->postcode . ', ' . $shippingAddress->city . ' ' . $country;
                if ($parcel_terminal_address != '')
                    $client_address = '';         
    return $client_address . $parcel_terminal_address;            
  }
}