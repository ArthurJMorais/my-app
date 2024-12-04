<?php

namespace Scandiweb\Test\Setup\Patch\Data;

use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Api\Data\ProductInterfaceFactory;
use Magento\Framework\App\State;
use Magento\Eav\Setup\EavSetup;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Type;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryApi\Api\Data\SourceItemInterfaceFactory;
use Magento\InventoryApi\Api\SourceItemsSaveInterface;
use Magento\Catalog\Api\CategoryLinkManagementInterface;

class CreateTestProduct implements DataPatchInterface
{
    /** @var ProductInterfaceFactory */
    protected ProductInterfaceFactory $productFactory;

    /** @var ProductRepositoryInterface */
    protected ProductRepositoryInterface $productRepository;

    /** @var State */
    protected State $appState;

    /** @var EavSetup */
    protected EavSetup $eavSetup;

    /** @var StoreManagerInterface */
    protected StoreManagerInterface $storeManager;

    /** @var SourceItemInterfaceFactory */
    protected SourceItemInterfaceFactory $sourceItemFactory;

    /** @var SourceItemsSaveInterface */
    protected SourceItemsSaveInterface $sourceItemsSave;

    /** @var CategoryLinkManagementInterface */
    protected CategoryLinkManagementInterface $categoryLinkManagement;

    protected array $sourceItems = [];

    /**
     * @param ProductInterfaceFactory $productFactory
     * @param ProductRepositoryInterface $productRepository
     * @param State $appState
     * @param EavSetup $eavSetup
     * @param StoreManagerInterface $storeManager
     * @param SourceItemInterfaceFactory $sourceItemFactory
     * @param SourceItemsSaveInterface $sourceItemsSave
     * @param CategoryLinkManagementInterface $categoryLinkManagement
     */
    public function __construct(
        ProductInterfaceFactory $productFactory,
        ProductRepositoryInterface $productRepository,
        State $appState,
        EavSetup $eavSetup,
        StoreManagerInterface $storeManager,
        SourceItemInterfaceFactory $sourceItemFactory,
        SourceItemsSaveInterface $sourceItemsSave,
        CategoryLinkManagementInterface $categoryLinkManagement
    ) {
        $this->productFactory = $productFactory;
        $this->productRepository = $productRepository;
        $this->appState = $appState;
        $this->eavSetup = $eavSetup;
        $this->storeManager = $storeManager;
        $this->sourceItemFactory = $sourceItemFactory;
        $this->sourceItemsSave = $sourceItemsSave;
        $this->categoryLinkManagement = $categoryLinkManagement;
    }

    /**
     * @inheritdoc
     */
    public function apply(): void
    {
        $this->appState->emulateAreaCode('adminhtml', [$this, 'execute']);
    }

    /**
     * Add test product
     *
     * @return void
     */
    public function execute(): void
    {
        $product = $this->productFactory->create();

        if ($product->getIdBySku('test-product')) {
            return;
        }

        $attributeSetId = $this->eavSetup->getAttributeSetId(Product::ENTITY, 'Default');
        $websiteIDs = [$this->storeManager->getStore()->getWebsiteId()];

        $product->setTypeId(Type::TYPE_SIMPLE)
            ->setAttributeSetId($attributeSetId)
            ->setWebsiteIds($websiteIDs)
            ->setName('Test product')
            ->setSku('test-product')
            ->setUrlKey('test-product')
            ->setPrice(9.99)
            ->setVisibility(Visibility::VISIBILITY_BOTH)
            ->setStatus(Status::STATUS_ENABLED)
            ->setStockData(['use_config_manage_stock' => 1, 'is_qty_decimal' => 0, 'is_in_stock' => 1]);
        $product = $this->productRepository->save($product);

        $sourceItem = $this->sourceItemFactory->create();
        $sourceItem->setSourceCode('default');
        $sourceItem->setQuantity(100);
        $sourceItem->setSku($product->getSku());
        $sourceItem->setStatus(SourceItemInterface::STATUS_IN_STOCK);
        $this->sourceItems[] = $sourceItem;

        $this->sourceItemsSave->execute($this->sourceItems);

        $this->categoryLinkManagement->assignProductToCategories($product->getSku(), [11]);
    }

    /**
     * @inheritdoc
     */
    public function getAliases(): array
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public static function getDependencies(): array
    {
        return [];
    }
}
