<?php

/**
 * Description of Kaskusnetworks_Mini_Block_Adminhtml_System_Store_Tree
 *
 * @author Karol Danutama <karol.danutama@gdpventure.com>
 */
class Kaskusnetworks_Mini_Block_Adminhtml_System_Store_Tree extends Mage_Adminhtml_Block_System_Store_Tree
{
    public function renderStoreGroup(Mage_Core_Model_Store_Group $storeGroup)
    {
        return $this->_createCellTemplate()
            ->setObject($storeGroup)
            ->setLinkUrl($this->getUrl('*/*/editGroup', array('group_id' => $storeGroup->getGroupId())))
            ->setInfo($this->__('Root Category') . ': ' . "none")
            ->toHtml();
    }

}
