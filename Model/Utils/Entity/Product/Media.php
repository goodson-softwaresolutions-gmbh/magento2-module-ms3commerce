<?php
/**
 * Copyright © 2016 Staempfli AG. All rights reserved.
 */

namespace Staempfli\CommerceImport\Model\Utils\Entity\Product;

use Magento\Catalog\Api\ProductAttributeMediaGalleryManagementInterface;
use Magento\Catalog\Model\ProductFactory;
use Magento\Framework\Filesystem\DirectoryList;
use Staempfli\CommerceImport\Model\Config;
use Staempfli\CommerceImport\Model\Utils\StoreFactory;

class Media
{
    /**
     * @var array
     */
    private $currentMediaValues = [];
    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product
     */
    private $productResource;
    /**
     * @var ProductFactory
     */
    private $productFactory;
    /**
     * @var StoreFactory
     */
    private $storeFactory;
    /**
     * @var ProductAttributeMediaGalleryManagementInterface
     */
    private $attributeMediaGalleryManagement;
    /**
     * @var Config
     */
    private $config;
    /**
     * @var \Magento\Framework\App\Filesystem\DirectoryList
     */
    private $directoryList;

    /**
     * @var \Magento\CatalogImportExport\Model\Import\Product\MediaGalleryProcessor
     */
    private $mediaProcessor;

    private const PRODUCT_IMAGE_RELATIVE_PATH = '/catalog/product';

    public function __construct(
        ProductFactory $productFactory,
        StoreFactory $storeFactory,
        Config $config,
        ProductAttributeMediaGalleryManagementInterface $attributeMediaGalleryManagement,
        \Magento\Framework\App\Filesystem\DirectoryList $directoryList,
        \Magento\CatalogImportExport\Model\Import\Product\MediaGalleryProcessor $mediaProcessor
    ) {
        $this->productFactory = $productFactory;
        $this->storeFactory = $storeFactory;
        $this->attributeMediaGalleryManagement = $attributeMediaGalleryManagement;
        $this->config = $config;
        $this->directoryList = $directoryList;
        $this->mediaProcessor = $mediaProcessor;
    }

    /**
     * @param array $products
     */
    public function deleteExistingMediaFiles(array $products = [])
    {
        $existingMedias = $this->mediaProcessor->getExistingImages($products);

        foreach ($existingMedias as $media) {
            foreach ($media as $file) {
                $fileName = $file['value'];
                $path = $this->directoryList->getPath(\Magento\Framework\App\Filesystem\DirectoryList::MEDIA) . self::PRODUCT_IMAGE_RELATIVE_PATH . $fileName;
                if (file_exists($path)) {
                    @unlink($path);
                }
            }
        }
    }

    /**
     * @param array $products
     */
    public function updateMediaValues(array $products = [])
    {
        $storeId = $this->storeFactory->create()->getCurrentStore()->getStoreId();
        foreach ($products as $product) {
            $mediaData = $this->getMediaDataFromProduct($product);

            if (!$mediaData['entity']) {
                continue;
            }

            $positions = [];
            foreach ([$storeId, 0] as $currentStoreId) {
                $mediaValues = $this->getCurrentMediaValuesByEntity($mediaData['entity'], $currentStoreId);
                $this->changeMediaStoreValues($mediaData, $mediaValues, $storeId, $positions);
            }
        }
    }

    /**
     * @param $mediaData
     * @param $mediaValues
     * @param $storeId
     * @param $positions
     */
    protected function changeMediaStoreValues($mediaData, $mediaValues, $storeId, &$positions)
    {
        if ($mediaValues) {
            foreach ($mediaValues['values'] as $row) {
                if (!in_array($row['position'], $positions)) {
                    if (in_array($row['image'], $mediaData['images'])) {
                        $data = $row;
                        $position = $data['position'];
                        $data['label'] = (isset($mediaData['labels'][$position]))
                            ? $mediaData['labels'][$position]
                            : '';
                        $data['store_id'] = $storeId;
                        unset($data['image']);
                        $positions[] = $position;

                        if ((int)$mediaValues['store'] === 0) {
                            unset($data['record_id']);
                        }
                        $this->saveMediaValues($data);
                    } else {
                        $this->removeMediaValues($row);
                    }
                }
            }
        }
    }

    /**
     * @param array $product
     * @return array
     */
    protected function getMediaDataFromProduct(array $product)
    {
        $images = [];
        $labels = [];
        $existing = [];
        $entity = null;
        if (isset($product['additional_images']) && isset($product['additional_image_labels'])) {
            foreach (explode($this->config->getMultipleValuesSeparator(), $product['additional_images']) as $image) {
                $images[] = pathinfo($image, PATHINFO_BASENAME);
            }

            $labels = explode($this->config->getMultipleValuesSeparator(), $product['additional_image_labels']);
            $existing = $this->attributeMediaGalleryManagement->getList($product['sku']);
            if (isset($existing[0])) {
                $entity = $existing[0]->getEntityId();
            }
        }
        return [
            'images' => $images,
            'labels' => $labels,
            'existing' => $existing,
            'entity' => $entity
        ];
    }

    /**
     * @param string $entity
     * @param int $store
     * @return array|bool
     */
    protected function getCurrentMediaValuesByEntity(string $entity, int $store = 0)
    {
        if (!$this->currentMediaValues) {
            $this->currentMediaValues = $this->getCurrentMediaValues();
        }
        if (isset($this->currentMediaValues[$entity]) && isset($this->currentMediaValues[$entity][$store])) {
            return [
                'store' => $store,
                'values' => $this->currentMediaValues[$entity][$store]
            ];
        } elseif (isset($this->currentMediaValues[$entity]) && isset($this->currentMediaValues[$entity][0])) {
            return [
                'store' => 0,
                'values' => $this->currentMediaValues[$entity][0]
            ];
        }
        return false;
    }

    /**
     * @return array
     */
    protected function getCurrentMediaValues()
    {
        $data = [];
        $mediaGallery = $this->getProductResource()->getTable('catalog_product_entity_media_gallery');
        $mediaGalleryValue = $this->getProductResource()->getTable('catalog_product_entity_media_gallery_value');
        $connection = $this->getProductResource()->getConnection();

        $result = $connection->fetchAssoc(
            $connection->select()->from(
                ["mgv" => $mediaGalleryValue],
                ['record_id', 'value_id', 'store_id', 'entity_id', 'label', 'position', 'disabled']
            )->joinLeft(
                ['mg' => $mediaGallery],
                'mg.value_id = mgv.value_id',
                ['image' => 'value']
            )
        );

        foreach ($result as $row) {
            $row['image'] = pathinfo($row['image'], PATHINFO_BASENAME);
            $data[$row['entity_id']][$row['store_id']][] = $row;
        }
        return $data;
    }

    /**
     * @param array $data
     */
    protected function saveMediaValues(array $data = [])
    {
        $table = $this->getProductResource()->getTable('catalog_product_entity_media_gallery_value');
        $connection = $this->getProductResource()->getConnection();
        if (isset($data['record_id'])) {
            $connection->update($table, $data, ['record_id=?' => $data['record_id']]);
        } else {
            $connection->insert($table, $data);
        }
    }

    /**
     * @param array $data
     */
    protected function removeMediaValues(array $data = [])
    {
        $table = $this->getProductResource()->getTable('catalog_product_entity_media_gallery');
        $connection = $this->getProductResource()->getConnection();
        $connection->delete($table, ['value_id=?' => $data['value_id']]);
    }

    /**
     * @return \Magento\Catalog\Model\ResourceModel\Product
     */
    protected function getProductResource()
    {
        if (!$this->productResource) {
            $product = $this->productFactory->create();
            $this->productResource = $product->getResource();
        }
        return $this->productResource;
    }
}
