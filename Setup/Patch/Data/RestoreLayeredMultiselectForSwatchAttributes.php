<?php

declare(strict_types=1);

namespace Venbhas\FilterMultiselect\Setup\Patch\Data;

use Magento\Catalog\Model\Product;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Setup\Patch\DataPatchInterface;

/**
 * Re-enables layered multiselect for filterable swatch attributes that were set to 0 on save.
 *
 * The admin UI form used to hide "Allow Multi-Select" for swatch_visual/swatch_text; POST then
 * lacked venbhas_layered_multiselect and HttpGetPostValuePlugin forced 0 on every save.
 */
class RestoreLayeredMultiselectForSwatchAttributes implements DataPatchInterface
{
    /**
     * @param ResourceConnection $resource Database connection pool.
     */
    public function __construct(
        private readonly ResourceConnection $resource
    ) {
    }

    /**
     * This patch runs after filterable attributes are initially flagged for layered multiselect.
     *
     * @return array
     */
    public static function getDependencies(): array
    {
        return [EnableLayeredMultiselectForFilterableAttributes::class];
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
     * Re-apply multiselect for swatch attributes incorrectly forced to 0 on admin save.
     *
     * @return void
     */
    public function apply(): void
    {
        $connection = $this->resource->getConnection();
        $cea = $this->resource->getTableName('catalog_eav_attribute');
        $ea = $this->resource->getTableName('eav_attribute');
        $eet = $this->resource->getTableName('eav_entity_type');

        $productTypeId = $connection->fetchOne(
            $connection->select()
                ->from($eet, ['entity_type_id'])
                ->where('entity_type_code = ?', Product::ENTITY)
        );
        if (!$productTypeId) {
            return;
        }

        $typeId = (int) $productTypeId;
        $select = $connection->select()
            ->from(['cea' => $cea], ['attribute_id'])
            ->joinInner(
                ['ea' => $ea],
                'ea.attribute_id = cea.attribute_id AND ' .
                $connection->quoteInto('ea.entity_type_id = ?', $typeId),
                []
            )
            ->where('cea.is_filterable > ?', 0)
            ->where('ea.frontend_input IN (?)', ['swatch_visual', 'swatch_text'])
            ->where('cea.venbhas_layered_multiselect = ?', 0);

        $attributeIds = $connection->fetchCol($select);
        if ($attributeIds !== []) {
            $connection->update(
                $cea,
                ['venbhas_layered_multiselect' => 1],
                ['attribute_id IN (?)' => $attributeIds]
            );
        }
    }
}
