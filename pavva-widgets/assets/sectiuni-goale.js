/*
 * Pavva Widgets – secțiuni goale (rezervă pentru browsere vechi, fără :has)
 * Creat de imagient pentru Pavva.
 */
(function () {
	'use strict';
	var supportsHas = false;
	try { supportsHas = !!(window.CSS && CSS.supports && CSS.supports('selector(:has(a))')); } catch (e) { supportsHas = false; }

	var DECOR = ['elementor-widget-heading', 'elementor-widget-spacer', 'elementor-widget-divider'];

	function isEmpty(w) {
		if (w.classList.contains('pavva-w-is-empty')) return true;
		if (w.querySelector('.pavva-w-empty')) { w.classList.add('pavva-w-is-empty'); return true; }
		return false;
	}
	function isDecor(w) {
		return DECOR.some(function (c) { return w.classList.contains(c); });
	}

	function run() {
		var markers = document.querySelectorAll('.pavva-w-empty');
		Array.prototype.forEach.call(markers, function (m) {
			var w = m.closest('.elementor-widget');
			if (w) w.classList.add('pavva-w-is-empty');
		});
		if (supportsHas) return;
		var boxes = document.querySelectorAll('.e-con, .elementor-section');
		Array.prototype.forEach.call(boxes, function (box) {
			var widgets = Array.prototype.slice.call(box.querySelectorAll('.elementor-widget'));
			if (!widgets.some(isEmpty)) return;
			var hide = widgets.every(function (w) { return isEmpty(w) || isDecor(w); });
			if (hide) box.style.display = 'none';
		});
	}

	if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', run);
	else run();
})();
