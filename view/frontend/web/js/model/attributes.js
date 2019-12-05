define([
	'jquery',
	'mage/utils/wrapper',
	'mage/apply/main'
], function ($, wrapper, mage) {
	'use strict';
	return function(targetModule){
		var reloadPrice = targetModule.prototype._reloadPrice;
		targetModule.prototype.dynamic = {};
		targetModule.prototype.dynamicAttrs = [];

		var reloadPriceWrapper = wrapper.wrap(reloadPrice, function(original){
			var dynamic = this.options.spConfig.dynamic;

			for (var code in dynamic){
				if (dynamic.hasOwnProperty(code)) {
					var value = "",
						replace = false,
						attrs = [],
						$placeholder = $(code);

					if(!$placeholder.length) {
						continue;
					}

					if (this.simpleProduct && typeof dynamic[code][this.simpleProduct] != 'undefined') {
						value = dynamic[code][this.simpleProduct].value;
						replace = dynamic[code][this.simpleProduct].replace;
						attrs = dynamic[code][this.simpleProduct].attrs;
					}

					if(!value) {
						value = dynamic[code]['default'].value;
						replace = dynamic[code]['default'].replace;
					}

					if(value) {
						if(replace) {
							$placeholder.replaceWith(value);
						} else {
							$placeholder.html(value);
						}
					}

					if(attrs != undefined) {
						for(var a in attrs) {
							$placeholder.attr(a, attrs[a]);
						}
					}
				}
			}

			mage.apply();

			return original();
		});

		targetModule.prototype._reloadPrice = reloadPriceWrapper;
		return targetModule;
	};
});
