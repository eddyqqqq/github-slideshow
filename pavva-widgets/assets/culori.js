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
		var mode = o.mode === 'fade' || o.mode === 'none' ? o.mode : 'slide';
		if (REDUCE) mode = 'none';
		if (mode === 'slide' && !stage.animate) mode = 'fade';

		var current = 0;
		var leaveTimer = null;
		var running = [];

		function duration() {
			return toMs(getComputedStyle(root).getPropertyValue('--pvc-speed'), 600);
		}
		function easing() {
			return getComputedStyle(root).getPropertyValue('--pvc-ease').trim() || 'ease';
		}

		function eager(slide) {
			var img = slide.querySelector('img');
			if (img && img.loading === 'lazy') img.loading = 'eager';
		}

		/* dir: 1 = vine din dreapta (următoarea), -1 = vine din stânga */
		function go(index, dir) {
			var n = slides.length;
			index = ((index % n) + n) % n;
			if (index === current) return;
			if (!dir) dir = index > current ? 1 : -1;

			var prev = slides[current];
			var next = slides[index];
			eager(next);

			/* o tranziție încă în curs se încheie imediat */
			running.forEach(function (a) { try { a.finish(); } catch (e) { /* nimic */ } });
			running = [];
			clearTimeout(leaveTimer);
			slides.forEach(function (sl) { sl.classList.remove('is-leaving'); });

			prev.classList.remove('is-active');
			prev.setAttribute('aria-hidden', 'true');
			next.classList.add('is-active');
			next.removeAttribute('aria-hidden');

			if (mode === 'slide') {
				var d = duration();
				var ease = easing();
				prev.classList.add('is-leaving');
				var a1 = next.animate(
					[{ transform: 'translateX(' + (dir * 100) + '%)' }, { transform: 'translateX(0)' }],
					{ duration: d, easing: ease }
				);
				var a2 = prev.animate(
					[{ transform: 'translateX(0)' }, { transform: 'translateX(' + (-dir * 100) + '%)' }],
					{ duration: d, easing: ease }
				);
				running = [a1, a2];
				a2.onfinish = function () { prev.classList.remove('is-leaving'); };
			} else if (mode === 'fade') {
				prev.classList.add('is-leaving');
				leaveTimer = setTimeout(function () { prev.classList.remove('is-leaving'); }, duration() + 50);
			}

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

		/* săgeți (circular) */
		var prevBtn = root.querySelector('.pvc-prev');
		var nextBtn = root.querySelector('.pvc-next');
		if (prevBtn) prevBtn.addEventListener('click', function (e) { e.preventDefault(); go(current - 1, -1); });
		if (nextBtn) nextBtn.addEventListener('click', function (e) { e.preventDefault(); go(current + 1, 1); });

		/* tastatură pe imaginea mare */
		stage.addEventListener('keydown', function (e) {
			if (e.key === 'ArrowLeft') { e.preventDefault(); go(current - 1, -1); }
			else if (e.key === 'ArrowRight') { e.preventDefault(); go(current + 1, 1); }
		});

		/* glisare: deget (swipe) și / sau mouse (drag) */
		function swipeEnd(dx, dy) {
			if (Math.abs(dx) > 40 && Math.abs(dx) > Math.abs(dy) * 1.2) {
				if (dx < 0) go(current + 1, 1); else go(current - 1, -1);
			}
		}

		if (o.swipe) {
			var tx = 0, ty = 0, touching = false;
			stage.addEventListener('touchstart', function (e) {
				if (!e.touches || e.touches.length !== 1) { touching = false; return; }
				tx = e.touches[0].clientX;
				ty = e.touches[0].clientY;
				touching = true;
			}, { passive: true });
			stage.addEventListener('touchend', function (e) {
				if (!touching || !e.changedTouches || !e.changedTouches.length) return;
				touching = false;
				swipeEnd(e.changedTouches[0].clientX - tx, e.changedTouches[0].clientY - ty);
			}, { passive: true });
		}

		if (o.drag) {
			root.classList.add('pvc-draggable');
			var mx = 0, my = 0, dragging = false;
			stage.addEventListener('mousedown', function (e) {
				if (e.button !== 0 || e.target.closest('.pvc-arrow')) return;
				mx = e.clientX;
				my = e.clientY;
				dragging = true;
				stage.classList.add('is-dragging');
				e.preventDefault();
			});
			window.addEventListener('mouseup', function (e) {
				if (!dragging) return;
				dragging = false;
				stage.classList.remove('is-dragging');
				swipeEnd(e.clientX - mx, e.clientY - my);
			});
		}

		/* imaginile celorlalte culori se încarcă când secțiunea se apropie de ecran */
		if ('IntersectionObserver' in window) {
			var io = new IntersectionObserver(function (entries) {
				if (entries[0] && entries[0].isIntersecting) {
					slides.forEach(eager);
					io.disconnect();
				}
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
