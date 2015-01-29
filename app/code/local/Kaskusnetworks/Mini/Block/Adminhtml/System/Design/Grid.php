<?php

/**
 * Description of Kaskusnetworks_Mini_Block_Adminhtml_System_Design_Grid
 *
 * @author Karol Danutama <karol.danutama@gdpventure.com>
 */
class Kaskusnetworks_Mini_Block_Adminhtml_System_Design_Grid extends Mage_Adminhtml_Block_System_Design_Grid
{
    protected function _prepareColumns()
    {
        if (!Mage::app()->isSingleStoreMode()) {
            $this->addColumn('store_id', array(
                'header' => Mage::helper('catalog')->__('Store'),
                'width' => '100px',
                'type' => 'store',
                'store_view' => true,
                'sortable' => false,
                'index' => 'store_id',
            ));
        }

        $this->addColumn('package', array(
            'header' => Mage::helper('catalog')->__('Design'),
            'width' => '150px',
            'index' => 'design',
        ));
        $this->addColumn('date_from', array(
            'header' => $this->__('Date From'),
            'align' => 'left',
            'width' => '100px',
            'type' => 'date',
            'index' => 'date_from',
        ));

        $this->addColumn('date_to', array(
            'header' => $this->__('Date To'),
            'align' => 'left',
            'width' => '100px',
            'type' => 'date',
            'index' => 'date_to',
        ));

        return Mage_Adminhtml_Block_Widget_Grid::_prepareColumns();
    }

}
