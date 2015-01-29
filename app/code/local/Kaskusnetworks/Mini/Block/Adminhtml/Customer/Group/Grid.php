<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Kaskusnetworks_Mini_Block_Adminhtml_Customer_Group_Grid
 *
 * @author Karol Danutama <karol.danutama@gdpventure.com>
 */
class Kaskusnetworks_Mini_Block_Adminhtml_Customer_Group_Grid extends Mage_Adminhtml_Block_Customer_Group_Grid
{
    protected function _prepareCollection()
    {
        $collection = Mage::getResourceModel('customer/group_collection');

        $this->setCollection($collection);
        return Mage_Adminhtml_Block_Widget_Grid::_prepareCollection();
    }

}
