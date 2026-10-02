/*
 * Pavva Widgets – Acordeon produs
 * Creat de imagient pentru Pavva.
 *
 * Folosește <details>: funcționează și fără JavaScript.
 * Scriptul adaugă doar animația de deschidere / închidere.
 */
(function ($) {
	'use strict';

	var REDUCE = !!(window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches);

	function toMs(value, fallback) {
		var v = String(value || '').trim();
		if (!v) return fallback;
		var n = parseFloat(v);
		if (isNaN(n)) return fallback;
		return /ms$/.test(v) ? n : (/s$/.test(v) ? n * 1000 : n);
	}

	function initAccordion(root) {
		if (!root || root.__pvaReady) return;
		var items = Array.prototype.slice.call(root.querySelectorAll(':scope > .pva-item'));
		if (!items.length) return;
		root.__pvaReady = true;

		var o = {};
		try { o = JSON.parse(root.getAttribute('data-pva') || '{}'); } catch (e) { o = {}; }

		function duration() {
			return REDUCE ? 0 : toMs(getComputedStyle(root).getPropertyValue('--pva-speed'), 450);
		}
		function ease() {
			return getComputedStyle(root).getPropertyValue('--pva-ease').trim() || 'ease';
		}

		function finish(item) {
			var panel = item.querySelector('.pva-panel');
			if (item.__pvaAnim) { item.__pvaAnim.cancel(); item.__pvaAnim = null; }
			if (panel) { panel.style.height = ''; panel.style.overflow = ''; }
		}

		function open(item) {
			var panel = item.querySelector('.pva-panel');
			var start = panel ? panel.offsetHeight : 0;
			finish(item);
			item.classList.remove('is-closing');
			item.open = true;
			var d = duration();
			if (!panel || !d || !panel.animate) return;
			var end = panel.scrollHeight;
			panel.style.overflow = 'hidden';
			var anim = panel.animate([{ height: start + 'px' }, { height: end + 'px' }], { duration: d, easing: ease() });
			item.__pvaAnim = anim;
			anim.onfinish = function () { item.__pvaAnim = null; panel.style.overflow = ''; };
		}

		function close(item) {
			var panel = item.querySelector('.pva-panel');
			var d = duration();
			if (!panel || !d || !panel.animate) { finish(item); item.open = false; return; }
			var start = panel.offsetHeight;
			finish(item);
			item.classList.add('is-closing');
			panel.style.overflow = 'hidden';
			var anim = panel.animate([{ height: start + 'px' }, { height: '0px' }], { duration: d, easing: ease() });
			item.__pvaAnim = anim;
			anim.onfinish = function () {
				item.__pvaAnim = null;
				item.open = false;
				item.classList.remove('is-closing');
				panel.style.overflow = '';
			};
		}

		items.forEach(function (item) {
			var summary = item.querySelector(':scope > .pva-title');
			if (!summary) return;
			summary.addEventListener('click', function (e) {
				e.preventDefault();
				var isOpen = item.open && !item.classList.contains('is-closing');
				if (isOpen) {
					close(item);
				} else {
					if (o.single) {
						items.forEach(function (other) {
							if (other !== item && other.open && !other.classList.contains('is-closing')) close(other);
						});
					}
					open(item);
				}
			});
		});
	}

	function initAll(context) {
		var scope = context || document;
		if (scope.classList && scope.classList.contains('pva')) initAccordion(scope);
		Array.prototype.forEach.call(scope.querySelectorAll('.pva'), initAccordion);
	}

	if ($) {
		$(window).on('elementor/frontend/init', function () {
			if (window.elementorFrontend && window.elementorFrontend.hooks) {
				window.elementorFrontend.hooks.addAction('frontend/element_ready/pavva_acordeon_produs.default', function ($scope) {
					initAll($scope && $scope[0] ? $scope[0] : document);
				});
			}
		});
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', function () { initAll(document); });
	} else {
		initAll(document);
	}
})(window.jQuery);
