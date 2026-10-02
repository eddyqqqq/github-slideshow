/*
 * Pavva Widgets – Culori și finisaje
 * Creat de imagient pentru Pavva.
 */
(function ($) {
	'use strict';

	var REDUCE = !!(window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches);
	var CAN_HOVER = !!(window.matchMedia && window.matchMedia('(hover: hover) and (pointer: fine)').matches);

	function toMs(value, fallback) {
		var v = String(value || '').trim();
		if (!v) return fallback;
		var n = parseFloat(v);
		if (isNaN(n)) return fallback;
		return /ms$/.test(v) ? n : (/s$/.test(v) ? n * 1000 : n);
	}

	function initColors(root) {
		if (!root || root.__pvcReady) return;
		var stage = root.querySelector('.pvc-stage');
		var slides = Array.prototype.slice.call(root.querySelectorAll('.pvc-slide'));
		var thumbs = Array.prototype.slice.call(root.querySelectorAll('.pvc-thumb'));
		if (!stage || slides.length < 2) return;
		root.__pvcReady = true;

		var o = {};
		try { o = JSON.parse(root.getAttribute('data-pvc') || '{}'); } catch (e) { o = {}; }
		var hover = o.trigger === 'hover' && CAN_HOVER;

		var current = 0;
		var leaveTimer = null;

		function duration() {
			return REDUCE ? 0 : toMs(getComputedStyle(root).getPropertyValue('--pvc-speed'), 600);
		}

		function go(index) {
			var n = slides.length;
			index = ((index % n) + n) % n;
			if (index === current) return;

			var prev = slides[current];
			var next = slides[index];

			slides.forEach(function (sl) { sl.classList.remove('is-leaving'); });
			clearTimeout(leaveTimer);

			prev.classList.remove('is-active');
			prev.classList.add('is-leaving');
			prev.setAttribute('aria-hidden', 'true');
			next.classList.add('is-active');
			next.removeAttribute('aria-hidden');
			/* imaginea leneșă (lazy) se cere imediat */
			var img = next.querySelector('img');
			if (img && img.loading === 'lazy') img.loading = 'eager';

			leaveTimer = setTimeout(function () { prev.classList.remove('is-leaving'); }, duration() + 50);

			thumbs.forEach(function (t, i) {
				var on = i === index;
				t.classList.toggle('is-active', on);
				t.setAttribute('aria-pressed', on ? 'true' : 'false');
			});
			current = index;
		}

		/* thumbnails */
		thumbs.forEach(function (t, i) {
			t.addEventListener('click', function (e) { e.preventDefault(); go(i); });
			if (hover) t.addEventListener('mouseenter', function () { go(i); });
		});

		/* săgeți */
		var prevBtn = root.querySelector('.pvc-prev');
		var nextBtn = root.querySelector('.pvc-next');
		if (prevBtn) prevBtn.addEventListener('click', function (e) { e.preventDefault(); go(current - 1); });
		if (nextBtn) nextBtn.addEventListener('click', function (e) { e.preventDefault(); go(current + 1); });

		/* tastatură pe imaginea mare */
		stage.addEventListener('keydown', function (e) {
			if (e.key === 'ArrowLeft') { e.preventDefault(); go(current - 1); }
			else if (e.key === 'ArrowRight') { e.preventDefault(); go(current + 1); }
		});

		/* glisare pe telefon / tabletă */
		var sx = 0, sy = 0, tracking = false;
		stage.addEventListener('touchstart', function (e) {
			if (!e.touches || e.touches.length !== 1) { tracking = false; return; }
			sx = e.touches[0].clientX;
			sy = e.touches[0].clientY;
			tracking = true;
		}, { passive: true });
		stage.addEventListener('touchend', function (e) {
			if (!tracking || !e.changedTouches || !e.changedTouches.length) return;
			tracking = false;
			var dx = e.changedTouches[0].clientX - sx;
			var dy = e.changedTouches[0].clientY - sy;
			if (Math.abs(dx) > 40 && Math.abs(dx) > Math.abs(dy) * 1.2) go(current + (dx < 0 ? 1 : -1));
		}, { passive: true });

		/* imaginile celorlalte culori se încarcă după ce pagina e gata */
		function warm() {
			slides.forEach(function (sl) {
				var im = sl.querySelector('img');
				if (im && im.loading === 'lazy') im.loading = 'eager';
			});
		}
		if ('IntersectionObserver' in window) {
			var io = new IntersectionObserver(function (entries) {
				if (entries[0] && entries[0].isIntersecting) { warm(); io.disconnect(); }
			}, { rootMargin: '300px 0px' });
			io.observe(root);
		}
	}

	function initAll(context) {
		var scope = context || document;
		if (scope.classList && scope.classList.contains('pvc')) initColors(scope);
		Array.prototype.forEach.call(scope.querySelectorAll('.pvc'), initColors);
	}

	if ($) {
		$(window).on('elementor/frontend/init', function () {
			if (window.elementorFrontend && window.elementorFrontend.hooks) {
				window.elementorFrontend.hooks.addAction('frontend/element_ready/pavva_culori_finisaje.default', function ($scope) {
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
