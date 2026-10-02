/*
 * Pavva Widgets – Slider header
 * Creat de imagient pentru Pavva.
 */
(function ($) {
	'use strict';

	var REDUCE = !!(window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches);
	var EASINGS = {
		smooth: 'cubic-bezier(.65, 0, .35, 1)',
		expo: 'cubic-bezier(.16, 1, .3, 1)',
		soft: 'cubic-bezier(.22, .61, .36, 1)',
		linear: 'linear'
	};

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

	function initHero(root) {
		if (!root || root.__psReady) return;
		var viewport = root.querySelector('.ps-viewport');
		if (!viewport) return;
		var slides = Array.prototype.filter.call(viewport.children, function (el) {
			return el.classList.contains('ps-slide');
		});
		var n = slides.length;
		if (!n) return;
		root.__psReady = true;

		var o = {};
		try { o = JSON.parse(root.getAttribute('data-ps') || '{}'); } catch (e) { o = {}; }

		var effect = (o.effect === 'fade' || o.effect === 'fade-zoom') ? o.effect : 'slide';
		var speed = REDUCE ? 0 : Math.max(0, Math.min(5000, parseInt(o.speed, 10) || 0));
		if (!REDUCE && o.speed === undefined) speed = 1100;
		var ease = EASINGS[o.easing] || EASINGS.smooth;
		var parallax = Math.max(0, Math.min(100, o.parallax === undefined ? 30 : parseFloat(o.parallax)));
		var loop = o.loop !== false;
		var autoplay = o.autoplay !== false && n > 1 && !REDUCE && !isEditMode();
		var delay = Math.max(1000, parseInt(o.delay, 10) || 6000);
		var pauseHover = o.pauseHover !== false;
		var allowDrag = o.drag !== false && n > 1;
		var kenburns = !!o.kenburns && !REDUCE;
		var textMode = o.textMode === 'slides' ? 'slides' : 'static';
		var wordsMode = o.textAnim === 'words' || o.textAnim === 'words-blur';
		var intro = wordsMode && !!o.intro && !REDUCE;

		var slider = root.querySelector('.ps-slider') || root;
		var prevBtn = root.querySelector('.ps-prev');
		var nextBtn = root.querySelector('.ps-next');
		var dots = Array.prototype.slice.call(root.querySelectorAll('.ps-dot'));
		var fracCurrent = root.querySelector('.ps-frac-current');
		/* câte un bloc de text (titlu + descriere) pentru fiecare slide */
		var groups = Array.prototype.slice.call(root.querySelectorAll('.ps-text-groups > .ps-group'));

		var current = 0;
		var animating = false;
		var pending = null;
		var cleanupTimer = null;

		function wrap(i) { return ((i % n) + n) % n; }
		function inRange(i) { return loop || (i >= 0 && i < n); }

		function resetSlide(el) {
			el.style.transition = '';
			el.style.transform = '';
			el.style.opacity = '';
			el.style.zIndex = '';
			el.style.visibility = '';
		}

		/* ---------- Zoom lent ---------- */
		function startKB(slide) {
			if (!kenburns) return;
			void slide.offsetWidth;
			slide.classList.add('is-kb');
		}
		function stopKB(slide) {
			if (!kenburns) return;
			var img = slide.querySelector('.ps-img');
			if (img) img.style.transition = 'none';
			slide.classList.remove('is-kb');
			if (img) { void img.offsetWidth; img.style.transition = ''; }
		}

		/* ---------- Stări UI ---------- */
		function updateUI(index) {
			slides.forEach(function (s, i) {
				var active = i === index;
				s.setAttribute('aria-hidden', active ? 'false' : 'true');
				var link = s.querySelector('a');
				if (link) {
					if (active) link.removeAttribute('tabindex');
					else link.setAttribute('tabindex', '-1');
				}
			});
			dots.forEach(function (d, i) {
				d.classList.toggle('is-active', i === index);
				if (i === index) d.setAttribute('aria-current', 'true');
				else d.removeAttribute('aria-current');
			});
			if (fracCurrent) fracCurrent.textContent = String(index + 1);
			if (!loop) {
				[[prevBtn, index === 0], [nextBtn, index === n - 1]].forEach(function (pair) {
					if (!pair[0]) return;
					pair[0].classList.toggle('is-disabled', pair[1]);
					pair[0].setAttribute('aria-disabled', pair[1] ? 'true' : 'false');
				});
			}
		}

		/* ---------- Cuvânt cu cuvânt: împarte textul în cuvinte ---------- */
		function splitWords(el) {
			if (el.__psSplit) return;
			el.__psSplit = true;
			var index = 0;
			(function walk(node) {
				Array.prototype.slice.call(node.childNodes).forEach(function (child) {
					if (child.nodeType === 3) {
						var parts = child.textContent.split(/(\s+)/);
						var frag = document.createDocumentFragment();
						parts.forEach(function (part) {
							if (!part) return;
							if (/^\s+$/.test(part)) {
								frag.appendChild(document.createTextNode(' '));
								return;
							}
							var w = document.createElement('span');
							w.className = 'ps-w';
							var inner = document.createElement('span');
							inner.className = 'ps-wi';
							inner.textContent = part;
							inner.style.setProperty('--wi', String(index++));
							w.appendChild(inner);
							frag.appendChild(w);
						});
						node.replaceChild(frag, child);
					} else if (child.nodeType === 1 && child.tagName !== 'BR') {
						walk(child);
					}
				});
			})(el);
			el.__psWords = index;
		}

		/* ---------- Text sincronizat ---------- */
		var textDur = toMs(getComputedStyle(root).getPropertyValue('--ps-text-dur'), 750);
		function leaveTime(el) {
			if (!wordsMode) return textDur + 400;
			var cs = getComputedStyle(root);
			var wd = toMs(cs.getPropertyValue('--ps-word-dur'), 1100);
			var ws = toMs(cs.getPropertyValue('--ps-word-step'), 55);
			return Math.round(wd * 0.42 + (el.__psWords || 10) * ws * 0.25 + 150);
		}
		function updateText(index) {
			if (textMode !== 'slides' || groups.length < 2) return;
			var next = groups[index];
			if (!next) return;
			var prev = null;
			groups.forEach(function (g) { if (g.classList.contains('is-active')) prev = g; });
			if (prev === next) return;
			root.classList.add('ps-changing');

			/* titlul și descrierea se schimbă fiecare în coloana lui; blocul rămâne pe loc */
			var leaveMax = 0;
			['.ps-title', '.ps-desc'].forEach(function (sel) {
				var t = swapEl(prev ? prev.querySelector(sel) : null, next.querySelector(sel));
				if (t > leaveMax) leaveMax = t;
			});

			if (prev) {
				prev.classList.remove('is-active');
				prev.classList.add('is-leaving');
				prev.setAttribute('aria-hidden', 'true');
				clearTimeout(prev.__psLeave);
				(function (g) {
					g.__psLeave = setTimeout(function () { g.classList.remove('is-leaving'); }, leaveMax);
				})(prev);
			}
			clearTimeout(next.__psLeave);
			next.classList.remove('is-leaving');
			next.classList.add('is-active');
			next.removeAttribute('aria-hidden');
		}

		/* schimbă un text cu altul; întoarce cât durează ieșirea celui vechi */
		function swapEl(prev, next) {
			if (next) clearTimeout(next.__psLeave);

			/* același text pe ambele slide-uri: schimb fără animație, ca să nu clipească */
			if (prev && next && prev.innerHTML.trim() === next.innerHTML.trim()) {
				prev.classList.add('is-instant');
				next.classList.add('is-instant');
				prev.classList.remove('is-active', 'is-leaving');
				next.classList.remove('is-leaving');
				next.classList.add('is-active');
				void next.offsetWidth;
				requestAnimationFrame(function () {
					prev.classList.remove('is-instant');
					next.classList.remove('is-instant');
				});
				return 0;
			}

			var t = 0;
			if (prev) {
				t = leaveTime(prev);
				prev.classList.remove('is-active');
				prev.classList.add('is-leaving');
				clearTimeout(prev.__psLeave);
				(function (el) {
					el.__psLeave = setTimeout(function () { el.classList.remove('is-leaving'); }, t);
				})(prev);
			}
			if (next) {
				next.classList.remove('is-leaving');
				next.classList.add('is-active');
			}
			return t;
		}

		/* ---------- Tranziții ---------- */
		function prepare(to, dir) {
			to.style.transition = 'none';
			to.style.visibility = 'visible';
			to.style.zIndex = '2';
			if (effect === 'slide') {
				to.style.transform = 'translate3d(' + (dir * 100) + '%, 0, 0)';
			} else {
				to.style.opacity = '0';
				to.style.transform = effect === 'fade-zoom' ? 'scale(1.06)' : '';
			}
		}

		function run(from, to, dir, duration, index) {
			animating = true;
			clearTimeout(cleanupTimer);
			void to.offsetWidth; /* aplică poziția de start înainte de animație */

			var tr = duration + 'ms ' + ease;
			from.style.visibility = 'visible';
			from.style.zIndex = '1';

			if (effect === 'slide') {
				from.style.transition = 'transform ' + tr;
				to.style.transition = 'transform ' + tr;
				to.style.transform = 'translate3d(0, 0, 0)';
				from.style.transform = 'translate3d(' + (-dir * parallax) + '%, 0, 0)';
			} else {
				to.style.transition = 'opacity ' + tr + ', transform ' + tr;
				to.style.opacity = '1';
				to.style.transform = effect === 'fade-zoom' ? 'scale(1)' : '';
			}

			from.classList.remove('is-active');
			to.classList.add('is-active');
			current = index;
			updateUI(current);
			updateText(current);
			startKB(to);

			cleanupTimer = setTimeout(function () {
				resetSlide(from);
				stopKB(from);
				resetSlide(to);
				animating = false;
				if (pending) {
					var p = pending;
					pending = null;
					goTo(p.index, p.dir);
				} else {
					scheduleAutoplay();
				}
			}, duration + 50);
		}

		function goTo(index, dir) {
			if (n < 2 || !inRange(index)) return;
			var target = wrap(index);
			if (target === current) return;
			if (animating) {
				pending = { index: index, dir: dir };
				return;
			}
			if (!dir) dir = target > current ? 1 : -1;
			stopAutoplay();
			var from = slides[current];
			var to = slides[target];
			prepare(to, dir);
			run(from, to, dir, speed, target);
		}
		function next() { goTo(current + 1, 1); }
		function prev() { goTo(current - 1, -1); }

		/* ---------- Autoplay ---------- */
		var apTimer = null, hovering = false, focusInside = false, inView = true, dragging = false;
		function stopAutoplay() { clearTimeout(apTimer); apTimer = null; }
		function scheduleAutoplay() {
			stopAutoplay();
			if (!autoplay || animating || dragging || !inView || document.hidden) return;
			if (pauseHover && (hovering || focusInside)) return;
			apTimer = setTimeout(function () {
				if (!loop && current === n - 1) goTo(0, -1);
				else next();
			}, delay);
		}
		slider.addEventListener('mouseenter', function () { hovering = true; if (pauseHover) stopAutoplay(); });
		slider.addEventListener('mouseleave', function () { hovering = false; scheduleAutoplay(); });
		slider.addEventListener('focusin', function () { focusInside = true; if (pauseHover) stopAutoplay(); });
		slider.addEventListener('focusout', function (e) {
			if (!slider.contains(e.relatedTarget)) { focusInside = false; scheduleAutoplay(); }
		});
		document.addEventListener('visibilitychange', function () {
			if (document.hidden) stopAutoplay(); else scheduleAutoplay();
		});
		if ('IntersectionObserver' in window) {
			new IntersectionObserver(function (entries) {
				inView = entries[0] ? entries[0].isIntersecting : true;
				if (inView) scheduleAutoplay(); else stopAutoplay();
			}, { threshold: 0.15 }).observe(slider);
		}

		/* ---------- Săgeți, puncte, tastatură ---------- */
		if (prevBtn) prevBtn.addEventListener('click', function () { if (!prevBtn.classList.contains('is-disabled')) prev(); });
		if (nextBtn) nextBtn.addEventListener('click', function () { if (!nextBtn.classList.contains('is-disabled')) next(); });
		dots.forEach(function (d, i) {
			d.addEventListener('click', function () { goTo(i, i > current ? 1 : -1); });
		});
		slider.addEventListener('keydown', function (e) {
			var tag = (e.target && e.target.tagName) || '';
			if (/INPUT|TEXTAREA|SELECT/.test(tag)) return;
			if (e.key === 'ArrowRight') { e.preventDefault(); next(); }
			else if (e.key === 'ArrowLeft') { e.preventDefault(); prev(); }
		});

		/* ---------- Drag cu mouse-ul / swipe ---------- */
		var down = false, pointerId = null, startX = 0, startY = 0, dx = 0;
		var lastX = 0, lastT = 0, velocity = 0;
		var dragTo = null, dragDir = 0, suppressClick = false;

		function onPointerMove(e) {
			if (!down || e.pointerId !== pointerId) return;
			var mx = e.clientX - startX;
			var my = e.clientY - startY;

			if (!dragging) {
				if (Math.abs(mx) < 8 && Math.abs(my) < 8) return;
				if (Math.abs(my) > Math.abs(mx)) { down = false; return; } /* derulare verticală: nu ne băgăm */
				dragging = true;
				viewport.classList.add('is-dragging');
				stopAutoplay();
				try { viewport.setPointerCapture(pointerId); } catch (err) { /* nimic */ }
			}
			if (e.cancelable) e.preventDefault();

			dx = mx;
			var now = performance.now();
			var dt = now - lastT;
			if (dt > 0) { velocity = (e.clientX - lastX) / dt; lastX = e.clientX; lastT = now; }

			var w = viewport.clientWidth || 1;
			var from = slides[current];
			var dir = dx < 0 ? 1 : -1;
			var targetIdx = current + dir;

			if (!inRange(targetIdx)) {
				if (dragTo) { resetSlide(dragTo); dragTo = null; }
				if (effect === 'slide') {
					from.style.transition = 'none';
					from.style.transform = 'translate3d(' + (dx * 0.25) + 'px, 0, 0)';
				}
				return;
			}

			var to = slides[wrap(targetIdx)];
			if (to !== dragTo || dir !== dragDir) {
				if (dragTo && dragTo !== to) resetSlide(dragTo);
				dragTo = to;
				dragDir = dir;
				prepare(to, dir);
			}

			if (effect === 'slide') {
				from.style.transition = 'none';
				from.style.transform = 'translate3d(' + (dx * parallax / 100) + 'px, 0, 0)';
				to.style.transform = 'translate3d(' + (dir * w + dx) + 'px, 0, 0)';
			} else {
				var p = Math.min(1, Math.abs(dx) / w);
				to.style.opacity = String(p);
				if (effect === 'fade-zoom') to.style.transform = 'scale(' + (1.06 - 0.06 * p) + ')';
			}
		}

		function onPointerUp(e) {
			if (!down || (e && e.pointerId !== pointerId)) return;
			down = false;
			if (!dragging) return;
			dragging = false;
			viewport.classList.remove('is-dragging');
			suppressClick = true;
			setTimeout(function () { suppressClick = false; }, 60);

			var w = viewport.clientWidth || 1;
			var from = slides[current];
			var progress = Math.abs(dx) / w;
			var flick = Math.abs(velocity) > 0.35 && (velocity < 0) === (dx < 0) && Math.abs(dx) > 20;

			if (dragTo && (progress > 0.18 || flick)) {
				var duration = Math.max(Math.min(speed, 380), Math.round(speed * Math.max(0.35, 1 - progress)));
				run(from, dragTo, dragDir, duration, wrap(current + dragDir));
			} else {
				var back = Math.max(260, Math.round(speed * 0.55));
				var tr = back + 'ms ' + EASINGS.soft;
				var t = dragTo;
				if (effect === 'slide') {
					from.style.transition = 'transform ' + tr;
					from.style.transform = 'translate3d(0, 0, 0)';
					if (t) {
						t.style.transition = 'transform ' + tr;
						t.style.transform = 'translate3d(' + (dragDir * 100) + '%, 0, 0)';
					}
				} else if (t) {
					t.style.transition = 'opacity ' + tr + ', transform ' + tr;
					t.style.opacity = '0';
				}
				animating = true;
				clearTimeout(cleanupTimer);
				cleanupTimer = setTimeout(function () {
					resetSlide(from);
					if (t) resetSlide(t);
					animating = false;
					if (pending) {
						var p = pending;
						pending = null;
						goTo(p.index, p.dir);
					} else {
						scheduleAutoplay();
					}
				}, back + 50);
			}
			dragTo = null;
			dragDir = 0;
		}

		if (allowDrag) {
			viewport.addEventListener('pointerdown', function (e) {
				if (animating) return;
				if (e.pointerType === 'mouse' && e.button !== 0) return;
				down = true;
				dragging = false;
				pointerId = e.pointerId;
				startX = lastX = e.clientX;
				startY = e.clientY;
				lastT = performance.now();
				dx = 0;
				velocity = 0;
				dragTo = null;
				dragDir = 0;
			});
			viewport.addEventListener('pointermove', onPointerMove);
			viewport.addEventListener('pointerup', onPointerUp);
			viewport.addEventListener('pointercancel', onPointerUp);
			viewport.addEventListener('lostpointercapture', function (e) { if (dragging) onPointerUp(e); });
		}
		viewport.addEventListener('dragstart', function (e) { e.preventDefault(); });
		viewport.addEventListener('click', function (e) {
			if (suppressClick) { e.preventDefault(); e.stopPropagation(); }
		}, true);

		/* ---------- Start ---------- */
		if (wordsMode) {
			Array.prototype.forEach.call(root.querySelectorAll('.ps-group .ps-t'), splitWords);
		}
		if (intro) {
			/* textul primului slide apare cuvânt cu cuvânt la încărcare */
			root.classList.add('ps-pre', 'ps-ready');
			var started = false;
			var play = function () {
				if (started) return;
				started = true;
				requestAnimationFrame(function () {
					requestAnimationFrame(function () {
						root.classList.remove('ps-pre');
					});
				});
			};
			if (document.fonts && document.fonts.ready) {
				document.fonts.ready.then(play);
				setTimeout(play, 700);
			} else {
				play();
			}
		} else {
			root.classList.add('ps-ready');
		}
		updateUI(0);
		requestAnimationFrame(function () { startKB(slides[0]); });
		scheduleAutoplay();

		/* acces din consolă / teste */
		root.__ps = { next: next, prev: prev, goTo: goTo, get index() { return current; } };
	}

	function initAll(context) {
		var scope = context || document;
		if (scope.classList && scope.classList.contains('ps-hero')) initHero(scope);
		Array.prototype.forEach.call(scope.querySelectorAll('.ps-hero'), initHero);
	}

	if ($) {
		$(window).on('elementor/frontend/init', function () {
			if (window.elementorFrontend && window.elementorFrontend.hooks) {
				window.elementorFrontend.hooks.addAction('frontend/element_ready/pavva_hero_slider.default', function ($scope) {
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
