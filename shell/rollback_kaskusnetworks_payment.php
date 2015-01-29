<?php
require_once 'abstract.php';

class Kaskusnetworks_Payment_Rollback extends Mage_Shell_Abstract
{
    private $_setup;

    public function run()
    {
        $this->_setup = new Mage_Core_Model_Resource_Setup('core_setup');
        $this->_setup->startSetup();

        $this->_deleteTables();
        $this->_deleteResourceEntry();

        $this->_setup->endSetup();
        Mage::app()->getCacheInstance()->flush();
    }

    public function usageHelp()
    {
        return <<<USAGE
Reversing any database changes made by Kaskusnetworks_Payment module.
To ease uninstallation.
USAGE;
    }

    private function _deleteTables()
    {
        $conn = $this->_setup->getConnection();
        $conn->dropTable('kaskusnetworks_payment_payment');
        $conn->dropTable('kaskusnetworks_payment_payment_detail');
        $conn->dropTable('kaskusnetworks_payment_callbacks');
        $conn->dropTable('kaskusnetworks_payment_receiver_information');
        $conn->dropTable('kaskusnetworks_payment_payer_information');
        $conn->dropTable('kaskusnetworks_payment_payer_information_klikbca');
        $conn->dropTable('kaskusnetworks_payment_merchant');
        $conn->dropTable('kaskusnetworks_payment_notification');
    }

    private function _deleteResourceEntry()
    {
        $this->_setup->deleteTableRow('core_resource', 'code', 'kaskusnetworks_payment_setup');
    }

}

$rollback = new Kaskusnetworks_Payment_Rollback();
$rollback->run();