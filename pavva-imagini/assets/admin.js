/* Pavva Imagini – conversia în masă (câte o imagine pe rând, ca serverul să nu se blocheze) */
(function () {
	'use strict';
	var $ = function (id) { return document.getElementById(id); };
	var stop = false, running = false;

	function post(action, data) {
		var body = new FormData();
		body.append('action', 'pvi_' + action);
		body.append('nonce', PVI.nonce);
		Object.keys(data || {}).forEach(function (k) { body.append(k, data[k]); });
		return fetch(PVI.ajax, { method: 'POST', body: body, credentials: 'same-origin' })
			.then(function (r) { return r.json(); })
			.then(function (j) { if (!j || !j.success) throw new Error((j && j.data && j.data.msg) || 'Eroare server'); return j.data; });
	}
	function mb(b) { return (b / 1048576).toFixed(1).replace('.', ',') + ' MB'; }
	function kb(b) { return Math.round(b / 1024) + ' KB'; }
	function log(text, cls) {
		var li = document.createElement('li');
		li.textContent = text;
		if (cls) li.className = cls;
		var ol = $('pvi-log');
		ol.insertBefore(li, ol.firstChild);
		while (ol.children.length > 200) ol.removeChild(ol.lastChild);
	}
	function busy(on) {
		running = on;
		$('pvi-run').disabled = on;
		$('pvi-run-all').disabled = on;
		$('pvi-stop').hidden = !on;
	}

	function run(all) {
		if (running) return;
		if (all && !window.confirm('Refaci toate variantele AVIF / WebP? Durează mai mult decât prima conversie.')) return;
		stop = false;
		busy(true);
		$('pvi-progress').hidden = false;
		$('pvi-log').innerHTML = '';
		$('pvi-status').textContent = 'Caut imaginile…';
		$('pvi-bar').style.width = '0%';
		var tot = { orig: 0, webp: 0, best: 0, errors: 0 }, t0 = Date.now();

		post('ids', { all: all ? 1 : 0 }).then(function (d) {
			var ids = d.ids, i = 0, pass = 0;
			if (!ids.length) { $('pvi-status').textContent = 'Toate imaginile sunt deja convertite.'; busy(false); return; }
			var next = function () {
				if (stop || i >= ids.length) {
					var saved = tot.orig ? Math.round(100 - tot.best * 100 / tot.orig) : 0;
					$('pvi-status').textContent = (stop ? 'Oprit' : 'Gata') + ': ' + i + ' imagini, ' + mb(tot.orig) + ' → ' + mb(tot.best) + ' (−' + saved + '%)' + (tot.errors ? ', ' + tot.errors + ' fișiere cu eroare' : '') + '. Reîncarcă pagina pentru totalul actualizat.';
					busy(false);
					return;
				}
				var id = ids[i];
				post('convert', { id: id, force: all ? 1 : 0, pass: pass }).then(function (r) {
					/* imagine mare: serverul a lucrat cât îi permite limita; continuăm aceeași imagine */
					if (r.more && pass < 30) { pass++; $('pvi-status').textContent = r.name + ': continui conversia (imagine mare)…'; next(); return 'again'; }
					pass = 0;
					tot.orig += r.orig; tot.webp += r.webp; tot.best += r.best; tot.errors += r.errors;
					log(r.name + ': ' + kb(r.orig) + ' → ' + kb(r.best) + (r.errors ? ' (' + r.errors + ' erori)' : ''), r.errors ? 'pvi-err' : '');
				}).catch(function (e) {
					pass = 0;
					tot.errors++;
					log('Imaginea #' + id + ': ' + e.message, 'pvi-err');
				}).then(function (again) {
					if (again === 'again') return;
					i++;
					var pct = Math.round(i * 100 / ids.length);
					$('pvi-bar').style.width = pct + '%';
					var left = (Date.now() - t0) / i * (ids.length - i) / 1000;
					$('pvi-status').textContent = i + ' / ' + ids.length + ' (' + pct + '%)' + (i < ids.length ? ' · mai durează ~' + (left > 90 ? Math.round(left / 60) + ' min' : Math.round(left) + ' s') : '');
					next();
				});
			};
			next();
		}).catch(function (e) { $('pvi-status').textContent = e.message; busy(false); });
	}

	$('pvi-run').addEventListener('click', function () { run(false); });
	$('pvi-run-all').addEventListener('click', function () { run(true); });
	$('pvi-stop').addEventListener('click', function () { stop = true; $('pvi-status').textContent = 'Se oprește după imaginea curentă…'; });

	$('pvi-test').addEventListener('click', function () {
		var out = $('pvi-test-out');
		out.hidden = false;
		out.className = 'pvi-test-out';
		out.textContent = 'Testez…';
		post('test').then(function (r) {
			out.textContent = r.msg;
			out.className = 'pvi-test-out ' + (r.ok ? 'is-ok' : 'is-no');
		}).catch(function (e) { out.textContent = e.message; out.className = 'pvi-test-out is-no'; });
	});

	$('pvi-delete').addEventListener('click', function () {
		if (!window.confirm('Ștergi toate variantele AVIF / WebP? Originalele rămân.')) return;
		var b = this;
		b.disabled = true;
		post('delete_all').then(function (r) { b.textContent = 'Șterse (' + r.count + ' imagini). Reîncarcă pagina.'; })
			.catch(function (e) { b.textContent = e.message; b.disabled = false; });
	});
})();
