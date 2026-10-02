/*
 * Pavva Widgets – Listă interactivă cu imagine
 * Creat de imagient pentru Pavva.
 */
(function ($) {
	'use strict';

	var REDUCE = !!(window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches);

	function isEditMode() {
		return !!(window.elementorFrontend && typeof window.elementorFrontend.isEditMode === 'function' && window.elementorFrontend.isEditMode());
	}

	function toMs(value, fallback) {
		var v = String(value || '').trim();
		if (!v) return fallback;
		var n = parseFloat(v);
		if (isNaN(n)) return fallback;
		return /ms$/.test(v) ? n : (/s$/.test(v) ? n * 1000 : n);
	}

	function initTabs(root) {
		if (!root || root.__pfReady) return;
		var list = root.querySelector('.pf-list');
		var items = Array.prototype.slice.call(root.querySelectorAll('.pf-item'));
		var imgs = Array.prototype.slice.call(root.querySelectorAll('.pf-img'));
		var dots = Array.prototype.slice.call(root.querySelectorAll('.pf-dot'));
		if (!list || !items.length) return;
		root.__pfReady = true;

		var o = {};
		try { o = JSON.parse(root.getAttribute('data-pf') || '{}'); } catch (e) { o = {}; }
		var trigger = o.trigger === 'click' ? 'click' : 'hover';
		var autoplay = !!o.autoplay && items.length > 1 && !REDUCE && !isEditMode();
		var delay = Math.max(1500, parseInt(o.delay, 10) || 5000);

		var current = 0;
		var animating = false;
		var busyTimer = null;

		/* 0 = alături, 1 = carduri glisante, 2 = acordeon (din Elementor, per dispozitiv) */
		function mode() {
			return parseInt(getComputedStyle(root).getPropertyValue('--pf-m'), 10) || 0;
		}
		function duration() {
			return REDUCE ? 0 : toMs(getComputedStyle(root).getPropertyValue('--pf-dur'), 900);
		}

		/* ---------- Schimbarea imaginii ---------- */
		function setImage(index) {
			var next = imgs[index];
			var cur = imgs[current];
			if (!next || next === cur) return;

			if (animating) {
				/* o tranziție e încă în curs: imaginea „de sub” rămâne ultima complet vizibilă */
				if (cur) cur.classList.remove('is-active');
			} else {
				imgs.forEach(function (im) { im.classList.remove('is-prev'); });
				if (cur) {
					cur.classList.remove('is-active');
					cur.classList.add('is-prev');
				}
			}
			next.classList.remove('is-prev');
			void next.offsetWidth;
			next.classList.add('is-active');
			imgs.forEach(function (im) {
				im.setAttribute('aria-hidden', im === next ? 'false' : 'true');
			});

			animating = true;
			clearTimeout(busyTimer);
			busyTimer = setTimeout(function () {
				animating = false;
				imgs.forEach(function (im) {
					if (!im.classList.contains('is-active')) im.classList.remove('is-prev');
				});
			}, duration() + 120);
		}

		/* ---------- Activare element ---------- */
		function activate(index, opts) {
			opts = opts || {};
			if (index < 0 || index >= items.length || index === current) {
				if (opts.scroll && index === current) scrollToCard(index);
				return;
			}
			setImage(index);
			items.forEach(function (it, k) {
				var on = k === index;
				it.classList.toggle('is-active', on);
				it.setAttribute('aria-selected', on ? 'true' : 'false');
				it.setAttribute('tabindex', on ? '0' : '-1');
			});
			dots.forEach(function (d, k) {
				d.classList.toggle('is-active', k === index);
				if (k === index) d.setAttribute('aria-current', 'true');
				else d.removeAttribute('aria-current');
			});
			current = index;
			if (opts.scroll) scrollToCard(index);
			if (!opts.fromAutoplay) restartAutoplay();
		}

		/* ---------- Carduri glisante (mobil) ---------- */
		var scrollLock = false, scrollLockTimer = null, scrollTicking = false;

		/* pregătește spațiul de la final, ca ultimul card să se alinieze la stânga */
		function syncSwipe() {
			var on = mode() === 1;
			root.classList.toggle('pf-swiping', on);
			if (on) {
				var cs = getComputedStyle(list);
				var gap = parseFloat(cs.columnGap || cs.gap) || 0;
				root.style.setProperty('--pf-gap-px', gap + 'px');
			}
		}
		syncSwipe();
		var swipeResize = null;
		window.addEventListener('resize', function () {
			clearTimeout(swipeResize);
			swipeResize = setTimeout(function () {
				syncSwipe();
				if (mode() === 1) scrollToCard(current);
			}, 150);
		});

		function padLeft() {
			return parseFloat(getComputedStyle(list).paddingLeft) || 0;
		}
		function scrollToCard(index) {
			if (mode() !== 1) return;
			var card = items[index];
			if (!card) return;
			var max = list.scrollWidth - list.clientWidth;
			var left = Math.max(0, Math.min(card.offsetLeft - padLeft(), max));
			scrollLock = true;
			clearTimeout(scrollLockTimer);
			scrollLockTimer = setTimeout(function () { scrollLock = false; }, 700);
			list.scrollTo({ left: left, behavior: REDUCE ? 'auto' : 'smooth' });
		}
		function cardInView() {
			var pl = padLeft();
			var max = list.scrollWidth - list.clientWidth;
			if (max > 0 && list.scrollLeft >= max - 2) return items.length - 1; /* la capăt: ultimul card */
			var best = 0, bestDist = Infinity;
			items.forEach(function (it, k) {
				var d = Math.abs(it.offsetLeft - pl - list.scrollLeft);
				if (d < bestDist) { bestDist = d; best = k; }
			});
			return best;
		}
		list.addEventListener('scroll', function () {
			if (mode() !== 1 || scrollLock) return;
			if (scrollTicking) return;
			scrollTicking = true;
			requestAnimationFrame(function () {
				scrollTicking = false;
				var k = cardInView();
				if (k !== current) activate(k);
			});
		}, { passive: true });

		/* ---------- Hover / click / tastatură ---------- */
		var hoverTimer = null;
		items.forEach(function (it, k) {
			it.addEventListener('mouseenter', function () {
				if (trigger !== 'hover' || mode() !== 0) return;
				clearTimeout(hoverTimer);
				hoverTimer = setTimeout(function () { activate(k); }, 70);
			});
			it.addEventListener('mouseleave', function () { clearTimeout(hoverTimer); });
			it.addEventListener('click', function () {
				clearTimeout(hoverTimer);
				activate(k, { scroll: true });
			});
			it.addEventListener('keydown', function (e) {
				var target = null;
				if (e.key === 'ArrowDown' || e.key === 'ArrowRight') target = (k + 1) % items.length;
				else if (e.key === 'ArrowUp' || e.key === 'ArrowLeft') target = (k - 1 + items.length) % items.length;
				else if (e.key === 'Home') target = 0;
				else if (e.key === 'End') target = items.length - 1;
				else if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); activate(k, { scroll: true }); return; }
				if (target === null) return;
				e.preventDefault();
				activate(target, { scroll: true });
				items[target].focus({ preventScroll: true });
			});
		});
		dots.forEach(function (d, k) {
			d.addEventListener('click', function () { activate(k, { scroll: true }); });
		});

		/* ---------- Autoplay (opțional) ---------- */
		var apTimer = null, paused = false, inView = true, touchPause = null;
		function stopAutoplay() { clearTimeout(apTimer); apTimer = null; }
		function restartAutoplay() {
			stopAutoplay();
			if (!autoplay || paused || !inView || document.hidden) return;
			apTimer = setTimeout(function () {
				activate((current + 1) % items.length, { scroll: true, fromAutoplay: true });
				restartAutoplay();
			}, delay);
		}
		if (autoplay) {
			root.addEventListener('mouseenter', function () { paused = true; stopAutoplay(); });
			root.addEventListener('mouseleave', function () { paused = false; restartAutoplay(); });
			root.addEventListener('focusin', function () { paused = true; stopAutoplay(); });
			root.addEventListener('focusout', function (e) {
				if (!root.contains(e.relatedTarget)) { paused = false; restartAutoplay(); }
			});
			list.addEventListener('touchstart', function () {
				paused = true;
				stopAutoplay();
				clearTimeout(touchPause);
				touchPause = setTimeout(function () { paused = false; restartAutoplay(); }, 8000);
			}, { passive: true });
			document.addEventListener('visibilitychange', function () {
				if (document.hidden) stopAutoplay(); else restartAutoplay();
			});
			if ('IntersectionObserver' in window) {
				new IntersectionObserver(function (entries) {
					inView = entries[0] ? entries[0].isIntersecting : true;
					if (inView) restartAutoplay(); else stopAutoplay();
				}, { threshold: 0.25 }).observe(root);
			}
			restartAutoplay();
		}

		root.__pf = { activate: activate, get index() { return current; }, get mode() { return mode(); } };
	}

	function initAll(context) {
		var scope = context || document;
		if (scope.classList && scope.classList.contains('pf')) initTabs(scope);
		Array.prototype.forEach.call(scope.querySelectorAll('.pf'), initTabs);
	}

	if ($) {
		$(window).on('elementor/frontend/init', function () {
			if (window.elementorFrontend && window.elementorFrontend.hooks) {
				window.elementorFrontend.hooks.addAction('frontend/element_ready/pavva_feature_tabs.default', function ($scope) {
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
