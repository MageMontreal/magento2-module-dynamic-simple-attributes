define([
    'jquery',
    'mage/utils/wrapper',
    'mage/apply/main'
], function ($, wrapper, mage) {
    'use strict';

    return function(targetModule){

        var updatePrice = targetModule.prototype._UpdatePrice;
        targetModule.prototype.dynamic = {};
        
        var updatePriceWrapper = wrapper.wrap(updatePrice, function(original){
            var dynamic = this.options.jsonConfig.dynamic;
            for (var code in dynamic){
                if (dynamic.hasOwnProperty(code)) {
                    var value = "";
                    var replace = false;
                    var $placeholder = $(code);
                    var allSelected = true;

                    if(!$placeholder.length) {
                        continue;
                    }

                    for(var i = 0; i<this.options.jsonConfig.attributes.length;i++){
                        if (!$('div.product-info-main .product-options-wrapper .swatch-attribute.' + this.options.jsonConfig.attributes[i].code).attr('option-selected')){
                            allSelected = false;
                        }
                    }

                    if(allSelected){
                        var products = this._CalcProducts();
                        var productId = products.slice().shift();
                        if(productId && this.options.jsonConfig.dynamic[code][productId]) {
                            value = this.options.jsonConfig.dynamic[code][productId].value;
                            replace = this.options.jsonConfig.dynamic[code][productId].replace;
                        }
                    }

                    if(!value) {
                        value = this.options.jsonConfig.dynamic[code]['default'].value;
                        replace = this.options.jsonConfig.dynamic[code]['default'].replace;
                    }

                    if(value) {
                        if(replace) {
                            $placeholder.replaceWith(value);
                        } else {
                            $placeholder.html(value);
                        }
                    }
                }
            }

            mage.apply();

            return original();
        });

        targetModule.prototype._UpdatePrice = updatePriceWrapper;
        return targetModule;
    };
});
