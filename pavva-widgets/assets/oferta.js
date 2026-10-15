/*
 * Pavva Widgets – formularele de pe site
 * Creat de Imagient (imagient.ro) pentru Pavva.
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

	function esc(t) {
		return String(t).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/"/g, '&quot;');
	}

	/* opțiunile ca un singur bloc HTML (mult mai rapid decât una câte una la mii de localități) */
	function optionsHTML(items) {
		var out = new Array(items.length);
		for (var i = 0; i < items.length; i++) {
			var it = items[i];
			out[i] = '<option value="' + esc(it[1] !== undefined ? it[1] : it[0]) + '"' + (it[2] ? ' data-judet="' + esc(it[2]) + '"' : '') + '>' + esc(it[0]) + '</option>';
		}
		return out.join('');
	}

	function rebuild(sel, fallback, items, html) {
		if (sel.__pvoPh === undefined) {
			var first = sel.options[0];
			sel.__pvoPh = first ? first.textContent : fallback;
		}
		var keep = sel.value;
		sel.innerHTML = '<option value="">' + esc(sel.__pvoPh) + '</option>' + (html !== undefined ? html : optionsHTML(items));
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
		/* lista de ofertă (oferta-lista.js) preia câmpul de produs: mai multe produse într-o cerere */
		var detail = { categorie: cat, produs: prod, data: D };
		f.form.__pvoProducts = detail;
		if (prod) f.form.dispatchEvent(new CustomEvent('pavva:oferta-form', { bubbles: true, detail: detail }));

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
		var allHTML = null;

		/* fără județ: toate localitățile (peste 13.000), cu județul în paranteză; se calculează o singură dată */
		function allLoc() {
			if (allHTML === null) {
				var items = [];
				judete.forEach(function (jj) {
					L[jj].forEach(function (n) { items.push([n + ' (' + jj + ')', n, jj]); });
				});
				var col = window.Intl && Intl.Collator ? new Intl.Collator('ro') : null;
				items.sort(col ? function (a, b) { return col.compare(a[0], b[0]); } : function (a, b) { return a[0].localeCompare(b[0], 'ro'); });
				allHTML = optionsHTML(items);
			}
			return allHTML;
		}

		/* lista mare se pune abia când cineva deschide câmpul (nu mai blochează pagina la încărcare) */
		function fillLoc(full) {
			var j = jud ? jud.value : '';
			if (j && L[j]) {
				rebuild(loc, 'Localitate', L[j].map(function (n) { return [n, n, j]; }));
				loc.__pvoFull = false;
			} else if (full) {
				if (loc.__pvoFull) return;
				rebuild(loc, 'Localitate', null, allLoc());
				loc.__pvoFull = true;
			} else {
				rebuild(loc, 'Localitate', []);
				loc.__pvoFull = false;
			}
		}

		if (jud) rebuild(jud, 'Județ', judete.map(function (j) { return [j]; }));
		if (loc) {
			fillLoc(false);
			var openFull = function () { if (!(jud && jud.value)) fillLoc(true); };
			['pointerdown', 'focus', 'keydown'].forEach(function (t) { loc.addEventListener(t, openFull); });
			/* lista se pregătește în fundal, când browserul n-are altceva de făcut, ca deschiderea să fie instantă */
			if (window.requestIdleCallback) window.requestIdleCallback(allLoc, { timeout: 6000 }); else setTimeout(allLoc, 3000);
		}

		if (jud && loc) {
			jud.addEventListener('change', function () { fillLoc(false); });
			/* alegi întâi localitatea: județul se completează singur */
			loc.addEventListener('change', function () {
				var o = loc.options[loc.selectedIndex];
				var j = o ? o.getAttribute('data-judet') : '';
				if (j && jud.value !== j) {
					var v = loc.value;
					jud.value = j;
					fire(jud);
					fillLoc(false);
					loc.value = v;
					fire(loc);
				}
			});
		}
	}

	/* ---------- suprafață în m²: clientul scrie suprafața (doar cifre) ----------
	   Dacă e o listă (ex. „Sub 100 mp”), lista rămâne în formular, ascunsă, și primește valoarea scrisă
	   („120 m²”) la fiecare tastă: formularul Elementor trimite câmpul exact ca înainte. */
	function setupArea(el) {
		if (el.__pvoArea) return;
		el.__pvoArea = true;
		var form = el.form || el.closest('form');
		var label = isSel(el) && el.options.length ? el.options[0].textContent : (el.getAttribute('placeholder') || 'Suprafață proiect');
		label = label.replace(/\s*\(?m(²|2|p)\)?\s*$/i, '').trim() || 'Suprafață proiect';
		var input = el;
		if (isSel(el)) {
			input = document.createElement('input');
			input.type = 'text';
			/* aspectul câmpurilor text din același formular (ex. Nume) */
			var twin = form && form.querySelector('input[type="text"], input[type="email"], input[type="tel"]');
			input.className = (twin ? twin.className + ' ' : '') + 'pvo-area';
			input.required = el.required;
			if (el.required) input.setAttribute('aria-required', 'true');
			input.setAttribute('aria-label', el.getAttribute('aria-label') || label);
			input.id = (el.id ? el.id.replace(/[^\w-]/g, '') : 'pvo') + '-m2';
			el.required = false;
			el.removeAttribute('aria-required');
			el.tabIndex = -1;
			var wrap = el;
			while (wrap.parentElement && wrap.parentElement !== form && wrap.parentElement.querySelectorAll('input:not([type="hidden"]), select, textarea').length === 1) wrap = wrap.parentElement;
			wrap.parentNode.insertBefore(input, wrap);
			wrap.classList.add('pvl-field-hidden');
			wrap.style.display = 'none';
			var opt = null;
			var push = function () {
				var n = input.value;
				if (!opt) { opt = document.createElement('option'); opt.setAttribute('data-pvo-area', ''); el.appendChild(opt); }
				opt.value = n ? n + ' m²' : '';
				opt.textContent = opt.value || label;
				el.value = opt.value;
				fire(el);
			};
			input.addEventListener('input', push);
			input.__pvoPush = push;
		} else {
			input.type = 'text';
		}
		input.inputMode = 'numeric';
		input.autocomplete = 'off';
		input.maxLength = 7;
		input.placeholder = label + ' (m²)';
		input.title = 'Doar cifre (m²)';
		/* doar cifre: orice altceva (litere, punct, minus, spații) dispare imediat, inclusiv la lipire */
		input.addEventListener('input', function () {
			var v = input.value.replace(/\D+/g, '').replace(/^0+/, '');
			if (v !== input.value) {
				input.value = v;
				if (input.__pvoPush) input.__pvoPush();
			}
		}, true);
		input.addEventListener('keydown', function (e) {
			if (e.ctrlKey || e.metaKey || e.altKey || e.key.length > 1) return;
			if (!/\d/.test(e.key)) e.preventDefault();
		});
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

	/* câmpurile select fără etichetă (formularele cu doar „placeholder”) primesc nume din prima opțiune,
	   ca să le poată folosi cititoarele de ecran și agenții AI */
	function labelSelects() {
		Array.prototype.forEach.call(document.querySelectorAll('form select'), function (el) {
			if (el.getAttribute('aria-label') || el.getAttribute('aria-labelledby') || (el.labels && el.labels.length)) return;
			var t = el.options.length ? (el.options[0].textContent || '').trim() : '';
			if (!t) t = (el.name || '').replace(/[_-]+/g, ' ').trim();
			if (t) el.setAttribute('aria-label', t);
		});
	}

	/* listele (Județ, Localitate...) fără valoare aleasă au aceeași culoare ca placeholder-ul câmpurilor text
	   („Nume”, „Email”); după alegere, aceeași culoare ca textul scris. Culorile se iau din câmpurile formularului. */
	function toneSelects() {
		if (!document.getElementById('pvo-tone-css')) {
			var st = document.createElement('style');
			st.id = 'pvo-tone-css';
			st.textContent = 'form select.pvo-empty{color:var(--pvo-ph)!important}form select.pvo-empty option{color:var(--pvo-tx)}' +
				'form select.pvo-filled{color:var(--pvo-tx)!important}';
			document.head.appendChild(st);
		}
		Array.prototype.forEach.call(document.querySelectorAll('form'), function (form) {
			var sels = form.querySelectorAll('select');
			if (!sels.length) return;
			var inp = form.querySelector('input[type="text"], input[type="email"], input[type="tel"], input:not([type]), textarea');
			if (!inp) return;
			var ph = '', tx = '';
			try { ph = getComputedStyle(inp, '::placeholder').color; } catch (e) {}
			tx = getComputedStyle(inp).color;
			if (ph) form.style.setProperty('--pvo-ph', ph);
			if (tx) form.style.setProperty('--pvo-tx', tx);
			var upd = function (el) {
				var empty = !el.value || (el.selectedIndex >= 0 && el.options[el.selectedIndex] && el.options[el.selectedIndex].disabled);
				el.classList.toggle('pvo-empty', !!empty);
				el.classList.toggle('pvo-filled', !empty);
			};
			Array.prototype.forEach.call(sels, function (el) {
				upd(el);
				if (!el.__pvoTone) { el.__pvoTone = true; el.addEventListener('change', function () { upd(el); }); }
			});
		});
	}

	function start() {
		labelSelects();
		toneSelects();
		/* listele se mai completează după pornire (localități, produse): recalculăm */
		setTimeout(toneSelects, 800);
		setTimeout(toneSelects, 2500);
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
