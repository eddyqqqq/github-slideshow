/*!
 * Pavva Motion – animații la scroll (GSAP + ScrollTrigger + SplitText, Lenis opțional)
 * Creat de imagient pentru Pavva.
 *
 * Pe element: clasa „pv-lines” (sau oricare tip de mai jos) ori atributul data-pv="lines".
 * Opțiuni (atribute): data-pv-delay, data-pv-duration, data-pv-stagger, data-pv-start,
 * data-pv-from (bottom/top/left/right), data-pv-speed, data-pv-mobile="off",
 * data-pv-to / data-pv-suffix / data-pv-prefix / data-pv-decimals (cifre).
 * Opțiuni ca clase (pentru Elementor V4): pv-delay-3 (= 0.3 s), pv-mobile-off.
 * La „pv-stagger” se animă pe rând: elementele cu data-pv-item, cardurile widgeturilor
 * Pavva sau, altfel, copiii direcți.
 */
(function (w, d) {
	'use strict';

	var TYPES = ['lines', 'words', 'blur', 'chars', 'scrub', 'count', 'fade-up', 'stagger',
		'img-reveal', 'parallax', 'draw', 'pin-zoom', 'hscroll', 'marquee', 'magnetic'];
	/* efectele grele: pe telefon, în modul „simplificat”, nu pornesc */
	var HEAVY = { 'pin-zoom': 1, hscroll: 1, parallax: 1, magnetic: 1 };
	/* elementele care se repetă în widgeturile Pavva (pentru „cascadă”) */
	var ITEMS = '[data-pv-item], .pvg-item, .cc-card, .pvc-stage, .pvc-thumb, .pvd-item, .pvk-item, .pva-item, .pf-item, .pf-media';

	var cfg = { intensity: 'soft', smooth: false, start: 'top 88%', mobile: 'simple', bp: 767 };
	var ctx = null, lenis = null, cleanups = [], tickerFn = null, mq = null, started = false;

	function sel(type) { return '.pv-' + type + ', [data-pv="' + type + '"]'; }
	function all(root, type) { return Array.prototype.slice.call(root.querySelectorAll(sel(type))); }
	function attr(el, name) {
		var v = el.getAttribute('data-pv-' + name);
		if (v !== null && v !== '') return v;
		/* opțiuni scrise ca clase: pv-delay-3 → 0.3 */
		if (name === 'delay' || name === 'duration' || name === 'stagger') {
			var m = (' ' + el.className + ' ').match(new RegExp(' pv-' + name + '-(\\d+) '));
			if (m) return String(parseInt(m[1], 10) / 10);
		}
		return null;
	}
	function num(el, name, def) { var v = parseFloat(attr(el, name)); return isNaN(v) ? def : v; }
	function str(el, name, def) { var v = attr(el, name); return v === null ? def : v; }
	function isMobile() { return !!(w.matchMedia && w.matchMedia('(max-width: ' + cfg.bp + 'px)').matches); }
	function k() { var base = cfg.intensity === 'bold' ? 1.9 : 1; return isMobile() ? base * 0.6 : base; }
	function reduced() { return !!(w.matchMedia && w.matchMedia('(prefers-reduced-motion: reduce)').matches); }
	function isEditor() {
		var b = d.body;
		return !!(b && b.classList.contains('elementor-editor-active')) ||
			!!(w.elementorFrontend && typeof w.elementorFrontend.isEditMode === 'function' && w.elementorFrontend.isEditMode());
	}
	function show(el) { w.gsap.set(el, { visibility: 'visible' }); }
	function once(el, extra) {
		var o = { trigger: el, start: str(el, 'start', cfg.start), once: true };
		for (var key in extra || {}) o[key] = extra[key];
		return o;
	}
	function base(el, dur, stg) {
		return { duration: num(el, 'duration', dur), delay: num(el, 'delay', 0), stagger: num(el, 'stagger', stg) };
	}
	function mobileOff(el) {
		return isMobile() && (str(el, 'mobile', '') === 'off' || el.classList.contains('pv-mobile-off'));
	}

	/* ---------- text ---------- */
	/* la widgeturile clasice: coboară până la titlul / paragraful din interior */
	function textTarget(el) {
		var t = el;
		while (t.children.length === 1 && /^(DIV|SECTION|ARTICLE)$/.test(t.firstElementChild.tagName)) t = t.firstElementChild;
		if (t.children.length === 1 && /^(H[1-6]|P|SPAN|A|STRONG)$/.test(t.firstElementChild.tagName)) t = t.firstElementChild;
		return t;
	}
	function splitAnim(el, type, mask, build) {
		var S = w.SplitText;
		show(el);
		var t = textTarget(el);
		if (!S) { return w.gsap.from(t, { opacity: 0, y: 24 * k(), duration: 1, ease: 'power3.out', scrollTrigger: once(el) }); }
		S.create(t, {
			type: type, mask: mask, autoSplit: true, aria: 'auto',
			linesClass: 'pv-l', wordsClass: 'pv-w', charsClass: 'pv-c',
			onSplit: function (self) { return build(self); }
		});
	}

	var builders = {
		lines: function (el) {
			splitAnim(el, 'lines', 'lines', function (s) {
				var b = base(el, 1.25, 0.09);
				return w.gsap.from(s.lines, { yPercent: 115, rotate: cfg.intensity === 'bold' && !isMobile() ? 4 : 0, transformOrigin: '0% 100%', ease: 'expo.out', duration: b.duration, delay: b.delay, stagger: b.stagger, scrollTrigger: once(el) });
			});
		},
		words: function (el) {
			splitAnim(el, 'words', null, function (s) {
				var b = base(el, 1, 0.04);
				return w.gsap.from(s.words, { y: 26 * k(), opacity: 0, ease: 'power3.out', duration: b.duration, delay: b.delay, stagger: b.stagger, scrollTrigger: once(el) });
			});
		},
		blur: function (el) {
			splitAnim(el, 'words', null, function (s) {
				var b = base(el, 1.1, 0.05);
				return w.gsap.from(s.words, { filter: 'blur(' + (10 * k()) + 'px)', y: 10 * k(), opacity: 0, ease: 'power2.out', duration: b.duration, delay: b.delay, stagger: b.stagger, scrollTrigger: once(el) });
			});
		},
		chars: function (el) {
			splitAnim(el, 'words,chars', 'words', function (s) {
				var b = base(el, 1.1, 0.03);
				return w.gsap.from(s.chars, { yPercent: 120, rotate: 8 * (k() - 0.5), ease: 'expo.out', duration: b.duration, delay: b.delay, stagger: b.stagger, scrollTrigger: once(el) });
			});
		},
		scrub: function (el) {
			splitAnim(el, 'words', null, function (s) {
				return w.gsap.fromTo(s.words, { opacity: cfg.intensity === 'bold' ? 0.08 : 0.18 }, {
					opacity: 1, ease: 'none', stagger: 0.12,
					scrollTrigger: { trigger: el, start: str(el, 'start', 'top 80%'), end: str(el, 'end', 'bottom 45%'), scrub: true }
				});
			});
		},
		count: function (box) {
			show(box);
			var el = textTarget(box);
			var raw = (el.textContent || '0').replace(/[^\d.,-]/g, '').replace(/\./g, '').replace(',', '.');
			var to = num(box, 'to', parseFloat(raw) || 0);
			var from = num(box, 'from', 0), dec = num(box, 'decimals', 0), suffix = str(box, 'suffix', ''), prefix = str(box, 'prefix', '');
			var fmt = new Intl.NumberFormat('ro-RO', { minimumFractionDigits: dec, maximumFractionDigits: dec });
			var final = el.textContent;
			var o = { v: from };
			el.textContent = prefix + fmt.format(from) + suffix;
			w.gsap.to(o, {
				v: to, duration: num(box, 'duration', 2.2), delay: num(box, 'delay', 0), ease: 'power2.out',
				onUpdate: function () { el.textContent = prefix + fmt.format(o.v) + suffix; },
				scrollTrigger: once(box)
			});
			cleanups.push(function () { el.textContent = final; });
		},

		/* ---------- elemente ---------- */
		'fade-up': function (el) {
			show(el);
			var b = base(el, 1.1, 0);
			w.gsap.fromTo(el, { y: 42 * k(), opacity: 0 }, { y: 0, opacity: 1, ease: 'power3.out', duration: b.duration, delay: b.delay, clearProps: 'transform', scrollTrigger: once(el) });
		},
		stagger: function (el) {
			var items = Array.prototype.slice.call(el.querySelectorAll(ITEMS)).filter(function (n) {
				/* doar primul nivel de carduri (nu și elementele din interiorul lor) */
				var p = n.parentElement;
				while (p && p !== el) { if (p.matches && p.matches(ITEMS)) return false; p = p.parentElement; }
				return true;
			});
			if (!items.length) {
				var box = el;
				/* coboară prin învelișurile cu un singur copil (ex. widget Elementor) */
				while (box.children.length === 1 && box.firstElementChild.children.length) box = box.firstElementChild;
				items = Array.prototype.slice.call(box.children);
			}
			show(el);
			/* tot ce nu e card rămâne vizibil (titluri, butoane, paginare) */
			w.gsap.set(el.children, { visibility: 'visible' });
			if (!items.length) return;
			var b = base(el, 1.05, 0.1);
			w.gsap.set(items, { visibility: 'visible', opacity: 0, y: 46 * k() });
			/* grilele mari: rând cu rând, când ajung în ecran */
			w.ScrollTrigger.batch(items, {
				start: str(el, 'start', cfg.start), once: true,
				onEnter: function (batch) {
					w.gsap.to(batch, { opacity: 1, y: 0, ease: 'power3.out', duration: b.duration, delay: b.delay, stagger: b.stagger, overwrite: true, clearProps: 'transform' });
				}
			});
		},
		'img-reveal': function (el) {
			show(el);
			var from = str(el, 'from', 'bottom');
			var clip = { bottom: 'inset(100% 0% 0% 0%)', top: 'inset(0% 0% 100% 0%)', left: 'inset(0% 100% 0% 0%)', right: 'inset(0% 0% 0% 100%)' }[from] || 'inset(100% 0% 0% 0%)';
			var inner = el.querySelector('img, video, .pv-inner');
			var b = base(el, 1.5, 0);
			var tl = w.gsap.timeline({ delay: b.delay, scrollTrigger: once(el) });
			tl.fromTo(el, { clipPath: clip }, { clipPath: 'inset(0% 0% 0% 0%)', duration: b.duration, ease: 'expo.inOut', clearProps: 'clipPath' });
			if (inner) tl.fromTo(inner, { scale: 1 + 0.18 * k() }, { scale: 1, duration: b.duration + 0.5, ease: 'expo.out', clearProps: 'transform' }, 0);
		},
		parallax: function (el) {
			var inner = el.querySelector('img, video, .pv-inner') || el.firstElementChild;
			if (!inner) return;
			var amt = num(el, 'speed', 10) * k();
			el.style.overflow = 'hidden';
			w.gsap.fromTo(inner, { yPercent: -amt, scale: 1 + amt / 50 }, {
				yPercent: amt, ease: 'none',
				scrollTrigger: { trigger: el, start: 'top bottom', end: 'bottom top', scrub: true }
			});
		},
		draw: function (el) {
			show(el);
			var paths = el.querySelectorAll('path, line, polyline, polygon, circle, rect, ellipse');
			var b = base(el, 1.6, 0.12);
			if (paths.length) {
				Array.prototype.forEach.call(paths, function (p) {
					var len = p.getTotalLength ? p.getTotalLength() : 1000;
					w.gsap.set(p, { strokeDasharray: len, strokeDashoffset: len });
				});
				w.gsap.to(paths, { strokeDashoffset: 0, ease: 'power2.inOut', duration: b.duration, delay: b.delay, stagger: b.stagger, scrollTrigger: once(el) });
			} else {
				var f = str(el, 'from', 'left');
				w.gsap.fromTo(el, { scaleX: 0, transformOrigin: f === 'right' ? '100% 50%' : (f === 'center' ? '50% 50%' : '0% 50%') },
					{ scaleX: 1, ease: 'expo.inOut', duration: b.duration, delay: b.delay, scrollTrigger: once(el) });
			}
		},

		/* ---------- efecte mari ---------- */
		'pin-zoom': function (el) {
			var media = el.querySelector('.pv-pin-media') || el.querySelector('img, video') || el.firstElementChild;
			if (!media) return;
			var inner = media.tagName === 'IMG' || media.tagName === 'VIDEO' ? media : media.querySelector('img, video, .pv-inner');
			var side = cfg.intensity === 'bold' ? 26 : 16;
			var tl = w.gsap.timeline({
				scrollTrigger: { trigger: el, start: 'top top', end: '+=' + num(el, 'length', 120) + '%', pin: true, scrub: 1, anticipatePin: 1 }
			});
			tl.fromTo(media, { clipPath: 'inset(14% ' + side + '% 14% ' + side + '% round 22px)' }, { clipPath: 'inset(0% 0% 0% 0% round 0px)', ease: 'none' });
			if (inner && inner !== media) tl.fromTo(inner, { scale: 1.25 }, { scale: 1, ease: 'none' }, 0);
		},
		hscroll: function (el) {
			var track = el.querySelector('.pv-hscroll-track') || el.firstElementChild;
			if (!track) return;
			var dist = function () { return Math.max(0, track.scrollWidth - el.clientWidth); };
			w.gsap.to(track, {
				x: function () { return -dist(); }, ease: 'none',
				scrollTrigger: { trigger: el, start: 'top top', end: function () { return '+=' + dist(); }, pin: true, scrub: 1, invalidateOnRefresh: true, anticipatePin: 1 }
			});
		},
		marquee: function (el) {
			var track = el.querySelector('.pv-marquee-track') || el.firstElementChild;
			if (!track) return;
			el.style.overflow = 'hidden';
			track.style.display = 'flex';
			track.style.width = 'max-content';
			var orig = Array.prototype.slice.call(track.children);
			var guard = 0;
			while (track.scrollWidth < el.clientWidth * 2.2 && guard++ < 20) {
				orig.forEach(function (n) { var c = n.cloneNode(true); c.setAttribute('aria-hidden', 'true'); c.classList.add('pv-clone'); track.appendChild(c); });
			}
			var setW = track.scrollWidth;
			Array.prototype.slice.call(track.children).forEach(function (n) {
				var c = n.cloneNode(true); c.setAttribute('aria-hidden', 'true'); c.classList.add('pv-clone'); track.appendChild(c);
			});
			var speed = num(el, 'speed', 70);
			var dir = str(el, 'from', 'right') === 'left' ? 1 : -1;
			var tween = w.gsap.fromTo(track, { x: dir < 0 ? 0 : -setW }, { x: dir < 0 ? -setW : 0, duration: setW / speed, ease: 'none', repeat: -1 });
			w.ScrollTrigger.create({
				trigger: el, start: 'top bottom', end: 'bottom top',
				onToggle: function (self) { if (self.isActive) tween.play(); else tween.pause(); },
				onUpdate: function (self) {
					var boost = 1 + Math.min(Math.abs(self.getVelocity()) / 400, cfg.intensity === 'bold' ? 6 : 3);
					w.gsap.to(tween, { timeScale: boost, duration: 0.2, overwrite: true, onComplete: function () { w.gsap.to(tween, { timeScale: 1, duration: 1.2 }); } });
				}
			});
			cleanups.push(function () { Array.prototype.forEach.call(track.querySelectorAll('.pv-clone'), function (c) { c.remove(); }); track.style.cssText = ''; el.style.overflow = ''; });
		},
		magnetic: function (el) {
			if (!(w.matchMedia && w.matchMedia('(hover: hover) and (pointer: fine)').matches)) return;
			var strength = num(el, 'speed', 0.35) * k();
			var xTo = w.gsap.quickTo(el, 'x', { duration: 0.45, ease: 'power3.out' });
			var yTo = w.gsap.quickTo(el, 'y', { duration: 0.45, ease: 'power3.out' });
			function move(e) { var r = el.getBoundingClientRect(); xTo((e.clientX - r.left - r.width / 2) * strength); yTo((e.clientY - r.top - r.height / 2) * strength); }
			function leave() { w.gsap.to(el, { x: 0, y: 0, duration: 0.9, ease: 'elastic.out(1, 0.4)' }); }
			el.addEventListener('pointermove', move);
			el.addEventListener('pointerleave', leave);
			cleanups.push(function () { el.removeEventListener('pointermove', move); el.removeEventListener('pointerleave', leave); w.gsap.set(el, { clearProps: 'transform' }); });
		}
	};

	/* ---------- scroll lin (Lenis), doar pe calculator ---------- */
	function startSmooth() {
		if (lenis || !w.Lenis || isMobile()) return;
		lenis = new w.Lenis({ duration: 1.15, easing: function (t) { return Math.min(1, 1.001 - Math.pow(2, -10 * t)); }, smoothWheel: true, anchors: true });
		lenis.on('scroll', w.ScrollTrigger.update);
		tickerFn = function (time) { lenis.raf(time * 1000); };
		w.gsap.ticker.add(tickerFn);
		w.gsap.ticker.lagSmoothing(0);
		d.documentElement.classList.add('pv-smooth');
	}
	function stopSmooth() {
		if (!lenis) return;
		w.gsap.ticker.remove(tickerFn);
		lenis.destroy();
		lenis = null;
		d.documentElement.classList.remove('pv-smooth');
	}

	function revealAll() { d.documentElement.classList.remove('pv-js'); d.documentElement.classList.add('pv-ready'); }

	/* animațiile alese din panoul Elementor pentru elementele V4 (după data-id), dacă n-au primit deja clasa */
	function applyMap() {
		var map = w.PavvaMotionMap || {};
		Object.keys(map).forEach(function (id) {
			var o = map[id];
			Array.prototype.forEach.call(d.querySelectorAll('[data-id="' + id + '"]'), function (el) {
				if (el.hasAttribute('data-pv') || el.classList.contains('pv-' + o.type)) return;
				el.setAttribute('data-pv', o.type);
				['delay', 'duration', 'stagger', 'from', 'mobile'].forEach(function (key) { if (o[key] !== undefined && o[key] !== '') el.setAttribute('data-pv-' + key, o[key]); });
			});
		});
	}

	function run() {
		var g = w.gsap;
		var mobile = isMobile();
		if (mobile && cfg.mobile === 'off') { revealAll(); return; }
		ctx = g.context(function () {
			TYPES.forEach(function (t) {
				all(d, t).forEach(function (el) {
					if (mobileOff(el) || (mobile && cfg.mobile === 'simple' && HEAVY[t])) { g.set(el, { visibility: 'visible' }); el.classList.add('pv-off'); return; }
					try { builders[t](el); } catch (e) { g.set(el, { visibility: 'visible', opacity: 1, clearProps: 'transform' }); }
				});
			});
		});
		if (cfg.smooth) startSmooth(); else stopSmooth();
		d.documentElement.classList.add('pv-ready');
	}

	function init(options) {
		options = options || {};
		for (var key in options) cfg[key] = options[key];
		if (!w.gsap || !w.ScrollTrigger || reduced() || isEditor()) { revealAll(); return; }
		w.gsap.registerPlugin(w.ScrollTrigger);
		if (w.SplitText) w.gsap.registerPlugin(w.SplitText);
		applyMap();
		run();
		if (!started) {
			started = true;
			if (d.fonts && d.fonts.ready) d.fonts.ready.then(function () { w.ScrollTrigger.refresh(); });
			w.addEventListener('load', function () { w.ScrollTrigger.refresh(); }, { once: true });
			/* responsive: la trecerea între telefon și calculator, animațiile se refac */
			if (w.matchMedia) {
				mq = w.matchMedia('(max-width: ' + cfg.bp + 'px)');
				var onChange = function () { destroy(); run(); w.ScrollTrigger.refresh(); };
				if (mq.addEventListener) mq.addEventListener('change', onChange); else if (mq.addListener) mq.addListener(onChange);
			}
		}
	}

	function destroy() {
		cleanups.forEach(function (fn) { try { fn(); } catch (e) {} });
		cleanups = [];
		if (ctx) { ctx.revert(); ctx = null; }
		Array.prototype.forEach.call(d.querySelectorAll('.pv-off'), function (el) { el.classList.remove('pv-off'); });
		stopSmooth();
	}

	w.PavvaMotion = {
		init: init,
		destroy: destroy,
		restart: function (options) { destroy(); for (var key in options || {}) cfg[key] = options[key]; run(); w.ScrollTrigger && w.ScrollTrigger.refresh(); },
		smooth: function (on) { cfg.smooth = !!on; if (on) startSmooth(); else stopSmooth(); },
		config: cfg,
		types: TYPES
	};
})(window, document);
