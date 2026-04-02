<?php

declare(strict_types=1);

namespace Venbhas\FilterMultiselect\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

/**
 * Preserve prior behavior: attributes already used in layered navigation default to multi-select on.
 * New attributes still default to 0 until merchants enable "Allow Multi-Select" on each attribute.
 */
class EnableLayeredMultiselectForFilterableAttributes implements DataPatchInterface
{
    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup Module data setup helper.
     */
    public function __construct(ModuleDataSetupInterface $moduleDataSetup)
    {
        $this->moduleDataSetup = $moduleDataSetup;
    }

    /**
     * No prerequisite data patches.
     *
     * @return array
     */
    public static function getDependencies(): array
    {
        return [];
    }

    /**
     * No alternative patch names for this migration.
     *
     * @return array
     */
    public function getAliases(): array
    {
        return [];
    }

    /**
     * Turn on layered multiselect for attributes already filterable in navigation.
     *
     * @return void
     */
    public function apply(): void
    {
        $setup = $this->moduleDataSetup;
        $setup->startSetup();
        $connection = $setup->getConnection();
        $table = $setup->getTable('catalog_eav_attribute');
        $connection->update(
            $table,
            ['venbhas_layered_multiselect' => 1],
            ['is_filterable > ?' => 0]
        );
        $setup->endSetup();
    }
}
