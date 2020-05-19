<?php

class Itella_Shipping_Model_Source_Terminal
{
    public function toOptionArray()
    {
        $itella = Mage::getSingleton('Itella_Shipping_Model');
        $arr = array();
        foreach ($itella->getCode('terminal') as $k => $v) {
            $arr[] = array('value' => $k, 'label' => $v);
        }
        return $arr;
    }
}

