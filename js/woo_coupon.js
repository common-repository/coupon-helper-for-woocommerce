jQuery(function () {
	if (window.location.search == "?post_type=shop_coupon") {
		jQuery('#discount_type').on('change', function () {

			if (jQuery(this).val() == "least_exp") {

				jQuery('.coupon_amount_field').slideUp();

			} else if (!jQuery('.coupon_amount_field').is(':visible')) {
				jQuery('.coupon_amount_field').slideDown();
			}

		});
	}

});