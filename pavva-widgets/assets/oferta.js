/*
 * Pavva Widgets – formularele de pe site
 * Creat de imagient pentru Pavva.
 *
 * Funcționează în orice formular (Elementor, CF7, WPForms…), pe orice pagină.
 * Câmpurile sunt recunoscute după ID, nume, etichetă sau prima opțiune:
 *   „Categorie produs”  → categoriile WooCommerce
 *   „Produs solicitat”  → produsele (doar din categoria aleasă)
 *   „Județ”             → județele României
 *   „Localitate”        → localitățile (doar din județul ales; alegi întâi
 *                         localitatea → județul se completează singur)
 *   „Suprafață proiect” → dacă lista are opțiunile tale, rămâne listă;
 *                         altfel câmp numeric în m² (clientul scrie suprafața)
 * Prima opțiune scrisă în listă rămâne textul de început.
 * Din linkul „Solicită ofertă” (?produs=…&categorie=…) alege singur
 * categoria și produsul. Pe paginile fără aceste câmpuri nu face nimic.
 */
(function () {
	'use strict';

	var C = window.PavvaOferta || {};
	var P = C.params || { produs: 'produs', categorie: 'categorie' };
	var q = new URLSearchParams(window.location.search);
	var want = { produs: q.get(P.produs) || '', categorie: q.get(P.categorie) || '' };
	var cache = {};

	function norm(t) {
		return String(t || '').normalize('NFD').replace(/[̀-ͯ]/g, '')
			.replace(/^[\s—–-]+/, '').toLowerCase().replace(/\s+/g, ' ').trim();
	}

	/* ce descrie câmpul: id, nume, placeholder, eticheta, prima opțiune */
	function hint(el) {
		var h = [el.name, el.id, el.getAttribute('placeholder'), el.getAttribute('aria-label')];
		if (el.id) {
			var lab = document.querySelector('label[for="' + el.id.replace(/"/g, '\\"') + '"]');
			if (lab) h.push(lab.textContent);
		}
		var grp = el.closest('.elementor-field-group, .wpforms-field, .wpcf7-form-control-wrap, .ff-el-group, .gfield');
		if (grp && grp.querySelectorAll('select, input:not([type="hidden"]), textarea').length === 1) {
			var l = grp.querySelector('label');
			if (l) h.push(l.textContent);
		}
		if (el.tagName === 'SELECT' && el.options.length) h.push(el.options[0].textContent);
		return norm(h.join(' '));
	}

	function kind(el) {
		if (el.tagName === 'TEXTAREA') return '';
		if (/^(hidden|submit|button|checkbox|radio|email|tel|file|date)$/.test(el.type)) return '';
		var h = hint(el);
		if (/categor/.test(h)) return 'categorie';
		if (/produs|product/.test(h)) return 'produs';
		if (/judet|county/.test(h)) return 'judet';
		if (/localitat|oras\b|comuna|city/.test(h)) return 'localitate';
		if (/suprafat|\bm2\b|\bmp\b/.test(h)) return 'suprafata';
		return '';
	}

	function fire(el) {
		['input', 'change'].forEach(function (t) { el.dispatchEvent(new Event(t, { bubbles: true })); });
	}

	function getJSON(url) {
		if (!url) return Promise.resolve(null);
		if (!cache[url]) {
			cache[url] = fetch(url, { credentials: 'same-origin' })
				.then(function (r) { return r.ok ? r.json() : null; })
				.catch(function () { return null; });
		}
		return cache[url];
	}

	/* ---------- liste ---------- */
	function isSel(el) { return !!el && el.tagName === 'SELECT'; }

	function rebuild(sel, fallback, items) {
		if (sel.__pvoPh === undefined) {
			var first = sel.options[0];
			var ph = document.createElement('option');
			ph.value = '';
			ph.textContent = first ? first.textContent : fallback;
			sel.__pvoPh = ph;
		}
		var keep = sel.value;
		var frag = document.createDocumentFragment();
		frag.appendChild(sel.__pvoPh.cloneNode(true));
		items.forEach(function (it) {
			var o = document.createElement('option');
			o.value = it[1] !== undefined ? it[1] : it[0];
			o.textContent = it[0];
			if (it[2]) o.setAttribute('data-judet', it[2]);
			frag.appendChild(o);
		});
		sel.innerHTML = '';
		sel.appendChild(frag);
		sel.value = keep;
		if (sel.value !== keep) sel.value = '';
	}

	/* alege valoarea potrivită (fără diacritice / majuscule); true dacă a reușit */
	function choose(el, value) {
		var v = norm(value);
		if (!v) return false;
		if (!isSel(el)) {
			if (!el.value) { el.value = value; fire(el); }
			return true;
		}
		var best = null, score = 0;
		Array.prototype.forEach.call(el.options, function (o) {
			if (!o.value) return;
			var t = norm(o.textContent), ov = norm(o.value), s = 0;
			if (t === v || ov === v) s = 3;
			else if (t.indexOf(v) === 0 || v.indexOf(t) === 0) s = 2;
			else if (t.indexOf(v) !== -1) s = 1;
			if (s > score) { best = o; score = s; }
		});
		if (!best) return false;
		el.value = best.value;
		fire(el);
		return true;
	}

	/* ---------- categorie / produs ---------- */
	function setupProducts(f, D) {
		var cat = isSel(f.categorie) ? f.categorie : null;
		var prod = isSel(f.produs) ? f.produs : null;

		function fillProds() {
			var c = cat ? norm(cat.value) : '';
			rebuild(prod, 'Produs solicitat', D.prods.filter(function (p) {
				return !c || (p.cats || []).some(function (n) { return norm(n) === c; });
			}).map(function (p) { return [p.name]; }));
		}

		if (cat) {
			rebuild(cat, 'Categorie produs', D.cats.map(function (c) {
				return [new Array((c.depth || 0) + 1).join('— ') + c.name, c.name];
			}));
		}
		if (prod) fillProds();
		if (cat && prod) cat.addEventListener('change', fillProds);

		if (want.categorie && f.categorie) {
			choose(f.categorie, want.categorie);
			if (cat && prod) fillProds();
		}
		if (want.produs && f.produs && !choose(f.produs, want.produs) && prod && cat && cat.value) {
			/* produsul nu e în categoria trimisă: îl căutăm în toate */
			cat.value = '';
			fillProds();
			choose(prod, want.produs);
		}
	}

	/* ---------- județ / localitate ---------- */
	function setupPlaces(f, L) {
		var jud = isSel(f.judet) ? f.judet : null;
		var loc = isSel(f.localitate) ? f.localitate : null;
		var judete = Object.keys(L);

		function fillLoc() {
			var j = jud ? jud.value : '';
			var items = [];
			if (j && L[j]) {
				L[j].forEach(function (n) { items.push([n, n, j]); });
			} else {
				/* fără județ: toate localitățile, cu județul în paranteză */
				judete.forEach(function (jj) {
					L[jj].forEach(function (n) { items.push([n + ' (' + jj + ')', n, jj]); });
				});
				items.sort(function (a, b) { return a[0].localeCompare(b[0], 'ro'); });
			}
			rebuild(loc, 'Localitate', items);
		}

		if (jud) rebuild(jud, 'Județ', judete.map(function (j) { return [j]; }));
		if (loc) fillLoc();

		if (jud && loc) {
			jud.addEventListener('change', fillLoc);
			/* alegi întâi localitatea: județul se completează singur */
			loc.addEventListener('change', function () {
				var o = loc.options[loc.selectedIndex];
				var j = o ? o.getAttribute('data-judet') : '';
				if (j && jud.value !== j) {
					var v = loc.value;
					jud.value = j;
					fillLoc();
					loc.value = v;
				}
			});
		}
	}

	/* ---------- suprafață în m² ---------- */
	function setupArea(el) {
		if (el.__pvoArea) return;
		el.__pvoArea = true;
		/* listă cu opțiunile tale (ex.: „sub 50 m²”, „50–100 m²”): rămâne listă */
		if (isSel(el) && Array.prototype.some.call(el.options, function (o, i) { return i > 0 && o.textContent.trim() !== ''; })) return;
		var label = isSel(el) && el.options.length ? el.options[0].textContent : (el.getAttribute('placeholder') || 'Suprafață proiect');
		label = label.replace(/\s*\(?m(²|2|p)\)?\s*$/i, '');
		var input = el;
		if (isSel(el)) {
			input = document.createElement('input');
			['name', 'id', 'className', 'required'].forEach(function (k) { if (el[k]) input[k] = el[k]; });
			['aria-required', 'aria-label', 'data-field'].forEach(function (a) { if (el.hasAttribute(a)) input.setAttribute(a, el.getAttribute(a)); });
			input.classList.remove('elementor-field-textual');
			input.classList.add('elementor-field-textual', 'pvo-area');
			var wrap = el.closest('.elementor-select-wrapper');
			(wrap || el).replaceWith(input);
		}
		input.type = 'number';
		input.min = '1';
		input.step = 'any';
		input.inputMode = 'decimal';
		input.placeholder = label + ' (m²)';
	}

	/* ---------- pornire ---------- */
	function scan() {
		var out = [];
		Array.prototype.forEach.call(document.querySelectorAll('form'), function (form) {
			if (form.__pvoDone) return;
			var f = { form: form };
			Array.prototype.forEach.call(form.querySelectorAll('select, input, textarea'), function (el) {
				var k = kind(el);
				if (k && !f[k]) f[k] = el;
			});
			if (f.categorie || f.produs || f.judet || f.localitate || f.suprafata) {
				form.__pvoDone = true;
				out.push(f);
			}
		});
		return out;
	}

	function start() {
		scan().forEach(function (f) {
			if (f.suprafata) setupArea(f.suprafata);

			if (f.categorie || f.produs) {
				if (isSel(f.categorie) || isSel(f.produs)) {
					getJSON(C.rest).then(function (D) {
						setupProducts(f, D && D.cats ? D : { cats: [], prods: [] });
					});
				} else {
					setupProducts(f, { cats: [], prods: [] });
				}
			}

			if (isSel(f.judet) || isSel(f.localitate)) {
				getJSON(C.places).then(function (L) { if (L) setupPlaces(f, L); });
			}
		});
	}

	if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', start);
	else start();

	/* formulare încărcate mai târziu (ex.: popup Elementor) */
	if (window.jQuery) window.jQuery(document).on('elementor/popup/show', function () { setTimeout(start, 50); });
})();
