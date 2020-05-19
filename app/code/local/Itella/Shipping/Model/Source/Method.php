<?php



class Itella_Shipping_Model_Source_Method
{
    public function toOptionArray()
    {
        $itella = Mage::getSingleton('Itella_Shipping_Model_Carrier');
        $arr = array();
        foreach ($itella->getCode('method') as $k => $v) {
            $arr[] = array('value' => $k, 'label' => $v);
        }
        return $arr;
    }
}
