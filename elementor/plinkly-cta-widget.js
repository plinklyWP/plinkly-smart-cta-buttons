;(function ($) {

	function updateColor(wrapper, domain) {

		if (!window.PlinkLyCompanyColors || !window.PlinkLyCompanyColors[domain]) return;

		var color = window.PlinkLyCompanyColors[domain].color;
		if (!/^#([0-9A-F]{3}){1,2}$/i.test(color)) return;

		var $input = wrapper.find('[data-setting="custom_color"] input').first();
		if (!$input.length) return;

		$input.val(color)
		      .trigger('input change keyup');      // يُزامن Elementor فوراً

		if (typeof $input.wpColorPicker === 'function') {
			try { $input.wpColorPicker('color', color); } catch (e) {}
		}
	}

	$(document).on('input change', '[data-setting="link"] input', function () {
		var url = $(this).val() || '';
		if (url && !/^https?:\/\//i.test(url)) url = 'https://' + url;

		try {
			var domain  = new URL(url).hostname.replace(/^www\./, '');
			var wrapper = $(this).closest('.elementor-repeater-item');
			updateColor(wrapper, domain);
		} catch (_) { console.warn('PlinkLy: Invalid URL'); }
	});

})(jQuery);
