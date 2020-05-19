<?php

class Itella_Shipping_Adminhtml_ItellamanifestController extends Mage_Adminhtml_Controller_Action
{
    protected function _isAllowed()
    {
      return Mage::getSingleton('admin/session')->isAllowed('sales');
    }
    
    public function indexAction()
    {
        $this->loadLayout()->_setActiveMenu('sales')->_title($this->__('Itella manifest'));; 
        $this->renderLayout();
    }
}