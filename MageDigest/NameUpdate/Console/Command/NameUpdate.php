<?php

namespace MageDigest\NameUpdate\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Store\Model\WebsiteFactory;
use Symfony\Component\Console\Input\InputOption;


/**
 * Class NameUpdate
 * @package MageDigest\ImageUpdate\Console\Command
 */
class NameUpdate extends Command
{
    /**
     *
     */
    const LIMIT = "limit";

    /**
     * @var \Magento\Framework\App\State
     */
    private $appState;
    /**
     * @var \Magento\Catalog\Model\ProductFactory
     */
    private $productFactory;
    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;
    /**
     * @var CollectionFactory
     */
    private $productCollectionFactory;
    /**
     * @var WebsiteFactory
     */
    private $websiteFactory;


    /**
     * ImageUpdate constructor.
     * @param \Magento\Framework\App\State $appState
     * @param WebsiteFactory $websiteFactory
     * @param ProductRepositoryInterface $productRepository
     * @param CollectionFactory $productCollectionFactory
     * @param \Magento\Catalog\Model\ProductFactory $productFactory
     * @param null $name
     */
    public function __construct(
        \Magento\Framework\App\State $appState,
        WebsiteFactory $websiteFactory,
        ProductRepositoryInterface $productRepository,
        CollectionFactory $productCollectionFactory,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        $name = null
    )
    {
        $this->appState = $appState;
        $this->websiteFactory = $websiteFactory;
        $this->productFactory = $productFactory;
        $this->productRepository = $productRepository;
        $this->productCollectionFactory = $productCollectionFactory;
        parent::__construct($name);
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $options = [
            new InputOption(
                self::LIMIT,
                null,
                InputOption::VALUE_REQUIRED,
                'Limit'
            )
        ];
        $this->setName('products:update-name')
            ->setDescription('Update names of the product')
            ->setDefinition($options);
        parent::configure();
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|void|null
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->appState->setAreaCode(\Magento\Framework\App\Area::AREA_ADMINHTML);
        $limitParameterValue = $input->getOption(self::LIMIT);
        $this->validateParameter(self::LIMIT, $limitParameterValue);
        if (!$limitParameterValue) {
            throw new \Exception("Values required for arguments");
        }

        try {
            $productCollection = $this->productCollectionFactory->create();
            $productCollection->addAttributeToFilter('name_updated', 0)
                ->getSelect()
                ->limit($limitParameterValue);
            $productIds = array();
            foreach ($productCollection as $product) {
                $productIds[] = $product->getId();
                $productModel = $this->productFactory->create()->load($product->getId());
                $productModel->setName($product->getSku() . '-' . $product->getId());
                $productModel->setNameUpdated(1);
                $productModel->save();
            }
            $output->writeln("<info>Names upated successfully</info>");
            $output->write($productIds);
            return \Magento\Framework\Console\Cli::RETURN_SUCCESS;
        } catch (\Exception $e) {
            $output->write('<error>' . $e->getMessage() . '</error>');
        }
    }

    /**
     * Validates parameter
     * @param $key
     * @param $value
     * @throws \Exception
     */
    private function validateParameter($key, $value)
    {
        if ($value && !is_numeric($value)) {
            throw new \Exception("Disallowed variable type for {$key} argument");
        }
    }
}