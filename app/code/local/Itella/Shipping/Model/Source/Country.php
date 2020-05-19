<?php

class Itella_Shipping_Model_Source_Country
{
    public function toOptionArray()
    {
        $arr = array();
        $arr[] = array('value' => 'EE', 'label' => Mage::helper('itella_shipping')->__('Estonia'));
        $arr[] = array('value' => 'LV', 'label' => Mage::helper('itella_shipping')->__('Latvia'));
        $arr[] = array('value' => 'LT', 'label' => Mage::helper('itella_shipping')->__('Lithuania'));
        $arr[] = array('value' => 'FI', 'label' => Mage::helper('itella_shipping')->__('Finland'));
        return $arr;
    }
}
