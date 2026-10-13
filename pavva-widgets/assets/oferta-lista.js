/*!
 * Pavva – lista de ofertă (mai multe produse într-o singură cerere de ofertă)
 * Creat de Imagient (imagient.ro) pentru Pavva.
 *
 * Lista se ține în browserul vizitatorului (localStorage, fără cookie-uri), deci merge și cu paginile din cache.
 *
 * Ce recunoaște în pagină:
 *   [data-pvl-add]            butonul „Adaugă la ofertă” (widgetul Buton ofertă, cardurile din Grilă produse)
 *   .pv-oferta-add            orice element Elementor cu această clasă CSS: adaugă produsul paginii
 *   .pv-oferta-go             la fel, apoi deschide formularul
 *   [data-pvl-counter]        widgetul „Oferta ta” (iconiță + număr)
 *   [data-pvl-count], .pv-oferta-count   orice element: primește numărul de produse
 *   [data-pvl-list]           widgetul „Listă ofertă” (produsele alese, cu ștergere)
 *   <html class="pv-oferta-are">         când lista are produse (pentru CSS propriu)
 * În formular, câmpul „Produs solicitat” devine „adaugă în listă”, iar la trimitere primește toate produsele.
 *
 * window.PavvaLista: get(), add(item), remove(id), has(id), clear(), count()
 */
(function (w, d) {
	'use strict';

	var C = w.PavvaListaCfg || {};
	var KEY = 'pavva_oferta_v1';
	var MAX = 20;
	var memory = [];
	var dataCache = null;

	/* ---------- lista (localStorage, cu rezervă în memorie) ---------- */
	function read() {
		try {
			var v = JSON.parse(w.localStorage.getItem(KEY) || '[]');
			return Array.isArray(v) ? v.filter(function (it) { return it && it.name; }) : [];
		} catch (e) { return memory.slice(); }
	}
	function write(list) {
		memory = list.slice();
		try { w.localStorage.setItem(KEY, JSON.stringify(list)); } catch (e) {}
		render();
	}
	function keyOf(it) { return it.id ? 'id:' + it.id : 'n:' + norm(it.name); }
	function find(list, it) {
		var k = keyOf(it);
		for (var i = 0; i < list.length; i++) if (keyOf(list[i]) === k) return i;
		return -1;
	}
	function clean(it) {
		return {
			id: parseInt(it.id, 10) || 0,
			name: String(it.name || '').trim(),
			cat: String(it.cat || '').trim(),
			url: String(it.url || ''),
			img: String(it.img || '')
		};
	}
	function add(it) {
		it = clean(it);
		if (!it.name) return false;
		var list = read();
		if (find(list, it) !== -1) return false;
		if (list.length >= MAX) { announce(txt('full', 'Lista are deja ' + MAX + ' produse.')); return false; }
		list.push(it);
		write(list);
		return true;
	}
	function remove(it) {
		var list = read();
		var i = find(list, typeof it === 'object' ? it : { id: it, name: String(it) });
		if (i === -1) return;
		list.splice(i, 1);
		write(list);
	}
	function has(it) { return find(read(), clean(it)) !== -1; }
	function clear() { write([]); }

	function norm(t) {
		return String(t || '').normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase().replace(/\s+/g, ' ').trim();
	}
	function esc(t) {
		return String(t).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
	}
	function txt(k, def) { return (C.i18n && C.i18n[k]) || def; }
	function plural(n) { return n === 1 ? txt('one', '1 produs') : n + ' ' + txt('many', 'produse'); }
	function formUrl(el) {
		var u = el && el.getAttribute('data-pvl-form');
		return u || C.form || '/solicita-oferta/';
	}

	/* ---------- anunțuri pentru cititoarele de ecran + mesaj scurt pe ecran ---------- */
	var live = null, toastEl = null, toastT = 0;
	function announce(msg) {
		if (!live) {
			live = d.createElement('div');
			live.className = 'pvl-sr';
			live.setAttribute('aria-live', 'polite');
			live.setAttribute('role', 'status');
			d.body.appendChild(live);
		}
		live.textContent = '';
		setTimeout(function () { live.textContent = msg; }, 30);
	}
	function toast(name, url) {
		if (!toastEl) {
			toastEl = d.createElement('div');
			toastEl.className = 'pvl-toast';
			d.body.appendChild(toastEl);
		}
		toastEl.innerHTML = '<span class="pvl-toast-t">' + esc(txt('added', 'Adăugat în ofertă')) + ': <strong>' + esc(name) + '</strong></span>' +
			'<a class="pvl-toast-a" href="' + esc(url) + '">' + esc(txt('see', 'Vezi oferta')) + '</a>';
		toastEl.classList.add('is-on');
		clearTimeout(toastT);
		toastT = setTimeout(function () { toastEl.classList.remove('is-on'); }, 4200);
	}

	/* ---------- produsul unui buton ---------- */
	function itemOf(el) {
		var raw = el.getAttribute('data-pvl');
		if (raw) { try { return clean(JSON.parse(raw)); } catch (e) {} }
		if (w.PavvaListaCurrent) return clean(w.PavvaListaCurrent);
		return null;
	}

	/* ---------- afișare: butoane, contoare, liste ---------- */
	function render() {
		var list = read();
		var n = list.length;
		d.documentElement.classList.toggle('pv-oferta-are', n > 0);

		each('[data-pvl-add], .pv-oferta-add, .pv-oferta-go', function (el) {
			var it = itemOf(el);
			var on = !!it && find(list, it) !== -1;
			el.classList.toggle('is-added', on);
			var btn = el.matches('button, a') ? el : el.querySelector('button, a');
			if (btn && btn.tagName === 'BUTTON') btn.setAttribute('aria-pressed', on ? 'true' : 'false');
		});

		each('[data-pvl-count], .pv-oferta-count', function (el) {
			if (el.closest('[data-pvl-preview]')) return;
			/* la elementele Elementor cu clasă: textul e în interior */
			var t = el.matches('[data-pvl-count]') ? el : (el.querySelector('.elementor-heading-title, .elementor-button-text, p, span') || el);
			t.textContent = String(n);
		});

		each('[data-pvl-counter]', function (el) {
			if (el.hasAttribute('data-pvl-preview')) return; /* în editor: exemplul rămâne vizibil, pentru stilizare */
			el.classList.toggle('is-empty', n === 0);
			var base = el.getAttribute('data-pvl-label') || txt('title', 'Oferta ta');
			el.setAttribute('aria-label', n ? base + ' – ' + plural(n) : base + ' – ' + txt('empty', 'niciun produs'));
		});

		each('[data-pvl-list]', renderList);
		forms.forEach(function (f) { f.sync(); });
	}
	function each(sel, fn) { Array.prototype.forEach.call(d.querySelectorAll(sel), fn); }

	var X = '<svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true" focusable="false"><path d="M6 6l12 12M18 6L6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" fill="none"/></svg>';
	function renderList(box) {
		if (box.hasAttribute('data-pvl-preview')) return; /* în editorul Elementor rămân produsele de exemplu */
		var list = read();
		var ul = box.querySelector('.pvl-items');
		if (!ul) return;
		var showImg = box.getAttribute('data-pvl-img') !== '0';
		var showCat = box.getAttribute('data-pvl-cat') !== '0';
		var html = '';
		list.forEach(function (it) {
			html += '<li class="pvl-item">' +
				(showImg ? '<span class="pvl-media">' + (it.img ? '<img src="' + esc(it.img) + '" alt="" loading="lazy" decoding="async">' : '') + '</span>' : '') +
				'<span class="pvl-info">' +
				(it.url ? '<a class="pvl-name" href="' + esc(it.url) + '">' + esc(it.name) + '</a>' : '<span class="pvl-name">' + esc(it.name) + '</span>') +
				(showCat && it.cat ? '<span class="pvl-cat">' + esc(it.cat) + '</span>' : '') +
				'</span>' +
				'<button type="button" class="pvl-remove" data-pvl-remove="' + esc(keyOf(it)) + '" aria-label="' + esc(txt('remove', 'Scoate din ofertă') + ': ' + it.name) + '">' + X + '</button>' +
				'</li>';
		});
		ul.innerHTML = html;
		box.classList.toggle('is-empty', list.length === 0);
		var cnt = box.querySelector('.pvl-title-count');
		if (cnt) cnt.textContent = list.length ? ' (' + list.length + ')' : '';
	}

	/* ---------- clicuri ---------- */
	d.addEventListener('click', function (e) {
		var rm = e.target.closest('[data-pvl-remove]');
		if (rm) {
			e.preventDefault();
			var k = rm.getAttribute('data-pvl-remove');
			var list = read();
			var victim = null;
			for (var i = 0; i < list.length; i++) if (keyOf(list[i]) === k) victim = list[i];
			if (victim) {
				/* focusul trece pe rândul următor (sau pe titlu), nu se pierde */
				var li = rm.closest('.pvl-item');
				var next = li && (li.nextElementSibling || li.previousElementSibling);
				remove(victim);
				announce(txt('removed', 'Scos din ofertă') + ': ' + victim.name);
				var box = rm.closest('[data-pvl-list]') || d;
				var target = next && box.querySelector('[data-pvl-remove]');
				if (target) target.focus();
			}
			return;
		}

		var clr = e.target.closest('[data-pvl-clear]');
		if (clr) { e.preventDefault(); clear(); announce(txt('cleared', 'Lista a fost golită')); return; }

		var el = e.target.closest('[data-pvl-add], .pv-oferta-add, .pv-oferta-go');
		if (!el) return;
		var it = itemOf(el);
		if (!it || !it.name) return;
		e.preventDefault();
		var go = el.classList.contains('pv-oferta-go') || el.getAttribute('data-pvl-mode') === 'go';
		var again = el.getAttribute('data-pvl-again') || 'go';
		if (has(it)) {
			if (go || again === 'go') { w.location.href = formUrl(el); return; }
			remove(it);
			announce(txt('removed', 'Scos din ofertă') + ': ' + it.name);
			return;
		}
		add(it);
		if (go) { w.location.href = formUrl(el); return; }
		announce(txt('added', 'Adăugat în ofertă') + ': ' + it.name);
		if (el.getAttribute('data-pvl-toast') !== '0') toast(it.name, formUrl(el));
	});

	/* tastatura pe elementele Elementor cu clasă (care nu sunt butoane) */
	d.addEventListener('keydown', function (e) {
		if (e.key !== 'Enter' && e.key !== ' ') return;
		var el = e.target.closest('.pv-oferta-add, .pv-oferta-go');
		if (el && !e.target.closest('button, a')) { e.preventDefault(); el.click(); }
	});

	/* altă filă a browserului a schimbat lista */
	w.addEventListener('storage', function (e) { if (e.key === KEY) render(); });

	/* ---------- formularul de ofertă ---------- */
	var forms = [];

	function bindForm(form, cat, prod, D) {
		if (!form || form.__pvl || !prod || prod.tagName !== 'SELECT') return;
		form.__pvl = true;
		var prods = (D && D.prods) || [];
		var byName = {};
		prods.forEach(function (p) { byName[norm(p.name)] = p; });

		/* produsele din listă se actualizează după catalog (nume schimbat, produs șters) */
		if (prods.length && prods[0].id) {
			var byId = {};
			prods.forEach(function (p) { byId[p.id] = p; });
			var list = read(), changed = false;
			list = list.filter(function (it) {
				var p = it.id ? byId[it.id] : byName[norm(it.name)];
				if (!p) { changed = true; return false; }
				['name', 'cat', 'url', 'img'].forEach(function (k) { if (p[k] !== undefined && it[k] !== p[k]) { it[k] = p[k]; changed = true; } });
				if (!it.id && p.id) { it.id = p.id; changed = true; }
				return true;
			});
			if (changed) { memory = list; try { w.localStorage.setItem(KEY, JSON.stringify(list)); } catch (e) {} }
		}

		var ph = null;
		var summary = d.createElement('option');
		summary.setAttribute('data-pvl-summary', '');
		var catsOpt = null, catPrev = '';

		function lines(list) {
			return list.map(function (it, i) { return (i + 1) + '. ' + it.name + (it.cat ? ' (' + it.cat + ')' : ''); }).join('\n');
		}

		function sync() {
			var list = read();
			/* textul de început: „+ Adaugă alt produs” cât timp lista are produse */
			var first = prod.options[0];
			if (ph === null) ph = prod.__pvoPh || (first && !first.value ? first.textContent : '');
			if (first && !first.value && !first.hasAttribute('data-pvl-summary')) first.textContent = list.length ? txt('pick', '+ Adaugă alt produs') : ph;
			if (!list.length) {
				if (summary.parentNode) summary.parentNode.removeChild(summary);
				if (prod.value && prod.value === summary.value) prod.value = '';
				return;
			}
			summary.value = lines(list);
			summary.textContent = list.length === 1 ? list[0].name : list[0].name + ' + ' + plural(list.length - 1);
			if (summary.parentNode !== prod) prod.insertBefore(summary, prod.options[1] || null);
			summary.selected = true;
			prod.value = summary.value;
			/* categoria e obligatorie în formular: dacă e goală, primește categoria primului produs */
			if (cat && cat.tagName === 'SELECT' && !cat.value && list[0].cat) {
				for (var i = 0; i < cat.options.length; i++) {
					if (cat.options[i].value && norm(cat.options[i].value) === norm(list[0].cat)) { cat.value = cat.options[i].value; break; }
				}
			}
		}

		/* alegi un produs din listă → intră în ofertă, iar câmpul arată din nou rezumatul */
		prod.addEventListener('change', function () {
			var v = prod.value;
			if (!v || v === summary.value) return;
			var p = byName[norm(v)] || { name: v, cat: cat && cat.value ? cat.value : '' };
			add(p);
			announce(txt('added', 'Adăugat în ofertă') + ': ' + p.name);
		});
		/* lista de produse se reface când schimbi categoria: punem rezumatul la loc */
		if (cat) cat.addEventListener('change', function () { setTimeout(sync, 0); });

		/* la trimitere: ambele câmpuri primesc toate produsele / categoriile (înaintea scriptului formularului) */
		function beforeSubmit() {
			var list = read();
			if (!list.length) return;
			sync();
			if (cat && cat.tagName === 'SELECT') {
				var cats = [];
				list.forEach(function (it) { if (it.cat && cats.indexOf(it.cat) === -1) cats.push(it.cat); });
				if (cats.length > 1) {
					if (!catsOpt) { catsOpt = d.createElement('option'); catsOpt.setAttribute('data-pvl-cats', ''); }
					if (catsOpt.parentNode !== cat) { catPrev = cat.value; cat.appendChild(catsOpt); }
					catsOpt.value = cats.join(', ');
					catsOpt.textContent = cats.join(', ');
					cat.value = catsOpt.value;
				}
			}
		}
		/* după trimitere, la prima atingere a categoriei, revine valoarea de filtrare */
		function restoreCat() {
			if (catsOpt && catsOpt.parentNode) { catsOpt.parentNode.removeChild(catsOpt); cat.value = catPrev; }
		}
		if (cat) ['pointerdown', 'focus', 'keydown'].forEach(function (t) { cat.addEventListener(t, restoreCat); });

		/* trimis cu succes → lista se golește */
		var okSel = '[data-element_type="e-form-success-message"], .elementor-message-success, .wpcf7-mail-sent-ok, .wpforms-confirmation-container';
		function visible(el) { return !!(el && (el.offsetWidth || el.offsetHeight || el.getClientRects().length) && getComputedStyle(el).visibility !== 'hidden'); }
		/* formularul Elementor V4 primește clasa form-state-success (la ea pornește și redirecționarea spre „Mulțumim”) */
		function success() {
			return form.classList.contains('form-state-success') ||
				Array.prototype.some.call(form.querySelectorAll(okSel), visible) ||
				!!(form.parentNode && form.parentNode.querySelector('.elementor-message-success'));
		}
		var shownAtStart = success();
		var pending = false;
		function checkOk() {
			if (!pending) return;
			if (success() && !shownAtStart) { pending = false; restoreCat(); clear(); }
		}
		if (w.MutationObserver) new MutationObserver(checkOk).observe(form, { attributes: true, childList: true, subtree: true });
		if (w.jQuery) w.jQuery(form).on('submit_success', function () { pending = true; checkOk(); restoreCat(); clear(); });

		var api = { form: form, sync: sync, before: function () { beforeSubmit(); pending = true; } };
		forms.push(api);

		/* lista se arată deasupra câmpurilor de produs, dacă pe pagină nu e pus widgetul „Listă ofertă” */
		if (!d.querySelector('[data-pvl-list]') && C.autoList !== false) {
			var anchor = cat && form.contains(cat) ? cat : prod;
			var node = anchor;
			while (node.parentElement && node.parentElement !== form) node = node.parentElement;
			var box = d.createElement('div');
			box.className = 'pvl-list pvl-list-auto is-empty';
			box.setAttribute('data-pvl-list', '');
			box.innerHTML = '<p class="pvl-title">' + esc(txt('listTitle', 'Produsele tale')) + '<span class="pvl-title-count"></span></p>' +
				'<ul class="pvl-items"></ul>' +
				'<p class="pvl-empty">' + esc(txt('listEmpty', 'Alege produsele dorite din lista de mai jos. Poți adăuga mai multe.')) + '</p>';
			form.insertBefore(box, node);
		}
		render();
	}

	/* capturăm trimiterea înaintea scriptului formularului (Elementor citește câmpurile în același moment) */
	d.addEventListener('submit', function (e) {
		forms.forEach(function (f) { if (f.form === e.target) f.before(); });
	}, true);

	/* oferta.js anunță formularele cu categorie / produs (după ce le-a completat) */
	d.addEventListener('pavva:oferta-form', function (e) {
		var x = e.detail || {};
		bindForm(e.target, x.categorie, x.produs, x.data);
	});

	function start() {
		each('form', function (form) {
			var x = form.__pvoProducts;
			if (x) bindForm(form, x.categorie, x.produs, x.data);
		});
		render();
	}
	if (d.readyState === 'loading') d.addEventListener('DOMContentLoaded', start); else start();

	w.PavvaLista = { get: read, add: add, remove: remove, has: has, clear: clear, count: function () { return read().length; } };
})(window, document);
