<?php
/**
 * This file is part of the Klarna Kp module
 *
 * (c) Klarna Bank AB (publ)
 *
 * For the full copyright and license information, please view the NOTICE
 * and LICENSE files that were distributed with this source code.
 */

namespace Klarna\Kp\Setup;

use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\UpgradeDataInterface;

class UpgradeData implements UpgradeDataInterface
{
    /**
     * Upgrades DB data for a module
     *
     * @param ModuleDataSetupInterface $installer
     * @param ModuleContextInterface   $context
     * @return void
     */
    public function upgrade(ModuleDataSetupInterface $installer, ModuleContextInterface $context)
    {
        $installer->startSetup();

        if (version_compare($context->getVersion(), '4.0.2', '<')) {
            $table = $installer->getTable('klarna_payments_quote');
            // Mark all quotes as inactive so that switch over to new payments endpoint happens
            $installer->getConnection()->update($table, ['is_active' => 0]);
        }
        $installer->endSetup();
    }
}
