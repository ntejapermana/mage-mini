<?php

/**
 * Description of Kaskusnetworks_Mini_Block_Adminhtml_Customer_Edit_Tabs
 *
 * @author Karol Danutama <karol.danutama@gdpventure.com>
 */
class Kaskusnetworks_Mini_Block_Adminhtml_Customer_Edit_Tabs extends Mage_Adminhtml_Block_Customer_Edit_Tabs
{
    protected function _beforeToHtml()
    {
        $this->addTab('account', array(
            'label' => Mage::helper('customer')->__('Account Information'),
            'content' => $this->getLayout()->createBlock('adminhtml/customer_edit_tab_account')->initForm()->toHtml(),
            'active' => Mage::registry('current_customer')->getId() ? false : true
        ));

        $this->addTab('addresses', array(
            'label' => Mage::helper('customer')->__('Addresses'),
            'content' => $this->getLayout()->createBlock('adminhtml/customer_edit_tab_addresses')->initForm()->toHtml(),
        ));


        // load: Orders, Shopping Cart, Wishlist, Product Reviews, Product Tags - with ajax

        if (Mage::registry('current_customer')->getId()) {

            
        }

        $this->_updateActiveTab();
        Varien_Profiler::stop('customer/tabs');
        return Mage_Adminhtml_Block_Widget_Tabs::_beforeToHtml();
    }

}
