<?php
/**
 * CategoryAssignation
 *
 * @copyright Copyright (c) 2016 Staempfli AG
 * @author    juan.alonso@staempfli.com
 */

namespace Staempfli\CommerceImport\Model\Import\Product;

use Staempfli\CommerceImport\Model\Config;
use Staempfli\CommerceImport\Model\Config\Source\CategoryAssignationMode;
use Staempfli\CommerceImport\Model\ResourceModel\Category\CategoryAssignationResource;

class CategoryAssignation
{
    const BATCH_SIZE = 1000;
    /**
     * @var Config
     */
    private $config;
    /**
     * @var CategoryAssignationResource
     */
    private $categoryAssignationResource;

    public function __construct(
        Config $config,
        CategoryAssignationResource $categoryAssignationResource
    ) {
        $this->config = $config;
        $this->categoryAssignationResource = $categoryAssignationResource;
    }

    public function updateAssignationPosition()
    {
        $this->updateAssignationsInBatches();
    }

    private function updateAssignationsInBatches()
    {
        $batchPage = 1;
        try {
            do {
                $assignationIds = $this->categoryAssignationResource
                    ->getImportAssignationIds($batchPage, self::BATCH_SIZE);
                if (!empty($assignationIds)) {
                    $this->categoryAssignationResource->updateCategoryAssignations($assignationIds);
                    $batchPage++;
                }
            } while (count($assignationIds) == self::BATCH_SIZE);
        } catch (\Exception $e) {
            throw new \Exception(
                sprintf(
                    'Updating category product assignations failed with error: %s',
                    $e->getMessage()
                )
            );
        }
    }
}
