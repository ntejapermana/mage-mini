<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Kaskusnetworks_Mini_Block_Adminhtml_Customer_Group_Edit_Form
 *
 * @author Karol Danutama <karol.danutama@gdpventure.com>
 */
class Kaskusnetworks_Mini_Block_Adminhtml_Customer_Group_Edit_Form extends Mage_Adminhtml_Block_Customer_Group_Edit_Form
{
    protected function _prepareLayout()
    {
        Mage_Adminhtml_Block_Widget_Form::_prepareLayout();
        $form = new Varien_Data_Form();
        $customerGroup = Mage::registry('current_group');

        $fieldset = $form->addFieldset('base_fieldset', array('legend' => Mage::helper('customer')->__('Group Information')));

        $validateClass = sprintf('required-entry validate-length maximum-length-%d', Mage_Customer_Model_Group::GROUP_CODE_MAX_LENGTH);
        $name = $fieldset->addField('customer_group_code', 'text', array(
            'name' => 'code',
            'label' => Mage::helper('customer')->__('Group Name'),
            'title' => Mage::helper('customer')->__('Group Name'),
            'note' => Mage::helper('customer')->__('Maximum length must be less then %s symbols', Mage_Customer_Model_Group::GROUP_CODE_MAX_LENGTH),
            'class' => $validateClass,
            'required' => true,)
        );

        if ($customerGroup->getId() == 0 && $customerGroup->getCustomerGroupCode()) {
            $name->setDisabled(true);
        }

        if (!is_null($customerGroup->getId())) {
            // If edit add id
            $form->addField('id', 'hidden', array(
                'name' => 'id',
                'value' => $customerGroup->getId(),
                    )
            );
        }

        if (Mage::getSingleton('adminhtml/session')->getCustomerGroupData()) {
            $form->addValues(Mage::getSingleton('adminhtml/session')->getCustomerGroupData());
            Mage::getSingleton('adminhtml/session')->setCustomerGroupData(null);
        } else {
            $form->addValues($customerGroup->getData());
        }

        $form->setUseContainer(true);
        $form->setId('edit_form');
        $form->setAction($this->getUrl('*/*/save'));
        $this->setForm($form);
    }

}
