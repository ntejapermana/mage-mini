<?php

/**
 * Description of Kaskusnetworks_Mini_Block_Adminhtml_Customer_Edit_Tab_View_Accordion
 *
 * @author Karol Danutama <karol.danutama@gdpventure.com>
 */
class Kaskusnetworks_Mini_Block_Adminhtml_Customer_Edit_Tab_View_Accordion extends Mage_Adminhtml_Block_Widget_Accordion
{
    protected function _prepareLayout()
    {
        $customer = Mage::registry('current_customer');

        $this->setId('customerViewAccordion');

        $this->addItem('lastOrders', array(
            'title' => Mage::helper('customer')->__('Recent Orders'),
            'ajax' => true,
            'content_url' => $this->getUrl('*/*/lastOrders', array('_current' => true)),
        ));

        // add shopping cart block of each website
        foreach (Mage::registry('current_customer')->getSharedWebsiteIds() as $websiteId) {
            $website = Mage::app()->getWebsite($websiteId);

            // prepare title for cart
            $title = Mage::helper('customer')->__('Shopping Cart - %d item(s)', 0);
            if (count($customer->getSharedWebsiteIds()) > 1) {
                $title = Mage::helper('customer')->__('Shopping Cart of %1$s - %2$d item(s)', $website->getName(), $cartItemsCount);
            }

            // add cart ajax accordion
            $this->addItem('shopingCart' . $websiteId, array(
                'title' => $title,
                'ajax' => true,
                'content_url' => $this->getUrl('*/*/viewCart', array('_current' => true, 'website_id' => $websiteId)),
            ));
        }

        // add wishlist ajax accordion
        $this->addItem('wishlist', array(
            'title' => Mage::helper('customer')->__('Wishlist - %d item(s)', 0),
            'ajax' => true,
            'content_url' => $this->getUrl('*/*/viewWishlist', array('_current' => true)),
        ));
    }

}
