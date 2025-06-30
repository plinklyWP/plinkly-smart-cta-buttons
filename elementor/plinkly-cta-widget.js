;(function ($) {

	/**
     * Initialize color fields in a single Repeater item.
     * Applies to both custom_color and ab_custom_color (if present).
     */
	function updateColor(wrapper, domain) {

		if ( !window.PlinkLyCompanyColors || !window.PlinkLyCompanyColors[domain] )
			return;

		var color = window.PlinkLyCompanyColors[domain].color;
		if ( !/^#([0-9A-F]{3}){1,2}$/i.test(color) )
			return;

		['custom_color', 'ab_custom_color'].forEach(function (setting) {

			var $field = wrapper.find('[data-setting="' + setting + '"] input').first();
			if ( !$field.length ) return;

			$field.val(color).trigger('input change keyup'); // يزامن Elementor فورًا

			// مزامنة الـ colour-picker إذا كان مُفعّلًا
			if ( typeof $field.wpColorPicker === 'function' ) {
				try { $field.wpColorPicker('color', color); } catch (e) {}
			}
		});
	}

	/**
     * When the link field changes, extract the domain and set the colors.
     */
	$(document).on('input change', '[data-setting="link"] input', function () {

		var val = $(this).val() || '';
		if ( val && !/^https?:\/\//i.test(val) ) val = 'https://' + val;

		try {
			var domain  = new URL(val).hostname.replace(/^www\./i, '');
			var wrapper = $(this).closest('.elementor-repeater-item');
			updateColor(wrapper, domain);
		} catch (_) {
			// عنوان غير صالح – تجاهَل
		}
	});

})(jQuery);
