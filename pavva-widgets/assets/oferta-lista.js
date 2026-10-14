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
	/* o singură dată, chiar dacă scriptul ajunge de două ori în pagină (ex. optimizări de cache) */
	if (w.PavvaLista) return;

	var C = w.PavvaListaCfg || {};
	var KEY = 'pavva_oferta_v1';
	var MAX = 20;
	var memory = [];
	var dataCache = null;

	/* ---------- lista (localStorage, cu rezervă în memorie) ---------- */
	function read() {
		try {
			var v = JSON.parse(w.localStorage.getItem(KEY) || '[]');
			return Array.isArray(v) ? v.filter(function (it) { return it && it.name; }).map(single) : [];
		} catch (e) { return memory.slice(); }
	}
	function write(list) {
		memory = list.slice();
		try { w.localStorage.setItem(KEY, JSON.stringify(list)); } catch (e) {}
		render();
	}
	/* produs + variantă (culoare / grosime): același produs în două culori = două rânduri în ofertă */
	function pkey(it) { return it.id ? 'id:' + it.id : 'n:' + norm(it.name); }
	function keyOf(it) { return pkey(it) + '|' + norm(it.color) + '|' + norm(it.thick); }
	function hasProduct(it) { var k = pkey(it); return read().some(function (x) { return pkey(x) === k; }); }
	function hasOpts(it) { return !!(it && it.opts && ((it.opts.colors && it.opts.colors.length) || (it.opts.thick && it.opts.thick.length))); }
	/* o singură culoare / grosime nu e o alegere: e completată automat (fereastra o arată deja bifată, fără „Nu m-am hotărât încă”) */
	function single(it) {
		var o = it && it.opts;
		if (!o) return it;
		if (o.colors && o.colors.length === 1 && !it.color) { it.color = String(o.colors[0].n); it.ct = String(o.colors[0].t || ''); }
		if (o.thick && o.thick.length === 1 && !it.thick) it.thick = String(o.thick[0]);
		return it;
	}
	function needsChoice(it) {
		if (!hasOpts(it)) return false;
		return (it.opts.colors && it.opts.colors.length && !it.color) || (it.opts.thick && it.opts.thick.length && !it.thick);
	}
	function varText(it) {
		var p = [];
		if (it.color) p.push(txt('color', 'Culoare') + ': ' + it.color);
		if (it.thick) p.push(txt('thick', 'Grosime') + ': ' + it.thick);
		return p.join(', ');
	}
	function find(list, it) {
		var k = keyOf(it);
		for (var i = 0; i < list.length; i++) if (keyOf(list[i]) === k) return i;
		return -1;
	}
	function clean(it) {
		var o = {
			id: parseInt(it.id, 10) || 0,
			name: String(it.name || '').trim(),
			cat: String(it.cat || '').trim(),
			url: String(it.url || ''),
			img: String(it.img || ''),
			color: String(it.color || '').trim(),
			ct: String(it.ct || ''),
			thick: String(it.thick || '').trim()
		};
		/* culorile și grosimile posibile rămân în listă: varianta se poate schimba oricând („Modifică”) */
		if (it.opts && typeof it.opts === 'object') {
			o.opts = {
				colors: (it.opts.colors || []).filter(function (c) { return c && c.n; }).map(function (c) { return { n: String(c.n), t: String(c.t || '') }; }),
				thick: (it.opts.thick || []).map(String)
			};
			if (!o.opts.colors.length && !o.opts.thick.length) delete o.opts;
		}
		return single(o);
	}
	/* schimbă varianta unui rând (păstrează locul în listă; dacă varianta nouă există deja, rândurile se unesc) */
	function replaceItem(oldKey, it) {
		it = clean(it);
		var list = read();
		var i = -1, j = -1;
		for (var n = 0; n < list.length; n++) { if (keyOf(list[n]) === oldKey) i = n; }
		for (n = 0; n < list.length; n++) { if (n !== i && keyOf(list[n]) === keyOf(it)) j = n; }
		if (i === -1) return add(it);
		if (j !== -1) list.splice(i, 1); else list[i] = it;
		write(list);
		return true;
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
	/* bara de jos „Oferta ta: 3 produse · Vezi oferta”: rămâne cât lista are produse (nu și pe pagina formularului),
	   ca vizitatorul să ajungă ușor la formular după ce a ales; după o adăugare arată câteva secunde produsul adăugat */
	var BAR_OFF = 'pavva_oferta_bar_off';
	function barOff() { try { return w.sessionStorage.getItem(BAR_OFF) === '1'; } catch (e) { return false; } }
	function onFormPage() {
		if (forms.length) return true;
		try { return new URL(C.form || '/solicita-oferta/', w.location.href).pathname.replace(/\/+$/, '') === w.location.pathname.replace(/\/+$/, ''); } catch (e) { return false; }
	}
	function ensureBar() {
		if (toastEl) return toastEl;
		toastEl = d.createElement('div');
		toastEl.className = 'pvl-toast';
		toastEl.setAttribute('role', 'region');
		toastEl.setAttribute('aria-label', txt('title', 'Oferta ta'));
		toastEl.innerHTML = '<span class="pvl-toast-t"></span><a class="pvl-toast-a" href="#"></a>' +
			'<button type="button" class="pvl-toast-x" aria-label="' + esc(txt('hide', 'Ascunde bara')) + '">' + X + '</button>';
		d.body.appendChild(toastEl);
		toastEl.querySelector('.pvl-toast-x').addEventListener('click', function () {
			try { w.sessionStorage.setItem(BAR_OFF, '1'); } catch (e) {}
			toastEl.classList.remove('is-on');
		});
		return toastEl;
	}
	function bar(flashName, url) {
		var n = read().length;
		var show = C.bar !== false && n > 0 && !onFormPage() && !barOff() && !(dw && !dw.hidden);
		if (!show && !toastEl) return;
		var el = ensureBar();
		el.querySelector('.pvl-toast-a').setAttribute('href', url || C.form || '/solicita-oferta/');
		var bc = dcfg();
		el.querySelector('.pvl-toast-a').textContent = bc.barSee;
		var t = el.querySelector('.pvl-toast-t');
		if (flashName) {
			t.innerHTML = esc(bc.barAdded) + ': <strong>' + esc(flashName) + '</strong>';
			clearTimeout(toastT);
			toastT = setTimeout(function () { toastT = 0; bar(); }, 3500);
		} else if (!toastT) {
			t.innerHTML = esc(bc.barLabel) + ': <strong>' + esc(plural(n)) + '</strong>';
		}
		el.classList.toggle('is-on', show || (!!flashName && C.bar === false));
		d.documentElement.classList.toggle('pv-oferta-bar', show);
	}
	function toast(name, url) {
		/* la adăugare, bara reapare chiar dacă a fost închisă */
		try { w.sessionStorage.removeItem(BAR_OFF); } catch (e) {}
		bar(name, url);
		if (C.bar === false) { clearTimeout(toastT); toastT = setTimeout(function () { toastT = 0; if (toastEl) toastEl.classList.remove('is-on'); }, 4200); }
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
		navItems();
		var list = read();
		var n = list.length;
		d.documentElement.classList.toggle('pv-oferta-are', n > 0);

		each('[data-pvl-add], .pv-oferta-add, .pv-oferta-go', function (el) {
			var it = itemOf(el);
			var on = !!it && hasProduct(it);
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
			if (dcfg().on) el.setAttribute('aria-haspopup', 'dialog');
		});

		each('[data-pvl-list], [data-pvl-drawer-list]', renderList);
		drawerSync();
		forms.forEach(function (f) { f.sync(); });
		bar();
	}

	/* ---------- „Oferta ta” în meniul de navigare ----------
	   Appearance → Menus: un link (de ex. /solicita-oferta/) cu clasa CSS pv-oferta-nav
	   (+ pv-oferta-nav-icon = doar iconiță, + pv-oferta-nav-hide = ascuns cât lista e goală) */
	var ICON = '<svg class="pvl-nav-ico" viewBox="0 0 384 512" aria-hidden="true" focusable="false"><path fill="currentColor" d="M336 64h-80c0-35.3-28.7-64-64-64s-64 28.7-64 64H48C21.5 64 0 85.5 0 112v352c0 26.5 21.5 48 48 48h288c26.5 0 48-21.5 48-48V112c0-26.5-21.5-48-48-48zM96 424c-13.3 0-24-10.7-24-24s10.7-24 24-24 24 10.7 24 24-10.7 24-24 24zm0-96c-13.3 0-24-10.7-24-24s10.7-24 24-24 24 10.7 24 24-10.7 24-24 24zm0-96c-13.3 0-24-10.7-24-24s10.7-24 24-24 24 10.7 24 24-10.7 24-24 24zm96-192c13.3 0 24 10.7 24 24s-10.7 24-24 24-24-10.7-24-24 10.7-24 24-24zm128 368c0 4.4-3.6 8-8 8H168c-4.4 0-8-3.6-8-8v-16c0-4.4 3.6-8 8-8h144c4.4 0 8 3.6 8 8v16zm0-96c0 4.4-3.6 8-8 8H168c-4.4 0-8-3.6-8-8v-16c0-4.4 3.6-8 8-8h144c4.4 0 8 3.6 8 8v16zm0-96c0 4.4-3.6 8-8 8H168c-4.4 0-8-3.6-8-8v-16c0-4.4 3.6-8 8-8h144c4.4 0 8 3.6 8 8v16z"/></svg>';
	function navItems() {
		each('.pv-oferta-nav', function (li) {
			var a = li.tagName === 'A' ? li : li.querySelector('a');
			if (!a || a.__pvl) return;
			a.__pvl = true;
			var label = (a.textContent || '').trim() || txt('title', 'Oferta ta');
			a.classList.add('pvl-nav');
			a.setAttribute('data-pvl-counter', '');
			a.setAttribute('data-pvl-label', label);
			if (li.classList.contains('pv-oferta-nav-hide')) a.setAttribute('data-pvl-hide-empty', '');
			var iconOnly = li.classList.contains('pv-oferta-nav-icon');
			if (iconOnly) {
				a.innerHTML = '<span class="pvl-c-ico">' + ICON + '<span class="pvl-badge" data-pvl-count>0</span></span><span class="pvl-sr">' + esc(label) + '</span>';
				a.classList.add('pvl-nav-icon');
			} else {
				var b = d.createElement('span');
				b.className = 'pvl-badge pvl-badge-inline';
				b.setAttribute('data-pvl-count', '');
				b.setAttribute('aria-hidden', 'true');
				a.appendChild(b);
			}
		});
	}
	function each(sel, fn) { Array.prototype.forEach.call(d.querySelectorAll(sel), fn); }

	var X = '<svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true" focusable="false"><path d="M6 6l12 12M18 6L6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" fill="none"/></svg>';
	/* eticheta unei variante în listă; „Nu m-am hotărât încă” apare scurt, cu numele criteriului („Culoare: nehotărâtă”) */
	function tag(v, k, img) {
		if (!v) return '';
		var cls = 'pvl-var-' + k;
		if (norm(v) === norm(undec())) return '<span class="' + cls + ' is-undec" title="' + esc(v) + '">' + esc((k === 'c' ? txt('color', 'Culoare') : txt('thick', 'Grosime')) + ': ' + txt('undecShort', 'nehotărâtă')) + '</span>';
		return '<span class="' + cls + (img ? ' has-sw' : '') + '">' + (img ? '<img src="' + esc(img) + '" alt="" loading="lazy">' : '') + esc(v) + '</span>';
	}
	function renderList(box) {
		if (box.hasAttribute('data-pvl-preview')) return; /* în editorul Elementor rămân produsele de exemplu */
		var list = read();
		var ul = box.querySelector('.pvl-items');
		if (!ul) return;
		var showImg = box.getAttribute('data-pvl-img') !== '0';
		var showCat = box.getAttribute('data-pvl-cat') !== '0';
		var html = '';
		list.forEach(function (it) {
			html += '<li class="pvl-item' + (needsChoice(it) ? ' pvl-need' : '') + '">' +
				(showImg ? '<span class="pvl-media">' + (it.img ? '<img src="' + esc(it.img) + '" alt="" loading="lazy" decoding="async">' : '') + '</span>' : '') +
				'<span class="pvl-info">' +
				(it.url ? '<a class="pvl-name" href="' + esc(it.url) + '">' + esc(it.name) + '</a>' : '<span class="pvl-name">' + esc(it.name) + '</span>') +
				(showCat && it.cat ? '<span class="pvl-cat">' + esc(it.cat) + '</span>' : '') +
				((it.color || it.thick) ? '<span class="pvl-var">' + tag(it.color, 'c', it.ct) + tag(it.thick, 't') + '</span>' : '') +
				(hasOpts(it) ? '<button type="button" class="pvl-edit" data-pvl-edit="' + esc(keyOf(it)) + '" aria-label="' + esc(txt('edit', 'Modifică') + ': ' + it.name) + '">' + esc(needsChoice(it) ? txt('choose', 'Alege culoarea / grosimea') : txt('edit', 'Modifică')) + '</button>' : '') +
				'</span>' +
				'<button type="button" class="pvl-remove" data-pvl-remove="' + esc(keyOf(it)) + '" aria-label="' + esc(txt('remove', 'Scoate din ofertă') + ': ' + it.name + (varText(it) ? ' (' + varText(it) + ')' : '')) + '">' + X + '</button>' +
				'</li>';
		});
		ul.innerHTML = html;
		box.classList.toggle('is-empty', list.length === 0);
		box.classList.toggle('is-multi', list.length > 1);
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
				var box = rm.closest('[data-pvl-list], [data-pvl-drawer-list]') || d;
				var target = next && box.querySelector('[data-pvl-remove]');
				if (target) target.focus();
				else if (dw && dw.contains(box)) { var x = dw.querySelector('.pvl-dw-x'); if (x) x.focus(); }
			}
			return;
		}

		var ed = e.target.closest('[data-pvl-edit]');
		if (ed) {
			e.preventDefault();
			var ek = ed.getAttribute('data-pvl-edit');
			var cur = read().filter(function (x) { return keyOf(x) === ek; })[0];
			if (cur) openPicker(cur, { edit: true, from: ed, done: function (v) { replaceItem(ek, v); announce(txt('saved', 'Variantă salvată') + ': ' + v.name + (varText(v) ? ', ' + varText(v) : '')); } });
			return;
		}

		var cls = e.target.closest('[data-pvl-close]');
		if (cls) { e.preventDefault(); closeDrawer(); return; }

		/* iconița „Oferta ta”, linkul din meniu, „Vezi oferta” din bară sau orice element cu clasa pv-oferta-open → panoul */
		var op = e.target.closest('[data-pvl-counter], .pvl-toast-a, .pv-oferta-open');
		if (op && !op.closest('[data-pvl-preview]') && dcfg().on) { e.preventDefault(); openDrawer(op); return; }

		var cta = e.target.closest('.pvl-dw-cta');
		if (cta && onFormPage()) {
			/* suntem deja pe pagina formularului: închidem panoul și ducem vizitatorul la formular */
			e.preventDefault();
			closeDrawer(true);
			var fm = forms[0] && forms[0].form;
			if (fm) { fm.scrollIntoView({ behavior: 'smooth', block: 'start' }); var fld = fm.querySelector('input, select, textarea'); if (fld) setTimeout(function () { fld.focus({ preventScroll: true }); }, 500); }
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
		var done = function (v) {
			add(v);
			if (go) { w.location.href = formUrl(el); return; }
			announce(txt('added', 'Adăugat în ofertă') + ': ' + v.name + (varText(v) ? ', ' + varText(v) : ''));
			if (dcfg().on && dcfg().auto) { openDrawer(el); return; }
			if (el.getAttribute('data-pvl-toast') !== '0') toast(v.name, formUrl(el));
		};
		if (hasProduct(it)) {
			if (go || again === 'go') { w.location.href = formUrl(el); return; }
			/* cu culori / grosimi: încă o variantă a aceluiași produs; fără: al doilea click îl scoate */
			if (hasOpts(it) && pickOn()) { openPicker(it, { from: el, done: done }); return; }
			read().filter(function (x) { return pkey(x) === pkey(it); }).forEach(function (x) { remove(x); });
			announce(txt('removed', 'Scos din ofertă') + ': ' + it.name);
			return;
		}
		if (hasOpts(it) && pickOn()) { openPicker(it, { from: el, done: done }); return; }
		done(it);
	});

	/* ---------- fereastra „Alege varianta” (culoare cu thumbnail + grosime) ---------- */
	var pk = null, pkLast = null;
	function pickOn() { return C.picker !== false && dcfg().pick; }
	function undec() { return txt('undecided', 'Nu m-am hotărât încă'); }
	function pickerBody(it, cur) {
		var o = it.opts || {}, h = '';
		var Q = '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M9.2 9a3 3 0 1 1 4.3 2.7c-.9.5-1.5 1.2-1.5 2.3v.5M12 18h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round" fill="none"/></svg>';
		var val = function (v) { return v ? '· ' + esc(v) : ''; };
		if (o.colors && o.colors.length) {
			var c1 = o.colors.length === 1, cv = cur ? cur.color : (c1 ? o.colors[0].n : '');
			h += '<fieldset class="pvl-pk-group" data-pvl-group="color"><legend class="pvl-pk-label">' + esc(txt('color', 'Culoare')) + ' <span class="pvl-pk-val" data-pvl-val="color">' + val(cv) + '</span></legend><div class="pvl-pk-colors">';
			(c1 ? o.colors : o.colors.concat([{ n: undec(), t: '', u: 1 }])).forEach(function (c) {
				var on = !!cv && norm(cv) === norm(c.n);
				h += '<button type="button" class="pvl-pk-color' + (c.u ? ' pvl-pk-undec' : '') + (on ? ' is-on' : '') + '" aria-pressed="' + (on ? 'true' : 'false') + '" data-pvl-color="' + esc(c.n) + '" data-pvl-ct="' + esc(c.t) + '">' +
					'<span class="pvl-pk-sw">' + (c.u ? Q : (c.t ? '<img src="' + esc(c.t) + '" alt="" loading="lazy" decoding="async">' : '')) + '</span><span class="pvl-pk-cn">' + esc(c.n) + '</span></button>';
			});
			h += '</div><p class="pvl-pk-err" role="alert" hidden>' + esc(txt('needColor', 'Alege o culoare (sau „Nu m-am hotărât încă”).')) + '</p></fieldset>';
		}
		if (o.thick && o.thick.length) {
			var t1 = o.thick.length === 1, tv = cur ? cur.thick : (t1 ? o.thick[0] : '');
			h += '<fieldset class="pvl-pk-group" data-pvl-group="thick"><legend class="pvl-pk-label">' + esc(txt('thick', 'Grosime')) + ' <span class="pvl-pk-val" data-pvl-val="thick">' + val(tv) + '</span></legend><div class="pvl-pk-thick">';
			(t1 ? o.thick : o.thick.concat([undec()])).forEach(function (t, i) {
				var u = i === o.thick.length;
				var on = !!tv && norm(tv) === norm(t);
				h += '<button type="button" class="pvl-pk-t' + (u ? ' pvl-pk-undec' : '') + (on ? ' is-on' : '') + '" aria-pressed="' + (on ? 'true' : 'false') + '" data-pvl-thick="' + esc(t) + '">' + esc(t) + '</button>';
			});
			h += '</div><p class="pvl-pk-err" role="alert" hidden>' + esc(txt('needThick', 'Alege grosimea (sau „Nu m-am hotărât încă”).')) + '</p></fieldset>';
		}
		h += '<p class="pvl-pk-note">' + esc(dcfg().pkNote) + '</p>';
		return h;
	}
	/* fiecare grup trebuie ales (o culoare / o grosime sau „Nu m-am hotărât încă”); altfel mesaj lângă grup */
	function pickerValid(root) {
		var first = null;
		Array.prototype.forEach.call(root.querySelectorAll('[data-pvl-group]'), function (g) {
			var ok = !!g.querySelector('.is-on');
			g.classList.toggle('is-missing', !ok);
			g.querySelector('.pvl-pk-err').hidden = ok;
			if (!ok && !first) first = g;
		});
		if (first) {
			var b = first.querySelector('button');
			first.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
			if (b) b.focus({ preventScroll: true });
			return false;
		}
		return true;
	}
	/* alegerea din fereastră / din formular: un singur buton apăsat pe grup; al doilea click îl deselectează */
	function bindChoices(root) {
		var upd = function () {
			['color', 'thick'].forEach(function (k) {
				var v = root.querySelector('[data-pvl-val="' + k + '"]');
				var on = root.querySelector('[data-pvl-' + k + '].is-on');
				if (v) v.textContent = on ? '· ' + on.getAttribute('data-pvl-' + k) : '';
			});
		};
		root.addEventListener('click', function (e) {
			var b = e.target.closest('[data-pvl-color], [data-pvl-thick]');
			if (!b || !root.contains(b)) return;
			e.preventDefault();
			var k = b.hasAttribute('data-pvl-color') ? 'color' : 'thick';
			Array.prototype.forEach.call(root.querySelectorAll('[data-pvl-' + k + ']'), function (x) { x.classList.remove('is-on'); x.setAttribute('aria-pressed', 'false'); });
			b.classList.add('is-on'); b.setAttribute('aria-pressed', 'true');
			var g = b.closest('[data-pvl-group]');
			if (g) { g.classList.remove('is-missing'); g.querySelector('.pvl-pk-err').hidden = true; }
			upd();
		});
		upd();
	}
	function chosen(root, it) {
		var c = root.querySelector('[data-pvl-color].is-on'), t = root.querySelector('[data-pvl-thick].is-on');
		var v = clean(it);
		v.color = c ? c.getAttribute('data-pvl-color') : '';
		v.ct = c ? c.getAttribute('data-pvl-ct') : '';
		v.thick = t ? t.getAttribute('data-pvl-thick') : '';
		return v;
	}
	function openPicker(it, o) {
		closePicker(true);
		var c = dcfg();
		pkLast = o.from || d.activeElement;
		pk = d.createElement('div');
		pk.className = 'pvl-pick';
		pk.innerHTML = '<div class="pvl-pk-overlay" data-pvl-pk-close></div>' +
			'<div class="pvl-pk-box" role="dialog" aria-modal="true" aria-labelledby="pvl-pk-title" data-lenis-prevent>' +
				'<div class="pvl-pk-head">' + (it.img ? '<img class="pvl-pk-img" src="' + esc(it.img) + '" alt="">' : '') +
					'<div class="pvl-pk-titles"><p class="pvl-pk-kicker">' + esc(c.pkTitle) + '</p><h2 class="pvl-pk-title" id="pvl-pk-title">' + esc(it.name) + '</h2>' + (it.cat ? '<p class="pvl-pk-cat">' + esc(it.cat) + '</p>' : '') + '</div>' +
					'<button type="button" class="pvl-pk-x" data-pvl-pk-close aria-label="' + esc(txt('close', 'Închide')) + '">' + X + '</button></div>' +
				'<div class="pvl-pk-body">' + pickerBody(it, o.edit ? it : null) + '</div>' +
				'<div class="pvl-pk-foot"><button type="button" class="pvl-pk-ok">' + esc(o.edit ? c.pkSave : c.pkAdd) + '</button>' +
					'<button type="button" class="pvl-pk-cancel" data-pvl-pk-close>' + esc(c.pkCancel) + '</button></div>' +
			'</div>';
		d.body.appendChild(pk);
		bindChoices(pk);
		d.documentElement.classList.add('pvl-pk-open');
		pk.addEventListener('click', function (e) {
			if (e.target.closest('[data-pvl-pk-close]')) { e.preventDefault(); closePicker(); return; }
			if (e.target.closest('.pvl-pk-ok')) { e.preventDefault(); if (!pickerValid(pk)) return; var v = chosen(pk, it); closePicker(true); o.done(v); }
		});
		requestAnimationFrame(function () { requestAnimationFrame(function () { if (pk) pk.classList.add('is-open'); }); });
		setTimeout(function () { var f = pk && (pk.querySelector('.pvl-pk-color, .pvl-pk-t') || pk.querySelector('.pvl-pk-ok')); if (f) f.focus({ preventScroll: true }); }, 60);
	}
	function closePicker(silent) {
		if (!pk) return;
		var el = pk; pk = null;
		el.remove();
		d.documentElement.classList.remove('pvl-pk-open');
		if (!silent && pkLast && pkLast.focus && d.contains(pkLast)) pkLast.focus({ preventScroll: true });
	}
	d.addEventListener('keydown', function (e) {
		if (!pk) return;
		if (e.key === 'Escape') { e.preventDefault(); closePicker(); return; }
		if (e.key !== 'Tab') return;
		var f = Array.prototype.filter.call(pk.querySelectorAll('button'), function (b) { return b.offsetWidth || b.offsetHeight; });
		if (!f.length) return;
		if (e.shiftKey && d.activeElement === f[0]) { e.preventDefault(); f[f.length - 1].focus(); }
		else if (!e.shiftKey && d.activeElement === f[f.length - 1]) { e.preventDefault(); f[0].focus(); }
	}, true);

	/* ---------- panoul lateral „Oferta ta” (ca un coș de cumpărături) ----------
	   Setările vin din widgetul „Oferta ta” (atributele data-pvl-* de pe iconiță); fără widget: valorile implicite. */
	var dw = null, dwLast = null, dwT = 0;
	function dcfg() {
		var el = d.querySelector('[data-pvl-counter][data-pvl-drawer]');
		var g = function (k, def) { var v = el && el.getAttribute('data-pvl-' + k); return v === null || v === undefined || v === '' ? def : v; };
		return {
			on: C.drawer !== false && g('drawer', '1') !== '0',
			side: g('side', 'right') === 'left' ? 'left' : 'right',
			auto: g('auto', '0') === '1',
			title: g('t-title', txt('title', 'Oferta ta')),
			cta: g('t-cta', txt('cta', 'Solicită ofertă')),
			more: g('t-more', txt('more', 'Continuă să alegi')),
			empty: g('t-empty', txt('dwEmpty', 'Nu ai adăugat încă niciun produs. Apasă „Adaugă la ofertă” pe produsele care te interesează.')),
			browse: g('t-browse', txt('browse', 'Vezi produsele')),
			browseUrl: g('browse-url', ''),
			form: g('form', C.form || '/solicita-oferta/'),
			barLabel: g('t-bar-label', txt('title', 'Oferta ta')),
			barSee: g('t-bar-see', txt('see', 'Vezi oferta')),
			barAdded: g('t-bar-added', txt('added', 'Adăugat în ofertă')),
			pick: g('pick', '1') !== '0',
			pkTitle: g('t-pk-title', txt('pkTitle', 'Alege varianta')),
			pkAdd: g('t-pk-add', txt('pkAdd', 'Adaugă la ofertă')),
			pkSave: g('t-pk-save', txt('pkSave', 'Salvează')),
			pkCancel: g('t-pk-cancel', txt('pkCancel', 'Anulează')),
			pkNote: g('t-pk-note', txt('pkNote', 'Nu știi încă? Alege „Nu m-am hotărât încă”: te ajutăm noi la telefon.'))
		};
	}
	function buildDrawer() {
		if (dw) return dw;
		var c = dcfg();
		dw = d.createElement('div');
		dw.className = 'pvl-drawer pvl-drawer-' + c.side;
		dw.hidden = true;
		dw.innerHTML =
			'<div class="pvl-dw-overlay" data-pvl-close></div>' +
			'<div class="pvl-dw-panel" role="dialog" aria-modal="true" aria-labelledby="pvl-dw-title" tabindex="-1" data-lenis-prevent>' +
				'<div class="pvl-dw-head"><h2 class="pvl-dw-title" id="pvl-dw-title">' + esc(c.title) + '<span class="pvl-dw-count"></span></h2>' +
				'<button type="button" class="pvl-dw-x" data-pvl-close aria-label="' + esc(txt('close', 'Închide')) + '">' + X + '</button></div>' +
				'<div class="pvl-dw-body">' +
					'<div class="pvl-list pvl-dw-list" data-pvl-drawer-list data-pvl-img="1" data-pvl-cat="1"><ul class="pvl-items"></ul></div>' +
					'<div class="pvl-dw-empty"><p>' + esc(c.empty) + '</p>' +
						(c.browseUrl ? '<a class="pvl-dw-browse" href="' + esc(c.browseUrl) + '">' + esc(c.browse) + '</a>' : '<button type="button" class="pvl-dw-browse" data-pvl-close>' + esc(c.browse) + '</button>') +
					'</div>' +
				'</div>' +
				'<div class="pvl-dw-foot">' +
					'<a class="pvl-dw-cta" href="' + esc(c.form) + '">' + esc(c.cta) + '</a>' +
					'<button type="button" class="pvl-dw-more" data-pvl-close>' + esc(c.more) + '</button>' +
				'</div>' +
			'</div>';
		d.body.appendChild(dw);
		renderList(dw.querySelector('[data-pvl-drawer-list]'));
		drawerSync();
		return dw;
	}
	function drawerSync() {
		if (!dw) return;
		var n = read().length;
		dw.classList.toggle('is-empty', n === 0);
		dw.querySelector('.pvl-dw-count').textContent = n ? ' (' + n + ')' : '';
	}
	function focusables() {
		return Array.prototype.filter.call(dw.querySelectorAll('a[href], button:not([disabled]), [tabindex]:not([tabindex="-1"])'), function (el) { return el.offsetWidth || el.offsetHeight; });
	}
	function openDrawer(from) {
		buildDrawer();
		dwLast = from || d.activeElement;
		clearTimeout(dwT);
		dw.hidden = false;
		d.documentElement.classList.add('pvl-dw-open');
		/* bara de jos nu mai are rost cât panoul e deschis */
		if (toastEl) toastEl.classList.remove('is-on');
		requestAnimationFrame(function () { requestAnimationFrame(function () { dw.classList.add('is-open'); }); });
		setTimeout(function () { var x = dw.querySelector('.pvl-dw-x'); if (x) x.focus({ preventScroll: true }); }, 60);
	}
	function closeDrawer(noFocus) {
		if (!dw || dw.hidden) return;
		dw.classList.remove('is-open');
		d.documentElement.classList.remove('pvl-dw-open');
		var back = !noFocus && dwLast && dwLast.focus && d.contains(dwLast) ? dwLast : null;
		/* butonul din bara de jos reapare abia după închidere: focusul revine pe el atunci */
		var later = back && toastEl && toastEl.contains(back);
		dwT = setTimeout(function () { dw.hidden = true; bar(); if (later) back.focus({ preventScroll: true }); }, 320);
		if (back && !later) back.focus({ preventScroll: true });
	}
	d.addEventListener('keydown', function (e) {
		if (!dw || dw.hidden) return;
		if (e.key === 'Escape') { e.preventDefault(); closeDrawer(); return; }
		if (e.key !== 'Tab') return;
		/* focusul rămâne în panou cât e deschis */
		var f = focusables();
		if (!f.length) return;
		var first = f[0], last = f[f.length - 1];
		if (e.shiftKey && (d.activeElement === first || !dw.contains(d.activeElement))) { e.preventDefault(); last.focus(); }
		else if (!e.shiftKey && (d.activeElement === last || !dw.contains(d.activeElement))) { e.preventDefault(); first.focus(); }
	});

	/* tastatura pe elementele Elementor cu clasă (care nu sunt butoane) */
	d.addEventListener('keydown', function (e) {
		if (e.key !== 'Enter' && e.key !== ' ') return;
		var el = e.target.closest('.pv-oferta-add, .pv-oferta-go');
		if (el && !e.target.closest('button, a')) { e.preventDefault(); el.click(); }
	});

	/* altă filă a browserului a schimbat lista */
	w.addEventListener('storage', function (e) { if (e.key === KEY) render(); });

	/* „Înapoi” în browser: pagina revine din memorie exact cum a fost lăsată (panou deschis, butoane vechi).
	   Panoul se închide înainte de plecare și totul se recitește la întoarcere. */
	function shutDrawerNow() {
		if (!dw) return;
		clearTimeout(dwT);
		dw.classList.remove('is-open');
		dw.hidden = true;
		d.documentElement.classList.remove('pvl-dw-open');
	}
	w.addEventListener('pagehide', shutDrawerNow);
	w.addEventListener('pageshow', function (e) { if (e.persisted) { shutDrawerNow(); render(); } });
	d.addEventListener('visibilitychange', function () { if (d.visibilityState === 'visible') render(); });

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
				if (p.opts && JSON.stringify(p.opts) !== JSON.stringify(it.opts || null)) { it.opts = p.opts; changed = true; }
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
			return list.map(function (it, i) { return (i + 1) + '. ' + it.name + (it.cat ? ' (' + it.cat + ')' : '') + (varText(it) ? ' – ' + varText(it) : ''); }).join('\n');
		}

		function sync() {
			var list = read();
			/* textul de început: „+ Adaugă alt produs” cât timp lista are produse */
			var first = prod.options[0];
			if (ph === null) ph = prod.__pvoPh || (first && !first.value ? first.textContent : '');
			if (first && !first.value && !first.hasAttribute('data-pvl-summary')) first.textContent = list.length ? txt('pick', '+ Adaugă alt produs') : ph;
			/* cu produse în listă, categoria devine doar un filtru pentru „+ Adaugă alt produs” (nu mai e obligatorie);
			   goală = toate produsele. La trimitere primește oricum categoriile produselor din listă. */
			if (cat && cat.tagName === 'SELECT' && cat.__pvlReqOrig === undefined) {
				if (cat.__pvlReq === undefined) cat.__pvlReq = cat.required;
				cat.required = list.length ? false : cat.__pvlReq;
				if (list.length) cat.removeAttribute('aria-required'); else if (cat.__pvlReq) cat.setAttribute('aria-required', 'true');
			}
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
				/* și cu o singură categorie: câmpul (acum opțional, folosit ca filtru) poate fi gol sau pe altă categorie */
				if (cats.length) {
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

		var api = { form: form, sync: function () { sync(); if (api.adderLabel) api.adderLabel(); }, before: function () { beforeSubmit(); pending = true; } };
		forms.push(api);

		/* lista se pune în formular: widgetul „Listă ofertă” (cu „Așază în formular”), altfel o listă automată */
		var widget = d.querySelector('[data-pvl-list][data-pvl-into-form]');
		var picker = C.formPicker !== false && !(widget && widget.getAttribute('data-pvl-picker') === '0');
		var anchor = cat && form.contains(cat) ? cat : prod;
		/* cu selectorul nou, lista + „Adaugă produs” stau sus, la începutul formularului */
		var node = anchor;
		while (node.parentElement && node.parentElement !== form) node = node.parentElement;
		if (picker && (!widget || widget.getAttribute('data-pvl-pos') !== 'fields')) {
			var firstField = form.querySelector('input:not([type="hidden"]), select, textarea');
			if (firstField) { node = firstField; while (node.parentElement && node.parentElement !== form) node = node.parentElement; }
		}
		var box = null;
		/* widgetul pus direct în formular (formularul V4 e un container): rămâne exact unde e */
		var inside = form.querySelector('[data-pvl-list]');
		if (inside) {
			box = inside;
			picker = C.formPicker !== false && inside.getAttribute('data-pvl-picker') !== '0';
		} else if (widget && !form.contains(widget)) {
			/* se mută tot widgetul Elementor, ca stilurile lui să rămână valabile */
			var wrap = widget.closest('.elementor-element') || widget;
			form.insertBefore(wrap, node);
			box = widget;
		} else if (!d.querySelector('[data-pvl-list]') && C.autoList !== false) {
			box = d.createElement('div');
			box.className = 'pvl-list pvl-list-auto is-empty';
			box.setAttribute('data-pvl-list', '');
			box.innerHTML = '<p class="pvl-title">' + esc(txt('listTitle', 'Produsele tale')) + '<span class="pvl-title-count"></span></p>' +
				'<ul class="pvl-items"></ul>' +
				'<p class="pvl-empty">' + esc(picker ? txt('listEmptyPick', 'Adaugă produsele care te interesează. Poți alege mai multe, fiecare cu culoarea și grosimea dorită.') : txt('listEmpty', 'Alege produsele dorite din lista de mai jos. Poți adăuga mai multe.')) + '</p>';
			form.insertBefore(box, node);
		}
		if (box) {
			/* titlul și textul încep la fel ca textul din câmpuri (padding-ul + chenarul câmpului) */
			box.classList.add('pvl-in-form');
			var cs = getComputedStyle(anchor);
			var pad = (parseFloat(cs.paddingLeft) || 0) + (parseFloat(cs.borderLeftWidth) || 0);
			if (box.getAttribute('data-pvl-align') !== '0') box.style.setProperty('--pvl-field-pad', pad + 'px');
		}
		if (picker && box) buildAdder(form, box, cat, prod, D, api);
		render();
	}

	/* câmpul cu tot învelișul lui (eticheta, săgeata listei), ca să-l putem ascunde */
	function fieldWrap(el, form) {
		var n = el;
		while (n.parentElement && n.parentElement !== form && n.parentElement.querySelectorAll('input:not([type="hidden"]), select, textarea').length === 1) n = n.parentElement;
		return n;
	}

	/* ---------- „Adaugă produs” în formular: categorie → produs (cu poză) → culoare → grosime ---------- */
	function buildAdder(form, box, cat, prod, D, api) {
		var prods = (D && D.prods) || [], cats = (D && D.cats) || [];
		if (!prods.length) return;
		/* listele vechi „Categorie produs” / „Produs solicitat” rămân în formular (duc datele), dar nu se mai văd */
		[cat, prod].forEach(function (el) {
			if (!el) return;
			el.__pvlReqOrig = el.__pvlReq !== undefined ? el.__pvlReq : el.required;
			el.required = false; el.removeAttribute('aria-required');
			el.tabIndex = -1;
			fieldWrap(el, form).classList.add('pvl-field-hidden');
		});
		api.picker = true;
		var uid = 'pvl-adder-' + Math.random().toString(36).slice(2, 8);
		var ad = d.createElement('div');
		ad.className = 'pvl-adder';
		ad.innerHTML =
			'<button type="button" class="pvl-add-btn" aria-expanded="false" aria-controls="' + uid + '"><span class="pvl-add-plus" aria-hidden="true">+</span><span class="pvl-add-t"></span></button>' +
			'<div class="pvl-adder-panel" id="' + uid + '" hidden>' +
				'<div class="pvl-adder-head"><p class="pvl-adder-title">' + esc(txt('pickTitle', 'Alege produsul')) + '</p>' +
				'<button type="button" class="pvl-adder-x" aria-label="' + esc(txt('close', 'Închide')) + '">' + X + '</button></div>' +
				'<p class="pvl-step">' + esc(txt('step1', 'Categorie')) + '</p><div class="pvl-chips" role="group" aria-label="' + esc(txt('step1', 'Categorie')) + '"></div>' +
				'<p class="pvl-step">' + esc(txt('step2', 'Produs')) + '</p><div class="pvl-tiles" data-lenis-prevent></div>' +
				'<div class="pvl-adder-var" hidden></div>' +
			'</div>' +
			'<p class="pvl-adder-err" role="alert" hidden>' + esc(txt('needOne', 'Adaugă cel puțin un produs în ofertă.')) + '</p>';
		box.appendChild(ad);
		var btn = ad.querySelector('.pvl-add-btn'), panel = ad.querySelector('.pvl-adder-panel'), chips = ad.querySelector('.pvl-chips'),
			tiles = ad.querySelector('.pvl-tiles'), vbox = ad.querySelector('.pvl-adder-var'), err = ad.querySelector('.pvl-adder-err');
		var curCat = '';
		var label = function () { btn.querySelector('.pvl-add-t').textContent = read().length ? txt('addMore', 'Adaugă alt produs') : txt('addFirst', 'Adaugă produs'); };
		var chipsHTML = '<button type="button" class="pvl-chip is-on" aria-pressed="true" data-pvl-cat="">' + esc(txt('all', 'Toate')) + '</button>';
		cats.forEach(function (c) { if (!c.depth) chipsHTML += '<button type="button" class="pvl-chip" aria-pressed="false" data-pvl-cat="' + esc(c.name) + '">' + esc(c.name) + '</button>'; });
		chips.innerHTML = chipsHTML;
		/* ordinea categoriilor din site (Pavele Premium → … → Bolțari): „Toate” arată produsele grupate așa */
		var catOrder = {};
		cats.forEach(function (c, i) { if (catOrder[norm(c.name)] === undefined) catOrder[norm(c.name)] = i; });
		var rank = function (p) {
			var r = catOrder[norm(p.cat)];
			if (r === undefined) (p.cats || []).forEach(function (n) { var x = catOrder[norm(n)]; if (x !== undefined && (r === undefined || x < r)) r = x; });
			return r === undefined ? 9999 : r;
		};
		var ordered = prods.map(function (p, i) { return { p: p, i: i, r: rank(p) }; })
			.sort(function (a, b) { return a.r - b.r || a.i - b.i; });
		function tile(x) {
			return '<button type="button" class="pvl-tile" data-pvl-i="' + x.i + '">' +
				'<span class="pvl-tile-img">' + (x.p.img ? '<img src="' + esc(x.p.img) + '" alt="" loading="lazy" decoding="async">' : '') + '</span>' +
				'<span class="pvl-tile-n">' + esc(x.p.name) + '</span></button>';
		}
		function drawTiles() {
			var h = '', last = null;
			ordered.forEach(function (x) {
				if (curCat && !(x.p.cats || []).some(function (n) { return norm(n) === norm(curCat); })) return;
				/* la „Toate”: titlul categoriei înaintea fiecărui grup */
				if (!curCat && x.r !== last) {
					last = x.r;
					var name = x.r < cats.length ? cats[x.r].name : txt('other', 'Alte produse');
					h += '<p class="pvl-tiles-cat">' + esc(name) + '</p>';
				}
				h += tile(x);
			});
			tiles.innerHTML = h || '<p class="pvl-none">' + esc(txt('none', 'Nu sunt produse în această categorie.')) + '</p>';
		}
		/* unde era butonul „Adaugă produs” pe ecran când s-a deschis panoul (după adăugare revenim acolo) */
		var anchorTop = null;
		function openPanel(on) {
			if (on && panel.hidden) anchorTop = btn.getBoundingClientRect().top;
			panel.hidden = !on;
			btn.setAttribute('aria-expanded', on ? 'true' : 'false');
			ad.classList.toggle('is-open', on);
			if (on) { vbox.hidden = true; tiles.hidden = false; drawTiles(); }
		}
		function chooseProduct(p) {
			var it = clean(p);
			if (!hasOpts(it)) { finish(it); return; }
			tiles.hidden = true;
			vbox.hidden = false;
			vbox.innerHTML = '<div class="pvl-av-head">' + (it.img ? '<img src="' + esc(it.img) + '" alt="">' : '') + '<div><p class="pvl-av-name">' + esc(it.name) + '</p>' + (it.cat ? '<p class="pvl-av-cat">' + esc(it.cat) + '</p>' : '') + '</div></div>' +
				pickerBody(it, null) +
				'<div class="pvl-av-foot"><button type="button" class="pvl-av-ok">' + esc(txt('addThis', 'Adaugă produsul')) + '</button><button type="button" class="pvl-av-back">' + esc(txt('back', 'Înapoi la produse')) + '</button></div>';
			if (!vbox.__bound) { bindChoices(vbox); vbox.__bound = true; }
			vbox.__it = it;
			var f = vbox.querySelector('.pvl-pk-color, .pvl-pk-t, .pvl-av-ok'); if (f) f.focus({ preventScroll: true });
			vbox.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
		}
		function finish(v) {
			add(v);
			announce(txt('added', 'Adăugat în ofertă') + ': ' + v.name + (varText(v) ? ', ' + varText(v) : ''));
			err.hidden = true;
			openPanel(false);
			label();
			restoreView();
			btn.focus({ preventScroll: true });
		}
		/* pe telefon panoul e lung: după închidere pagina se scurtează și lista rămâne deasupra ecranului.
		   Dacă butonul nu se mai vede, îl readucem unde era când s-a apăsat „Adaugă produs” (pe desktop nu se schimbă nimic). */
		function restoreView() {
			var r = btn.getBoundingClientRect(), vh = w.innerHeight || d.documentElement.clientHeight;
			if (r.top >= 0 && r.bottom <= vh) return;
			var to = anchorTop !== null && anchorTop >= 0 && anchorTop + r.height <= vh ? anchorTop : Math.max(16, (vh - r.height) / 2);
			w.scrollTo(0, Math.max(0, w.pageYOffset + r.top - to));
		}
		btn.addEventListener('click', function () { openPanel(panel.hidden); });
		ad.querySelector('.pvl-adder-x').addEventListener('click', function () { openPanel(false); btn.focus({ preventScroll: true }); });
		panel.addEventListener('keydown', function (e) { if (e.key === 'Escape') { e.preventDefault(); openPanel(false); btn.focus({ preventScroll: true }); } });
		chips.addEventListener('click', function (e) {
			var c = e.target.closest('[data-pvl-cat]'); if (!c) return;
			curCat = c.getAttribute('data-pvl-cat');
			Array.prototype.forEach.call(chips.children, function (x) { var on = x === c; x.classList.toggle('is-on', on); x.setAttribute('aria-pressed', on ? 'true' : 'false'); });
			vbox.hidden = true; tiles.hidden = false; drawTiles();
		});
		tiles.addEventListener('click', function (e) { var t = e.target.closest('[data-pvl-i]'); if (t) chooseProduct(prods[+t.getAttribute('data-pvl-i')]); });
		vbox.addEventListener('click', function (e) {
			if (e.target.closest('.pvl-av-ok')) { e.preventDefault(); if (pickerValid(vbox)) finish(chosen(vbox, vbox.__it)); }
			else if (e.target.closest('.pvl-av-back')) { e.preventDefault(); vbox.hidden = true; tiles.hidden = false; var t = tiles.querySelector('.pvl-tile'); if (t) t.focus(); }
		});
		label();
		api.adderLabel = label;
		/* fără produse, formularul nu pleacă: mesaj lângă „Adaugă produs” */
		api.validate = function () {
			var need = read().filter(needsChoice)[0];
			if (need) {
				err.textContent = txt('needVar', 'Alege culoarea și grosimea pentru') + ': ' + need.name + '.';
				err.hidden = false;
				var eb = box.querySelector('[data-pvl-edit="' + keyOf(need).replace(/"/g, '\\"') + '"]');
				(eb || btn).scrollIntoView({ behavior: 'smooth', block: 'center' });
				(eb || btn).focus({ preventScroll: true });
				return false;
			}
			err.textContent = txt('needOne', 'Adaugă cel puțin un produs în ofertă.');
			if (read().length) return true;
			err.hidden = false;
			openPanel(true);
			btn.scrollIntoView({ behavior: 'smooth', block: 'center' });
			btn.focus({ preventScroll: true });
			return false;
		};
	}

	/* capturăm trimiterea înaintea scriptului formularului (Elementor citește câmpurile în același moment) */
	d.addEventListener('submit', function (e) {
		forms.forEach(function (f) {
			if (f.form !== e.target) return;
			if (f.validate && !f.validate()) { e.preventDefault(); e.stopImmediatePropagation(); return; }
			f.before();
		});
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

	w.PavvaLista = { get: read, add: add, remove: remove, has: has, clear: clear, count: function () { return read().length; }, open: function () { openDrawer(); }, close: function () { closeDrawer(); } };
})(window, document);
