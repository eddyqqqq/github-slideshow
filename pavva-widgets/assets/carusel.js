/*
 * Pavva Widgets – Carusel produse
 * Creat de imagient pentru Pavva.
 */
/* Carusel produse – comportament (drag, alunecare lină, bară de progres). */
(function ($) {
	'use strict';

	var reduceMotion = !!(window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches);

	function easeOutQuart(t) { return 1 - Math.pow(1 - t, 4); }

	function initCarousel(root) {
		if (!root || root.__ccReady) return;
		var track = root.querySelector('.cc-track');
		if (!track) return;
		root.__ccReady = true;

		var opts = {};
		try { opts = JSON.parse(root.getAttribute('data-cc') || '{}'); } catch (e) { opts = {}; }
		var allowDrag = opts.drag !== false;
		var useInertia = opts.inertia !== false;
		var baseDuration = parseInt(opts.duration, 10) || 700;
		var barMode = opts.barMode === 'move' ? 'move' : 'fill';

		var bar = root.querySelector('.cc-bar');
		var thumb = root.querySelector('.cc-thumb');

		function maxScroll() { return Math.max(0, track.scrollWidth - track.clientWidth); }

		/* ---------- Cât iese în dreapta până la marginea ecranului ---------- */
		function updateBleed() {
			var vw = document.documentElement.clientWidth; /* fără scrollbar, deci fără scroll orizontal */
			var rect = root.getBoundingClientRect();
			var bleed = Math.max(0, Math.floor(vw - rect.right));
			root.style.setProperty('--cc-bleed', bleed + 'px');
			updateEndSpace();
		}

		/* spațiu automat după ultimul card: dacă pista ajunge la marginea din dreapta a ecranului,
		   ultimul card se oprește la aceeași distanță de margine ca primul card în stânga */
		function updateEndSpace() {
			var vw = document.documentElement.clientWidth;
			var r = track.getBoundingClientRect();
			var leftGap = Math.max(0, r.left);
			var rightGap = Math.max(0, vw - r.right);
			var end = Math.max(0, Math.round(leftGap - rightGap));
			root.style.setProperty('--cc-end-auto', end + 'px');
		}
		updateBleed();
		window.addEventListener('resize', function () { updateBleed(); requestUpdate(); });
		if ('ResizeObserver' in window) {
			new ResizeObserver(function () { updateBleed(); requestUpdate(); }).observe(root);
		}

		/* ---------- Bara de progres ---------- */
		var ticking = false;
		function updateBar() {
			ticking = false;
			if (!bar || !thumb) return;
			var max = maxScroll();
			bar.style.visibility = max > 1 ? '' : 'hidden';
			if (barMode === 'fill') {
				var fill = track.scrollWidth > 0 ? (track.scrollLeft + track.clientWidth) / track.scrollWidth : 1;
				if (max - track.scrollLeft < 2) fill = 1;
				thumb.style.width = '100%';
				thumb.style.transform = 'scaleX(' + Math.min(1, Math.max(0, fill)) + ')';
			} else {
				var barW = bar.clientWidth;
				var w = Math.max(40, barW * (track.clientWidth / Math.max(1, track.scrollWidth)));
				var x = max > 0 ? (track.scrollLeft / max) * (barW - w) : 0;
				thumb.style.width = w + 'px';
				thumb.style.transform = 'translateX(' + x + 'px)';
			}
		}
		function requestUpdate() {
			if (!ticking) { ticking = true; window.requestAnimationFrame(updateBar); }
		}
		track.addEventListener('scroll', requestUpdate, { passive: true });
		if ('ResizeObserver' in window) {
			var ro = new ResizeObserver(requestUpdate);
			ro.observe(track);
			Array.prototype.forEach.call(track.children, function (c) { ro.observe(c); });
		} else {
			window.addEventListener('resize', requestUpdate);
		}
		track.addEventListener('load', requestUpdate, true); /* când se încarcă pozele */
		updateBar();

		/* ---------- Poziții carduri ---------- */
		function cardStep() {
			var cards = track.children;
			if (cards.length < 2) return track.clientWidth || 1;
			return (cards[1].offsetLeft - cards[0].offsetLeft) || 1;
		}
		function nearestCard(left) {
			var step = cardStep();
			var target = Math.round(left / step) * step;
			return Math.max(0, Math.min(target, maxScroll()));
		}

		/* ---------- Animație lină ---------- */
		var animId = null;
		function stopAnim() {
			if (animId) { window.cancelAnimationFrame(animId); animId = null; }
		}
		function animateTo(target) {
			stopAnim();
			var from = track.scrollLeft;
			var dist = target - from;
			if (reduceMotion || Math.abs(dist) < 1) {
				track.scrollLeft = target;
				track.classList.remove('is-dragging');
				return;
			}
			var duration = Math.min(baseDuration * 1.6, baseDuration * 0.8 + Math.abs(dist) * 0.35);
			track.classList.add('is-dragging');
			var start = null;
			function frame(now) {
				if (start === null) start = now;
				var t = Math.min(1, (now - start) / duration);
				track.scrollLeft = from + dist * easeOutQuart(t);
				if (t < 1) {
					animId = window.requestAnimationFrame(frame);
				} else {
					animId = null;
					track.classList.remove('is-dragging');
				}
			}
			animId = window.requestAnimationFrame(frame);
		}

		/* ---------- Trage cu mouse-ul (pe telefon: swipe nativ) ---------- */
		var down = false, moved = false, startX = 0, startScroll = 0, samples = [];

		function onMove(e) {
			if (!down) return;
			var dx = e.clientX - startX;
			if (!moved && Math.abs(dx) > 4) {
				moved = true;
				track.classList.add('is-dragging');
			}
			if (!moved) return;
			track.scrollLeft = startScroll - dx;
			var now = performance.now();
			samples.push({ x: e.clientX, t: now });
			while (samples.length > 2 && now - samples[0].t > 100) samples.shift();
		}
		function onUp() {
			document.removeEventListener('pointermove', onMove);
			document.removeEventListener('pointerup', onUp);
			document.removeEventListener('pointercancel', onUp);
			if (!down) return;
			down = false;
			if (!moved) return;

			var velocity = 0;
			if (useInertia && samples.length > 1) {
				var first = samples[0], last = samples[samples.length - 1];
				velocity = (last.x - first.x) / Math.max(1, last.t - first.t);
				if (performance.now() - last.t > 120) velocity = 0;
			}
			var target = nearestCard(track.scrollLeft - velocity * 320);
			var dragDist = track.scrollLeft - startScroll;
			if (target === nearestCard(startScroll) && Math.abs(dragDist) > 40) {
				target = nearestCard(startScroll + (dragDist > 0 ? 1 : -1) * cardStep());
			}
			animateTo(target);
		}

		if (allowDrag) {
			track.addEventListener('pointerdown', function (e) {
				if (e.pointerType !== 'mouse' || e.button !== 0) return;
				stopAnim();
				down = true;
				moved = false;
				startX = e.clientX;
				startScroll = track.scrollLeft;
				samples = [{ x: e.clientX, t: performance.now() }];
				document.addEventListener('pointermove', onMove);
				document.addEventListener('pointerup', onUp);
				document.addEventListener('pointercancel', onUp);
			});
		}

		/* nu porni „drag”-ul nativ al linkurilor și imaginilor */
		track.addEventListener('dragstart', function (e) { e.preventDefault(); });

		/* nu deschide linkul dacă utilizatorul doar a tras */
		track.addEventListener('click', function (e) {
			if (moved) { e.preventDefault(); e.stopPropagation(); moved = false; }
		}, true);

		/* orice derulare manuală (roată, trackpad, tastatură) oprește animația */
		track.addEventListener('wheel', stopAnim, { passive: true });
		track.addEventListener('touchstart', stopAnim, { passive: true });

		/* săgeți tastatură: card cu card */
		track.addEventListener('keydown', function (e) {
			if (e.key !== 'ArrowRight' && e.key !== 'ArrowLeft') return;
			e.preventDefault();
			var dir = e.key === 'ArrowRight' ? 1 : -1;
			animateTo(nearestCard(nearestCard(track.scrollLeft) + dir * cardStep()));
		});

		/* click pe bară: mergi la poziția respectivă */
		if (bar) {
			bar.addEventListener('click', function (e) {
				var rect = bar.getBoundingClientRect();
				var ratio = (e.clientX - rect.left) / Math.max(1, rect.width);
				animateTo(nearestCard(ratio * maxScroll()));
			});
		}
	}

	function initAll(context) {
		var scope = context || document;
		if (scope.classList && scope.classList.contains('cc-carousel')) initCarousel(scope);
		Array.prototype.forEach.call(scope.querySelectorAll('.cc-carousel'), initCarousel);
	}

	/* În editorul Elementor și pe site */
	if ($) {
		$(window).on('elementor/frontend/init', function () {
			if (window.elementorFrontend && window.elementorFrontend.hooks) {
				window.elementorFrontend.hooks.addAction('frontend/element_ready/carusel_produse.default', function ($scope) {
					initAll($scope && $scope[0] ? $scope[0] : document);
				});
			}
		});
	}

	/* Rezervă: pornește oricum după încărcarea paginii */
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', function () { initAll(document); });
	} else {
		initAll(document);
	}
})(window.jQuery);
