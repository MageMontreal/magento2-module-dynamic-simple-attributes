var config = {
    config: {
        mixins: {
            'Magento_ConfigurableProduct/js/configurable': {
                'MageMontreal_DynamicSimpleAttributes/js/model/attributes': true
            },
			'Magento_Swatches/js/swatch-renderer': {
                'MageMontreal_DynamicSimpleAttributes/js/model/swatch/attributes': true
            }
        }
    }
};
