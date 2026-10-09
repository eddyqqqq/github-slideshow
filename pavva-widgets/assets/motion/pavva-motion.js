/*!
 * Pavva Motion – animații la scroll (GSAP + ScrollTrigger + SplitText, Lenis opțional)
 * Creat de imagient pentru Pavva.
 *
 * Pe element: clasa „pv-lines” (sau oricare tip de mai jos) ori atributul data-pv="lines".
 * Opțiuni (atribute): data-pv-delay, data-pv-duration, data-pv-stagger, data-pv-start,
 * data-pv-from (bottom/top/left/right), data-pv-speed, data-pv-mobile="off",
 * data-pv-to / data-pv-suffix / data-pv-prefix / data-pv-decimals (cifre),
 * data-pv-color (cortină, pavaj, subliniere; implicit culoarea Pavva).
 * Opțiuni ca clase (pentru Elementor V4): pv-delay-3 (= 0.3 s), pv-mobile-off.
 * La „pv-stagger” se animă pe rând: elementele cu data-pv-item, cardurile widgeturilor
 * Pavva sau, altfel, copiii direcți.
 */
(function (w, d) {
	'use strict';

	var TYPES = ['words-mask', 'lines', 'words', 'blur', 'chars', 'underline', 'scrub', 'count', 'fade-up', 'stagger', 'flip3d',
		'img-reveal', 'curtain', 'tiles', 'parallax', 'skew', 'draw', 'pin-zoom', 'hscroll', 'marquee', 'magnetic'];
	/* efectele grele: pe telefon, în modul „simplificat”, nu pornesc */
	var HEAVY = { 'pin-zoom': 1, hscroll: 1, parallax: 1, magnetic: 1, skew: 1 };
	/* culoarea Pavva (cortină, pavaj, subliniere), dacă elementul nu are alta */
	var BRAND = '#AB5525';
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
	/* curba cubic-bezier exactă (aceeași ca în CSS), ca ease pentru GSAP */
	function bezier(x1, y1, x2, y2) {
		var cx = 3 * x1, bx = 3 * (x2 - x1) - cx, ax = 1 - cx - bx;
		var cy = 3 * y1, by = 3 * (y2 - y1) - cy, ay = 1 - cy - by;
		function sx(u) { return ((ax * u + bx) * u + cx) * u; }
		function sy(u) { return ((ay * u + by) * u + cy) * u; }
		function dx(u) { return (3 * ax * u + 2 * bx) * u + cx; }
		return function (t) {
			if (t <= 0) return 0;
			if (t >= 1) return 1;
			var u = t, i, e, dd;
			for (i = 0; i < 8; i++) {
				e = sx(u) - t;
				if (Math.abs(e) < 1e-6) return sy(u);
				dd = dx(u);
				if (Math.abs(dd) < 1e-6) break;
				u -= e / dd;
			}
			var lo = 0, hi = 1;
			u = t;
			for (i = 0; i < 30 && hi - lo > 1e-7; i++) {
				if (sx(u) < t) lo = u; else hi = u;
				u = (lo + hi) / 2;
			}
			return sy(u);
		};
	}
	/* slider-ul din header: cuvintele urcă în 1100 ms, la 55 ms unul după altul, pe curba (.16, 1, .3, 1) */
	var HERO = { duration: 1.1, step: 0.055, ease: bezier(0.16, 1, 0.3, 1) };
	function color(el) { return str(el, 'color', '') || BRAND; }
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

	/* cardurile animate pe rând: ale widgeturilor Pavva sau copiii direcți */
	function staggerItems(el) {
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
		return items;
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
		/* identic cu textul din slider-ul din header (cuvânt cu cuvânt, urcă de sub o linie invizibilă) */
		'words-mask': function (el) {
			splitAnim(el, 'words', 'words', function (s) {
				/* textele lungi merg mai repede, ca descrierea din slider */
				var step = num(el, 'stagger', s.words.length > 14 ? HERO.step * 0.35 : HERO.step);
				return w.gsap.from(s.words, {
					yPercent: 100,
					y: function (i, t) { return (parseFloat(w.getComputedStyle(t).fontSize) || 16) * 0.32; },
					force3D: true, ease: HERO.ease, duration: num(el, 'duration', HERO.duration), delay: num(el, 'delay', 0), stagger: step,
					scrollTrigger: once(el)
				});
			});
		},
		/* rândurile urcă din mască, apoi o linie în culoarea Pavva se desenează sub fiecare */
		underline: function (el) {
			splitAnim(el, 'lines', 'lines', function (s) {
				var b = base(el, 1.1, 0.12);
				var t = textTarget(el);
				var align = w.getComputedStyle(t).textAlign;
				s.lines.forEach(function (l) {
					l.style.width = 'fit-content';
					if (align === 'center') { l.style.marginLeft = 'auto'; l.style.marginRight = 'auto'; } else if (align === 'right' || align === 'end') l.style.marginLeft = 'auto';
					l.style.paddingBottom = '.14em';
					l.style.backgroundImage = 'linear-gradient(' + color(el) + ',' + color(el) + ')';
					l.style.backgroundRepeat = 'no-repeat';
					l.style.backgroundPosition = '0 100%';
				});
				var tl = w.gsap.timeline({ delay: b.delay, scrollTrigger: once(el) });
				tl.from(s.lines, { yPercent: 110, ease: HERO.ease, duration: b.duration, stagger: b.stagger });
				tl.fromTo(s.lines, { backgroundSize: '0% .07em' }, { backgroundSize: '100% .07em', ease: 'expo.inOut', duration: b.duration * 0.9, stagger: b.stagger }, b.duration * 0.45);
				return tl;
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
			var items = staggerItems(el);
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
		/* cardurile se ridică pe rând și se rotesc în 3D, ca niște plăci puse pe teren */
		flip3d: function (el) {
			var items = staggerItems(el);
			show(el);
			w.gsap.set(el.children, { visibility: 'visible' });
			if (!items.length) return;
			var b = base(el, 1.2, 0.11);
			w.gsap.set(items, { visibility: 'visible', opacity: 0, y: 70 * k(), rotateX: -38, transformPerspective: 1100, transformOrigin: '50% 100%' });
			w.ScrollTrigger.batch(items, {
				start: str(el, 'start', cfg.start), once: true,
				onEnter: function (batch) {
					w.gsap.to(batch, { opacity: 1, y: 0, rotateX: 0, ease: 'expo.out', duration: b.duration, delay: b.delay, stagger: b.stagger, overwrite: true, clearProps: 'transform' });
				}
			});
		},
		'img-reveal': function (el) {
			show(el);
			var from = str(el, 'from', 'bottom');
			var clip = { bottom: 'inset(100% 0% 0% 0%)', top: 'inset(0% 0% 100% 0%)', left: 'inset(0% 100% 0% 0%)', right: 'inset(0% 0% 0% 100%)', center: 'inset(0% 50% 0% 50%)' }[from] || 'inset(100% 0% 0% 0%)';
			var inner = el.querySelector('img, video, .pv-inner');
			var b = base(el, 1.5, 0);
			var tl = w.gsap.timeline({ delay: b.delay, scrollTrigger: once(el) });
			tl.fromTo(el, { clipPath: clip }, { clipPath: 'inset(0% 0% 0% 0%)', duration: b.duration, ease: 'expo.inOut', clearProps: 'clipPath' });
			if (inner) tl.fromTo(inner, { scale: 1 + 0.18 * k() }, { scale: 1, duration: b.duration + 0.5, ease: 'expo.out', clearProps: 'transform' }, 0);
		},
		/* o cortină în culoarea Pavva trece peste imagine și o lasă descoperită */
		curtain: function (el) {
			show(el);
			var from = str(el, 'from', 'left');
			var vertical = from === 'top' || from === 'bottom';
			var prop = vertical ? 'scaleY' : 'scaleX';
			var o1 = { left: '0% 50%', right: '100% 50%', top: '50% 0%', bottom: '50% 100%' }[from] || '0% 50%';
			var o2 = { left: '100% 50%', right: '0% 50%', top: '50% 100%', bottom: '50% 0%' }[from] || '100% 50%';
			var kids = Array.prototype.slice.call(el.children);
			var cover = d.createElement('span');
			cover.className = 'pv-cover';
			cover.style.background = color(el);
			if (w.getComputedStyle(el).position === 'static') el.style.position = 'relative';
			el.appendChild(cover);
			var inner = el.querySelector('img, video, .pv-inner');
			var b = base(el, 1.4, 0);
			var a = {}, z = {};
			a[prop] = 0; z[prop] = 1;
			w.gsap.set(kids, { opacity: 0 });
			var tl = w.gsap.timeline({ delay: b.delay, scrollTrigger: once(el) });
			tl.fromTo(cover, Object.assign({ transformOrigin: o1 }, a), Object.assign({ duration: b.duration * 0.45, ease: 'expo.inOut' }, z));
			tl.set(kids, { opacity: 1 });
			tl.set(cover, { transformOrigin: o2 });
			var out = {}; out[prop] = 0;
			tl.to(cover, Object.assign({ duration: b.duration * 0.55, ease: 'expo.inOut' }, out));
			if (inner) tl.fromTo(inner, { scale: 1 + 0.14 * k() }, { scale: 1, duration: b.duration, ease: 'expo.out', clearProps: 'transform' }, '<');
			cleanups.push(function () { cover.remove(); el.style.position = ''; });
		},
		/* „pavaj”: imaginea e acoperită de plăci care se strâng una câte una */
		tiles: function (el) {
			show(el);
			var r = el.getBoundingClientRect();
			var cols = isMobile() ? 4 : 7;
			var rows = Math.max(2, Math.min(8, Math.round(cols * (r.height || 1) / (r.width || 1))));
			var grid = d.createElement('span');
			grid.className = 'pv-tiles-grid';
			grid.style.gridTemplateColumns = 'repeat(' + cols + ', 1fr)';
			grid.style.gridTemplateRows = 'repeat(' + rows + ', 1fr)';
			for (var i = 0; i < cols * rows; i++) {
				var c = d.createElement('span');
				c.style.background = color(el);
				grid.appendChild(c);
			}
			if (w.getComputedStyle(el).position === 'static') el.style.position = 'relative';
			el.appendChild(grid);
			var inner = el.querySelector('img, video, .pv-inner');
			var f = str(el, 'from', 'random');
			var order = { top: 'start', left: 'start', bottom: 'end', right: 'end', center: 'center', random: 'random' }[f] || 'random';
			var b = base(el, 0.9, 0);
			var tl = w.gsap.timeline({ delay: b.delay, scrollTrigger: once(el) });
			tl.to(grid.children, { scale: 0, ease: 'power3.inOut', duration: b.duration, stagger: { grid: [rows, cols], from: order, amount: num(el, 'stagger', 0.7) } });
			if (inner) tl.fromTo(inner, { scale: 1 + 0.12 * k() }, { scale: 1, duration: b.duration + 0.9, ease: 'expo.out', clearProps: 'transform' }, 0);
			tl.add(function () { grid.style.display = 'none'; });
			cleanups.push(function () { grid.remove(); el.style.position = ''; });
		},
		/* la scroll rapid imaginile se înclină puțin și revin elastic */
		skew: function (el) {
			var max = num(el, 'speed', 6) * k();
			var to = w.gsap.quickTo(el, 'skewY', { duration: 0.6, ease: 'power3.out' });
			w.ScrollTrigger.create({
				trigger: el, start: 'top bottom', end: 'bottom top',
				onUpdate: function (self) { to(w.gsap.utils.clamp(-max, max, self.getVelocity() / -250)); },
				onLeave: function () { to(0); }, onLeaveBack: function () { to(0); }
			});
			cleanups.push(function () { w.gsap.set(el, { clearProps: 'transform' }); });
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
				scrollTrigger: { trigger: el, start: 'top top', end: '+=' + num(el, 'length', 120) + '%', pin: true, pinSpacing: true, scrub: 1, anticipatePin: 1 }
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
				scrollTrigger: { trigger: el, start: 'top top', end: function () { return '+=' + dist(); }, pin: true, pinSpacing: true, scrub: 1, invalidateOnRefresh: true, anticipatePin: 1 }
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
				['delay', 'duration', 'stagger', 'from', 'mobile', 'color'].forEach(function (key) { if (o[key] !== undefined && o[key] !== '') el.setAttribute('data-pv-' + key, o[key]); });
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
