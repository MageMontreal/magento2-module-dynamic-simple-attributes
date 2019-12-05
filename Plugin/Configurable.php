<?php

namespace MageMontreal\DynamicSimpleAttributes\Plugin;

use Magento\Store\Model\ScopeInterface;

class Configurable
{
    const ITEM_STOCK = '.product-info-stock-sku .stock';

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Magento\Framework\Serialize\Serializer\Json
     */
    private $serializer;

    /**
     * @var \Magento\Cms\Model\Template\FilterProvider
     */
    protected $filterProvider;

    /**
     * Catalog product
     *
     * @var \Magento\Catalog\Helper\Product
     */
    protected $catalogProduct = null;

    private $itemProps = [];

    /**
     * @var \Magento\ConfigurableProduct\Block\Product\View\Type\Configurable
     */
    private $subject = null;

    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Serialize\Serializer\Json $serializer,
        \Magento\Cms\Model\Template\FilterProvider $filterProvider,
        \Magento\Catalog\Helper\Product $catalogProduct
    ) {
        $this->serializer = $serializer;
        $this->filterProvider = $filterProvider;
        $this->catalogProduct = $catalogProduct;
        $this->scopeConfig = $scopeConfig;
    }

    public function getBlock() {
        return $this->subject;
    }

    public function aroundGetAllowProducts(\Magento\ConfigurableProduct\Block\Product\View\Type\Configurable $subject, callable $proceed) {
        if($this->isEnabled() && $this->showOutOfStock()) {
            $skipSaleableCheck = $this->catalogProduct->getSkipSaleableCheck();
            $this->catalogProduct->setSkipSaleableCheck(true);

            $allowProducts = $proceed();

            $this->catalogProduct->setSkipSaleableCheck($skipSaleableCheck);

            return $allowProducts;
        }

        return  $proceed();
    }

    public function afterGetJsonConfig(\Magento\ConfigurableProduct\Block\Product\View\Type\Configurable $subject, $result) {
        if($this->isEnabled()) {
            $this->subject = $subject;

            $this->loadPropsArray();

            if (!empty($this->itemProps)) {
                $jsonResult = $this->serializer->unserialize($result);
                $jsonResult = $this->addItemProps($subject->getProduct(), 'default', $jsonResult);

                foreach ($subject->getAllowProducts() as $simpleProduct) {
                    $jsonResult = $this->addItemProps($simpleProduct, $simpleProduct->getId(), $jsonResult);
                }

                $result = $this->serializer->serialize($jsonResult);
            }
        }

        return $result;
    }

    public function addItemProps($product, $key, $jsonResult)
    {
        $attributes = $product->getTypeInstance()->getSetAttributes($product);
        foreach ($attributes as $attribute) {
            if (array_key_exists($attribute->getAttributeCode(), $this->itemProps)) {
                $code = $attribute->getAttributeCode();
                $selectors = $this->itemProps[$code];

                if (!is_array($selectors)) {
                    $selectors = [$selectors];
                }

                foreach ($selectors as $selector) {
                    $value = $this->getAttributeValue($product, $attribute);
                    if ($value) {
                        $jsonResult['dynamic'][$selector][$key] = $value;
                    }
                }

            }
        }

        if($this->includeStock()) {
            $availability = $this->getBlock()->getLayout()
                ->createBlock('Magento\Catalog\Block\Product\AbstractProduct')
                ->setTemplate('Magento_Catalog::product/view/type/default.phtml')
                ->setData('product', $product)
                ->toHtml();

            $jsonResult['dynamic'][self::ITEM_STOCK][$key] = [
                'value' => $availability,
                'replace' => true
            ];
        }

        return $jsonResult;
    }

    public function getAttributeValue($product, $attribute) {
        $value = $attribute->getFrontend()->getValue($product);
        if ($value) {
            return [
                'value' => $this->filterProvider->getPageFilter()->filter($value)
            ];
        }

        return null;
    }

    private function loadPropsArray() {
        $itemProps = $this->scopeConfig->getValue('catalog/dynamicsimpleattributes/attributes_map', ScopeInterface::SCOPE_STORE);

        if (!empty($itemProps)) {
            $itemProps = $this->serializer->unserialize($itemProps);
            foreach ($itemProps as $prop) {
                if(!isset($this->itemProps[$prop['attribute']])) {
                    $this->itemProps[$prop['attribute']] = [];
                }
                $this->itemProps[$prop['attribute']][] = $prop['selector'];
            }
        }
    }

    private function isEnabled() {
        return (bool) $this->scopeConfig->getValue('catalog/dynamicsimpleattributes/enabled', ScopeInterface::SCOPE_STORE);
    }

    private function includeStock() {
        return (bool) $this->scopeConfig->getValue('catalog/dynamicsimpleattributes/include_stock', ScopeInterface::SCOPE_STORE);
    }

    private function showOutOfStock() {
        return (bool) $this->scopeConfig->getValue('catalog/dynamicsimpleattributes/show_out_of_stock', ScopeInterface::SCOPE_STORE);
    }
}