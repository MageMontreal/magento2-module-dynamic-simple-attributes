<?php

namespace MageMontreal\DynamicSimpleAttributes\Block\System\Config\Form\Field;

class AttributesMap extends \Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray {

    /**
     * Enable the "Add after" button or not
     *
     * @var bool
     */
    protected $_addAfter = false;

    /**
     * Prepare to render
     *
     * @return void
     */
    protected function _prepareToRender()
    {
        $this->addColumn('attribute', array('label' => __('Attribute Code')));
        $this->addColumn('selector', array('label' => __('CSS Selector')));
    }
}