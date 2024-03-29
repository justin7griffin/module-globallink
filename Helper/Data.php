<?php
/**
 * TransPerfect_GlobalLink
 *
 * @category   TransPerfect
 * @package    TransPerfect_GlobalLink
 * @author     Eugene Monakov <emonakov@robofirm.com>
 */

namespace TransPerfect\GlobalLink\Helper;

use Magento\Store\Model\StoreManagerInterface;
use \TransPerfect\GlobalLink\Model\ResourceModel\Category\Attribute\CollectionCustomFactory as CategoryAttributeCollectionFactory;
use \TransPerfect\GlobalLink\Model\ResourceModel\Product\Attribute\CollectionCustomFactory as ProductAttributeCollectionFactory;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Indexer\Model\Indexer\CollectionFactory as IndexerCollectionFactory;
use Magento\Indexer\Model\IndexerFactory as IndexerFactory;
use Magento\Cms\Model\ResourceModel\Page\CollectionFactory as PageCollectionFactory;
use Magento\Cms\Model\ResourceModel\Block\CollectionFactory as BlockCollectionFactory;
/**
 * Class Data
 *
 * @package TransPerfect\GlobalLink\Helper
 */
class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @var \Magento\Eav\Model\Config
     */
    protected $eavConfig;

    /**
     * @var \Magento\Eav\Model\ResourceModel\Entity\Attribute
     */
    protected $eavAttribute;

    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    protected $resource;

    /**
     * @var \TransPerfect\GlobalLink\Helper\Ui\Logger
     */
    protected $logger;

    /**
     * @var \Magento\Store\Model\StoreManager
     */
    protected $storeManager;

    /**
     * @var \TransPerfect\GlobalLink\Model\TranslationService
     */
    protected $translationService;

    /**
     * @var array
     */
    protected $sysLocales;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Category\Attribute\CollectionFactory
     */
    protected $categoryAttributeCollectionFactory;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory
     */
    protected $productAttributeCollectionFactory;

    /**
     * @var ProductCollectionFactory
     */
    protected $productCollectionFactory;

    /**
     * @var \Magento\Framework\App\ProductMetadataInterface
     */
    protected $productMetadata;

    /**
     * @var \Magento\Indexer\Model\IndexerFactory
     */
    protected $indexerFactory;
    /**
     * @var \Magento\Indexer\Model\Indexer\CollectionFactory
     */
    protected $indexerCollectionFactory;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \TransPerfect\GlobalLink\Model\FieldProductCategory $productFieldModel
     */
    protected $productFieldModel;
    protected $entityAttribute;
    /**
     * @var \Magento\Cms\Model\ResourceModel\Page\CollectionFactory
     */
    protected $pageCollectionFactory;
    /**
     * @var \Magento\Cms\Model\ResourceModel\Block\CollectionFactory
     */
    protected $blockCollectionFactory;

    const LOGGING_LEVEL_DEBUG = 0;
    const LOGGING_LEVEL_INFO = 1;
    const LOGGING_LEVEL_ERROR = 2;

    /**
     * Object types
     */
    const CATALOG_CATEGORY_TYPE_ID = 3;
    const CATALOG_PRODUCT_TYPE_ID = 4;
    const PRODUCT_ATTRIBUTE_TYPE_ID = 11;
    const CMS_PAGE_TYPE_ID = 12;
    const CMS_BLOCK_TYPE_ID = 13;
    const CUSTOMER_ATTRIBUTE_TYPE_ID = 14;

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Eav\Model\ResourceModel\Entity\Attribute $eavAttribute,
        \Magento\Framework\App\ResourceConnection $resource,
        \Magento\Eav\Model\Config $eavConfig,
        StoreManagerInterface $storeManager,
        \TransPerfect\GlobalLink\Model\TranslationService $translationService,
        \Magento\Framework\Locale\ListsInterface $localeLists,
        CategoryAttributeCollectionFactory $categoryAttributeCollectionFactory,
        ProductAttributeCollectionFactory $productAttributeCollectionFactory,
        ProductCollectionFactory $productCollectionFactory,
        \Magento\Eav\Api\AttributeSetRepositoryInterface $attributeSet,
        \Magento\Framework\App\ProductMetadataInterface $productMetadata,
        \Magento\Indexer\Model\IndexerFactory $indexerFactory,
        \Magento\Indexer\Model\Indexer\CollectionFactory $indexerCollectionFactory,
        \TransPerfect\GlobalLink\Model\FieldProductCategory $productFieldModel,
        \TransPerfect\GlobalLink\Model\Entity\Attribute $entityAttribute,
        PageCollectionFactory $pageCollectionFactory,
        BlockCollectionFactory $blockCollectionFactory
    ) {
        $this->eavConfig = $eavConfig;
        $this->resource = $resource;
        $this->eavAttribute = $eavAttribute;
        $this->storeManager = $storeManager;
        $this->translationService = $translationService;
        $this->localeLists = $localeLists;
        $this->createSysLocaleList();
        $this->categoryAttributeCollectionFactory = $categoryAttributeCollectionFactory;
        $this->productAttributeCollectionFactory = $productAttributeCollectionFactory;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->attributeSet = $attributeSet;
        $this->productMetadata = $productMetadata;
        $this->indexerFactory = $indexerFactory;
        $this->indexerCollectionFactory = $indexerCollectionFactory;
        $this->productFieldModel = $productFieldModel;
        $this->entityAttribute = $entityAttribute;
        $this->pageCollectionFactory = $pageCollectionFactory;
        $this->blockCollectionFactory = $blockCollectionFactory;
        parent::__construct($context);
    }
	
	/*
     * @return project short codes
     */
    public function getProjectShortCodes(){
        return explode(",", $this->scopeConfig->getValue('globallink/general/project_short_codes',  \Magento\Store\Model\ScopeInterface::SCOPE_STORE ));
    }

    /**
     * @return int
     */
    public function getStoreId($typeId, $id)
    {
        $defaultStore = $this->storeManager->getDefaultStoreView()->getId();
        $isEnterprise = $this->isEnterprise();
        switch ($typeId) {
            case Data::CMS_PAGE_TYPE_ID:
                $collection = $this->pageCollectionFactory->create();
                if($isEnterprise) {
                    $collection->getSelect()->join(['store_table' => $collection->getTable('cms_page_store')],
                        "main_table.row_id = store_table.row_id",
                        []);
                }
                else{
                    $collection->getSelect()->join(['store_table' => $collection->getTable('cms_page_store')],
                        "main_table.page_id = store_table.page_id",
                        []);
                }
                foreach ($collection as $entity) {
                    if ($entity->getData('page_id') == $id) {
                        return $entity->getData('store_id')[0];
                    }
                }
                return $defaultStore;
            case Data::CMS_BLOCK_TYPE_ID:
                $collection = $this->blockCollectionFactory->create();
                if($isEnterprise) {
                    $collection->getSelect()->join(['store_table' => $collection->getTable('cms_block_store')],
                        "main_table.row_id = store_table.row_id",
                        []);
                }
                else{
                    $collection->getSelect()->join(['store_table' => $collection->getTable('cms_block_store')],
                        "main_table.block_id = store_table.block_id",
                        []);
                }
                foreach ($collection as $entity) {
                    if ($entity->getData('block_id') == $id) {
                        return $entity->getData('store_id')[0];
                    }
                }
                return $defaultStore;
        }
    }
    /**
     * @return boolean
     */
    public function hasDifferentStores($typeId, $ids){
        $defaultStore = $this->storeManager->getDefaultStoreView()->getId();
        $isEnterprise = $this->isEnterprise();
        switch($typeId){
            case Data::CMS_PAGE_TYPE_ID:
                $storeViewArray = array();
                $collection = $this->pageCollectionFactory->create();
                if($isEnterprise) {
                    $collection->getSelect()->join(['store_table' => $collection->getTable('cms_page_store')],
                        "main_table.row_id = store_table.row_id",
                        []);
                }
                else{
                    $collection->getSelect()->join(['store_table' => $collection->getTable('cms_page_store')],
                        "main_table.page_id = store_table.page_id",
                        []);
                }
                foreach($ids as $id) {
                    foreach ($collection as $entity) {
                        if ($entity->getData('page_id') == $id){
                            $storeViewArray[] = $entity->getData('store_id')[0];
                        }
                    }
                }
                if(count(array_unique($storeViewArray)) > 2){
                    return true;
                }
                else if(count(array_unique($storeViewArray)) == 2 && !(in_array($defaultStore, $storeViewArray) && in_array('0', $storeViewArray))){
                    return true;
                }
                else{
                    return false;
                }
                break;
            case Data::CMS_BLOCK_TYPE_ID:
                $storeViewArray = array();
                $collection = $this->blockCollectionFactory->create();
                if($isEnterprise) {
                    $collection->getSelect()->join(['store_table' => $collection->getTable('cms_block_store')],
                        "main_table.row_id = store_table.row_id",
                        []);
                }
                else{
                    $collection->getSelect()->join(['store_table' => $collection->getTable('cms_block_store')],
                        "main_table.block_id = store_table.block_id",
                        []);
                }
                foreach($ids as $id) {
                    foreach ($collection as $entity) {
                        if ($entity->getData('block_id') == $id){
                            $storeViewArray[] = $entity->getData('store_id')[0];
                        }
                    }
                }
                if(count(array_unique($storeViewArray)) > 2){
                    return true;
                }
                else if(count(array_unique($storeViewArray)) == 2 && !(in_array($defaultStore, $storeViewArray) && in_array('0', $storeViewArray))){
                    return true;
                }
                else{
                    return false;
                }
                break;
        }
    }
    public function getDefaultStoreViewIds($typeId, $ids){
        $defaultStore = $this->storeManager->getDefaultStoreView()->getId();
        switch($typeId){
            case Data::CMS_PAGE_TYPE_ID:
                $newIds = array();
                $collection = $this->pageCollectionFactory->create();
                $collection->getSelect()->join(['store_table' => $collection->getTable('cms_page_store')],
                    "main_table.row_id = store_table.row_id",
                    []);
                foreach($ids as $id) {
                    foreach ($collection as $entity) {
                        if ($entity->getData('page_id') == $id){
                            if($entity->getData('store_id')[0] != '0' && $entity->getData('store_id')[0] != $defaultStore){
                                $identifier = $entity->getData('identifier');
                                foreach($collection as $innerEntity){
                                    if($identifier == $innerEntity->getIdentifier() && ($innerEntity->getData('store_id')[0] == '0' || $innerEntity->getData('store_id')[0] == $defaultStore)){
                                        $newIds[] = $innerEntity->getData('page_id');
                                    }
                                }
                            }
                            else{
                                $newIds[] = $id;
                            }
                        }
                    }
                }
                return array_unique($newIds);
                break;
            case Data::CMS_BLOCK_TYPE_ID:
                $newIds = array();
                $collection = $this->blockCollectionFactory->create();
                $collection->getSelect()->join(['store_table' => $collection->getTable('cms_block_store')],
                    "main_table.row_id = store_table.row_id",
                    []);
                foreach($ids as $id) {
                    foreach ($collection as $entity) {
                        if ($entity->getData('block_id') == $id){
                            if($entity->getData('store_id')[0] != '0' && $entity->getData('store_id')[0] != $defaultStore){
                                $identifier = $entity->getData('identifier');
                                foreach($collection as $innerEntity){
                                    if($identifier == $innerEntity->getIdentifier() && ($innerEntity->getData('store_id')[0] == '0' || $innerEntity->getData('store_id')[0] == $defaultStore)){
                                        $newIds[] = $innerEntity->getData('block_id');
                                    }
                                    //Put an else here if we want to resolve this and still translate
                                }
                            }
                            else{
                                $newIds[] = $id;
                            }
                        }
                    }
                }
                return array_unique($newIds);
                break;
        }
    }
    /**
     * @return boolean
     */
    public function defaultStoreSelected(){
        if($this->storeManager->getDefaultStoreView()->getId() == $this->storeManager->getStore()->getId()){
            return true;
        }
        return false;
    }
    /**
     * @return array
     */
    public function mapObjectTypeToModel()
    {
        return [
            self::CATALOG_CATEGORY_TYPE_ID => [
                'class' => \Magento\Catalog\Model\Category::class,
                'messages' => [
                    'form_action' => __('Send for Translation Form - Categories'),
                    'send_action' => __('Send for Translation - Categories'),
                    'config_action' => __('Field Configuration - Categories'),
                ]
            ],
            self::CATALOG_PRODUCT_TYPE_ID => [
                'class' => \Magento\Catalog\Model\Product::class,
                'messages' => [
                    'form_action' => __('Send for Translation Form - Products'),
                    'send_action' => __('Send for Translation - Products'),
                    'config_action' => __('Field Configuration - Products'),
                ]
            ],
            self::PRODUCT_ATTRIBUTE_TYPE_ID => [
                'class' => \Magento\Eav\Model\Attribute::class,
                'entity' => \Magento\Catalog\Model\Product::ENTITY,
                'messages' => [
                    'form_action' => __('Send for Translation Form - Product Attributes'),
                    'send_action' => __('Send for Translation - Product Attributes'),
                    'config_action' => __('Field Configuration - Product Attributes'),
                ]
            ],
            self::CMS_PAGE_TYPE_ID => [
                'class' => \Magento\Cms\Model\Page::class,
                'messages' => [
                    'form_action' => __('Send for Translation Form - CMS Pages'),
                    'send_action' => __('Send for Translation - CMS Pages'),
                    'config_action' => __('Field Configuration - CMS Pages'),
                    'config_add_action' => __('Field Configuration Add - CMS Pages'),
                    'config_delete_action' => __('Field Configuration Delete - CMS Pages')
                ]
            ],
            self::CMS_BLOCK_TYPE_ID => [
                'class' =>\Magento\Cms\Model\Block::class,
                'messages' => [
                    'form_action' => __('Send for Translation Form - CMS Blocks'),
                    'send_action' => __('Send for Translation - CMS Blocks'),
                    'config_action' => __('Field Configuration - CMS Blocks'),
                    'config_add_action' => __('Field Configuration Add - CMS Blocks'),
                    'config_delete_action' => __('Field Configuration Delete - CMS Blocks')
                ]
            ],
            self::CUSTOMER_ATTRIBUTE_TYPE_ID => [
                'class' => \Magento\Eav\Model\Attribute::class,
                'entity' => \Magento\Customer\Model\Customer::ENTITY,
                'messages' => [
                    'form_action' => __('Send for Translation Form - Customer Attributes'),
                    'send_action' => __('Send for Translation - Customer Attributes'),
                    'config_action' => __('Field Configuration - Customer Attributes'),
                ]
            ],
        ];
    }

    public function reIndexing()
    {
        if ($this->scopeConfig->getValue('globallink/general/reindexing', \Magento\Store\Model\ScopeInterface::SCOPE_STORE) == 1) {
            $indexerCollection = $this->indexerCollectionFactory->create();
            $ids = $indexerCollection->getAllIds();
            foreach ($ids as $id) {
                $idx = $this->indexerFactory->create()->load($id);
                $idx->reindexAll($id);
            }
        }
    }

    public function getEntityTypeOptionArray()
    {
        $options = [
            self::CATALOG_CATEGORY_TYPE_ID => __('Category'),
            self::CATALOG_PRODUCT_TYPE_ID => __('Product'),
            self::PRODUCT_ATTRIBUTE_TYPE_ID => __('Product Attribute'),
            self::CMS_PAGE_TYPE_ID => __('CMS Page'),
            self::CMS_BLOCK_TYPE_ID => __('CMS Block'),
        ];
        if ($this->isEnterprise()) {
            $options[self::CUSTOMER_ATTRIBUTE_TYPE_ID] = __('Customer Attribute');
        }

        return $options;
    }

    /**
     * check magento edition
     *
     * @return bool
     */
    public function isEnterprise()
    {
        return ($this->productMetadata->getEdition() == 'Enterprise');
    }

    /**
     * @param integer $type_id
     *
     * @return string
     */
    public function getModelForObjectType($type_id)
    {
        return $this->mapObjectTypeToModel()[$type_id];
    }

    /**
     * Get target locales
     *
     * @param bool $limitByStores - true: only locales which have assigned stores
     *                              false: all available locales
     * @param bool $includeSource - false: only target locales
     *                              true: target and source locales
     *
     * @return array
     *      [
     *          'en-US' => 'English (USA)',
     *          'de-DE' => 'German (Germany)',
     *          'fr-FR' => 'French (France)',
     *      ]
     */
    public function getLocales($limitByStores = false, $includeSource = false)
    {
        $targetLocales = $this->getAllLocales($includeSource);

        if ($limitByStores) {
            $storeLocales = [];
            $stores = $this->storeManager->getStores(true);
            foreach ($stores as $store) {
                if (!$store->getLocale()) {
                    continue;
                }
                $storeLocales[$store->getLocale()] = 1;
            }

            $targetLocales = array_intersect_key($targetLocales, $storeLocales);
        }

        return $targetLocales;
    }

    /**
     * Get all target locales for all projects. merge them all in one array
     *
     * @param bool $includeSource - false: only target locales
     *                              true: target and source locales
     *
     * @return array
     *      [
     *          'en-US' => 'English (USA)',
     *          'de-DE' => 'German (Germany)',
     *          'fr-FR' => 'French (France)',
     *      ]
     */
    protected function getAllLocales($includeSource = false)
    {
        try {
            $response = $this->translationService->requestGLExchange(
                '/services/ProjectService',
                'getUserProjects',
                [
                    'isSubProjectIncluded' => true,
                ]
            );
            $targetLocales = [];
            $sourceLocales = [];
            foreach ($response as $project) {
                foreach ($project->projectLanguageDirections as $direction) {
                    $targetLocales[$direction->targetLanguage->locale] = $direction->targetLanguage->value;
                    $sourceLocales[$direction->sourceLanguage->locale] = $direction->sourceLanguage->value;
                }
            }
        } catch (\Exception $e) {
            $targetLocales = [];
            $sourceLocales = [];
        }
        $locales = $targetLocales;

        if ($includeSource) {
            $locales = array_merge($targetLocales, $sourceLocales);
        }

        if (is_array($locales)) {
            asort($locales);
        }

        return $locales;
    }

    /**
     * get stores which given locale code been assigned for
     *
     * @param string $localeCode
     *
     * @return array
     */
    public function getStoresByLocaleCode($localeCode)
    {
        $returnStores = [];
        $stores = $this->storeManager->getStores(false);
        foreach ($stores as $storeId => $store) {
            if ($store->getLocale() == $localeCode) {
                $returnStores[] = $store;
            }
        }

        return $returnStores;
    }

    public function getLocaleOptionsArray()
    {
        $locales = $this->getLocales(true, true);
        return $locales;
    }

    /**
     * Get locales as options
     *
     * @param bool $withEmpty
     * @param null $exception
     *
     * @return array
     */
    public function getLocaleOptions($withEmpty = true)
    {
        $locales = $this->getLocales(false, true);

        $options = [];
        if ($withEmpty) {
            $options = [
                0 => ['label' => ' ', 'value' => 0]
            ];
        }
        foreach ($locales as $code => $label) {
            $locale = [
                'value' => $code,
                'label' => $label,
            ];

            array_push($options, $locale);
        }

        return $options;
    }

    /**
     * Get locale label
     *
     * @param $id
     *
     * @return string
     */
    public function getLocaleLabel($id)
    {
        $locales = $this->getLocales();

        if (isset($locales[$id])) {
            return $locales[$id];
        }

        return '';
    }

    /**
     * @param \TransPerfect\GlobalLink\Model\ResourceModel\Queue\Item\CollectionFactory $collectionFactory
     *
     * @return array
     */
    public function getSubmissionsArray($collectionFactory)
    {
        /** @todo rework this code to pass loaded collection from submission grid. Grid collection returns null at _prepareColumns */
        /** @var \TransPerfect\GlobalLink\Model\ResourceModel\Queue\Item\Collection $collection */
        $collection = $collectionFactory->create();
        $collection->load();
        $submissions = [];
        foreach ($collection as $submission) {
            $submissions[$submission->getSubmissionName()] = $submission->getSubmissionName();
        }
        return $submissions;
    }

    /**
     * @param $entity_type
     * @param $code
     *
     * @return int
     */
    public function getAttributeIdBeCode($entity_type, $code)
    {
        return $this->eavAttribute->getIdByCode($entity_type, $code);
    }

    /**
     * @param $collectionFactory
     * @param int $storeId
     * @param $ids
     * @param $excluded
     *
     * @return array
     */
    public function getEntityNames($collectionFactory, $storeId, $ids, $excluded = true)
    {
        /** @var \Magento\Catalog\Model\ResourceModel\Product\Collection $collection */
        $collection = $collectionFactory->create();
        $collection->addAttributeToSelect('name');
        if (is_array($excluded)) {
            $collection->addAttributeToFilter('entity_id', ['nin' => $excluded]);
        } elseif ($excluded !== false && !empty($ids)) {
            $collection->addAttributeToFilter('entity_id', ['in' => $ids]);
        }
        $collection->setStore($storeId);
        $names = [];
        foreach ($collection as $entity) {
            $names[$entity->getId()] = $entity->getName();
        }
        return $names;
    }

    /**
     * @param        $collectionFactory
     * @param int    $storeId
     * @param        $ids
     * @param        $type
     * @param string $label
     * @param mixed $excluded
     *
     * @return array
     */
    public function getOtherEntityNames($collectionFactory, $storeId, $ids, $type, $label = 'title', $excluded = true)
    {
        $names = [];
        $collection = $collectionFactory->create();
        if (is_array($excluded)) {
            $collection->addFieldToFilter("main_table.{$type}_id", ['nin' => $excluded]);
        } elseif ($excluded !== false && !empty($ids)) {
            $collection->addFieldToFilter("main_table.{$type}_id", ['in' => $ids]);
        }
        foreach ($collection as $entity) {
            $names[$entity->getId()] = $entity->getData($label);
        }
        return $names;
    }
    /**
     * @param        $collectionFactory
     * @param int    $storeId
     * @param        $ids
     * @param        $type
     * @param string $label
     * @param mixed $excluded
     *
     * @return boolean
     */
    public function differentStoresSelected($collectionFactory, $storeId, $ids, $type, $label = 'store_id', $excluded = true)
    {
        $storeIds = [];
        $collection = $collectionFactory->create();
        if (is_array($excluded)) {
            $collection->addFieldToFilter("main_table.{$type}_id", ['nin' => $excluded]);
        } elseif ($excluded !== false && !empty($ids)) {
            $collection->addFieldToFilter("main_table.{$type}_id", ['in' => $ids]);
        }
        foreach ($collection as $entity) {
            $storeIds[] = $entity->getData($label)[0];
        }
        foreach($storeIds as $current){
            foreach($storeIds as $inner){
                if($current != $inner){
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Updates translation configuration for eav attributes
     *
     * @param array $attributeIds
     * @param int $entity_type_id
     *
     * @return $this
     * @throws \Exception
     */
    public function updateAttributesTranslation($attributeIds, $entity_type_id)
    {
        $existingFieldRow = null;
        if (is_string($entity_type_id)) {
            $entity_type_id = $this->eavConfig->getEntityType($entity_type_id)->getId();
            $entityType = $this->eavConfig->getEntityType($entity_type_id);
        }

        $connection = $this->resource->getConnection();
        $table = $connection->getTableName('globallink_field_product_category');
        $connection->beginTransaction();
        $connection->update($table, ['include_in_translation' => 0], ['entity_type_id = ?' => $entity_type_id]);
        //$connection->update($table, ['include_in_translation' => 1], ['attribute_id IN (?)' => $attributeIds], true);
        try {
            $connection->commit();
        } catch (\Exception $e) {
            $connection->rollBack();
            throw $e;
        }
        /** @var \Magento\Framework\DB\Adapter\Pdo\Mysql $connection */
        foreach ($attributeIds as $attribute) {
            $existingFieldRow = $this->productFieldModel->getRecord($attribute);
            $existingEntity = $this->entityAttribute->getRecord($attribute);
            $existingFieldRow->setData('include_in_translation', 1);
            $existingFieldRow->setData('entity_attribute_id', $existingEntity->getEntityAttributeId());
            $existingFieldRow->setData('entity_type_id', $existingEntity->getData('entity_type_id'));
            $existingFieldRow->setData('attribute_set_id', $existingEntity->getData('attribute_set_id'));
            $existingFieldRow->setData('attribute_group_id', $existingEntity->getData('attribute_group_id'));
            $existingFieldRow->setData('attribute_id', $existingEntity->getData('attribute_id'));
            $existingFieldRow->save();
        }
        return $this;
    }

    /**
     * Get id of all stores without default store
     *
     * @return array
     */
    public function getAllStoresIds()
    {
        $result = [];
        $allStores = $this->storeManager->getStores(false);
        foreach ($allStores as $store) {
            $result[] = $store->getId();
        }

        return $result;
    }

    /**
     * check if var array or some kind of complex var
     *
     * @param mixed $var
     *
     * @return bool
     */
    public static function isComplex($var)
    {
        return is_array($var) ||
           ($var instanceof ArrayAccess &&
            $var instanceof Traversable &&
            $var instanceof Serializable &&
            $var instanceof Countable);
    }

    /**
     * create system locales array
     */
    protected function createSysLocaleList()
    {
        $locales = $this->localeLists->getOptionLocales();
        foreach ($locales as $locale) {
            $localeCode = str_replace('_', '-', $locale['value']);
            $this->sysLocales[$localeCode] = $locale['label'];
        }
    }

    /**
     * @param string $localeCode
     *
     * @return string
     */
    public function getCountrybyLocaleCode($localeCode)
    {
        if (!empty($this->sysLocales[$localeCode])) {
            return $this->sysLocales[$localeCode];
        }
        return '';
    }

    /**
     * @param int   $typeId
     * @param array $ids
     *
     * return array [
     *      'ok' => bool,
     *      'errorMessage' => array [string]
     *  ]
     */
    public function checkFieldsConfigured($typeId, $ids)
    {
        $result = ['ok' => true, 'errorMessages' => []];
        switch ($typeId) {
            case self::CATALOG_CATEGORY_TYPE_ID:
                $attributes = $this->categoryAttributeCollectionFactory->create();
                $attributes->appendFieldData();
                $categoryEntityTypeId = 3;
                $attributes->addFieldToFilter('globallink_field_product_category.entity_type_id', $categoryEntityTypeId);
                $attributes->addFieldToFilter('globallink_field_product_category.include_in_translation', 1);
                $result['ok'] = (bool) count($attributes);
                break;
            case self::CATALOG_PRODUCT_TYPE_ID:
                $products = $this->productCollectionFactory->create();
                $products->addAttributeToFilter('entity_id', ['in' => $ids]);
                $attrSets = [];
                foreach ($products as $product) {
                    $attrSets[$product->getAttributeSetId()] = $product->getAttributeSetId();
                }
                if (empty($attrSets)) {
                    $result['ok'] = false;
                    return $result;
                }
                foreach ($attrSets as $attributeSetId) {
                    $attributes = $this->productAttributeCollectionFactory->create();
                    $attributes->appendFieldData();
                    $attributes->addFieldToFilter('globallink_field_product_category.attribute_set_id', $attributeSetId);
                    $attributes->addFieldToFilter('globallink_field_product_category.include_in_translation', 1);
                    if (!count($attributes)) {
                        $attributeSetRepository = $this->attributeSet->get($attributeSetId);
                        $result['ok'] = false;
                        $result['errorMessages'][] = $attributeSetRepository->getAttributeSetName();
                    }
                }
                break;
        }

        return $result;
    }
}
