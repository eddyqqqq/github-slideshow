/*!
 * Pavva – accesibilitate pentru cititoare de ecran și agenți AI
 * (Setări → Pavva – Viteză → „Accesibilitate automată”)
 *
 * 1. Zona principală: primul document Elementor de conținut (nu header / footer / popup) devine
 *    role="main", cu id="content" pentru link-ul „Sari la conținut” al temei.
 * 2. Eticheta pusă din Elementor (Advanced → Attributes → aria-label) ajunge pe învelișul widgetului,
 *    unde e interzisă: o mutăm pe link-ul din el.
 * 3. Link-urile fără text (iconițe, logo, carduri cu imagine) primesc un nume: rețeaua socială,
 *    numele site-ului pentru prima pagină sau numele paginii din adresă.
 * 4. Meniul Elementor pune aria-expanded pe link-uri, unde nu e permis: îl scoatem.
 */
(function (w, d) {
	'use strict';
	var SITE = (w.PavvaA11y && w.PavvaA11y.site) || d.title.split(/\s[–|-]\s/).pop() || '';
	var SOCIAL = [
		['facebook.', 'Facebook'], ['instagram.', 'Instagram'], ['tiktok.', 'TikTok'], ['youtube.', 'YouTube'],
		['youtu.be', 'YouTube'], ['linkedin.', 'LinkedIn'], ['pinterest.', 'Pinterest'], ['twitter.', 'X'],
		['x.com', 'X'], ['wa.me', 'WhatsApp'], ['whatsapp.', 'WhatsApp'], ['t.me', 'Telegram'], ['maps.google', 'Google Maps'],
		['google.com/maps', 'Google Maps'], ['goo.gl/maps', 'Google Maps']
	];

	/* 1. zona principală */
	function main() {
		if (d.querySelector('main, [role="main"]')) return;
		var docs = d.querySelectorAll('[data-elementor-type]');
		for (var i = 0; i < docs.length; i++) {
			var e = docs[i], t = e.getAttribute('data-elementor-type');
			if (/^(header|footer|popup|loop-item|kit)$/.test(t) || (e.parentElement && e.parentElement.closest('[data-elementor-type]'))) continue;
			e.setAttribute('role', 'main');
			if (!d.getElementById('content')) e.id = 'content';
			e.setAttribute('tabindex', '-1');
			e.style.outline = 'none';
			return;
		}
	}

	function hasName(a) {
		if (a.getAttribute('aria-label') || a.getAttribute('aria-labelledby') || a.getAttribute('title')) return true;
		if ((a.textContent || '').replace(/\s+/g, '')) return true;
		var imgs = a.querySelectorAll('img[alt], [aria-label], svg title');
		for (var i = 0; i < imgs.length; i++) {
			var v = imgs[i].tagName === 'title' ? imgs[i].textContent : (imgs[i].getAttribute('alt') || imgs[i].getAttribute('aria-label'));
			if (v && v.trim()) return true;
		}
		return false;
	}

	function nameFor(a) {
		var href = a.getAttribute('href') || '';
		if (/^tel:/i.test(href)) return 'Sună la ' + decodeURIComponent(href.slice(4));
		if (/^mailto:/i.test(href)) return 'Scrie la ' + decodeURIComponent(href.slice(7).split('?')[0]);
		var low = (a.href || '').toLowerCase();
		for (var i = 0; i < SOCIAL.length; i++) if (low.indexOf(SOCIAL[i][0]) !== -1) return SOCIAL[i][1];
		if (a.hostname && a.hostname !== location.hostname) return 'Deschide ' + a.hostname.replace(/^www\./, '');
		var seg = (a.pathname || '').replace(/\/+$/, '').split('/').pop();
		if (!seg) return SITE ? SITE + ' – prima pagină' : 'Prima pagină';
		seg = decodeURIComponent(seg).replace(/[-_]+/g, ' ').trim();
		return seg.charAt(0).toUpperCase() + seg.slice(1);
	}

	/* 2 + 3. etichete */
	function labels(root) {
		Array.prototype.forEach.call(root.querySelectorAll('.elementor-element[aria-label]:not([role])'), function (wrap) {
			var a = wrap.querySelector('a[href]');
			if (a && !a.getAttribute('aria-label') && !(a.textContent || '').trim()) a.setAttribute('aria-label', wrap.getAttribute('aria-label'));
			if (a) wrap.removeAttribute('aria-label');
		});
		Array.prototype.forEach.call(root.querySelectorAll('a[href]'), function (a) {
			if (!hasName(a)) a.setAttribute('aria-label', nameFor(a));
		});
	}

	/* 4. meniul Elementor (SmartMenus): aria-expanded nu e permis pe link */
	function menuFix(el) {
		if (el.tagName === 'A' && !el.getAttribute('role') && el.hasAttribute('aria-expanded') && el.closest('.elementor-nav-menu, .elementor-nav-menu--main, .elementor-nav-menu--dropdown')) {
			el.removeAttribute('aria-expanded');
		}
	}
	function menus() {
		Array.prototype.forEach.call(d.querySelectorAll('.elementor-nav-menu a[aria-expanded]'), menuFix);
	}

	function run() { main(); labels(d); menus(); }
	run();
	d.addEventListener('DOMContentLoaded', run);
	w.addEventListener('load', run);
	if (w.MutationObserver) {
		new MutationObserver(function (list) {
			list.forEach(function (m) { if (m.type === 'attributes') menuFix(m.target); });
		}).observe(d.documentElement, { attributes: true, subtree: true, attributeFilter: ['aria-expanded'] });
	}
})(window, document);
