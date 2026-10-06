<?php
/**
 * Pavva Widgets – formularul de ofertă se completează singur
 *
 * Linkul „Solicită ofertă” (Dynamic Tag Pavva) duce la pagina formularului cu
 * ?produs=…&categorie=… . Pe acea pagină, scriptul de mai jos alege automat
 * categoria și produsul în câmpurile formularului (liste Select sau text),
 * inclusiv când lista de produse se încarcă abia după alegerea categoriei.
 * Merge cu orice plugin de formulare (Elementor, CF7, WPForms, Fluent etc.).
 * Scriptul se pune în pagină doar când adresa conține acești parametri.
 *
 * @package Pavva_Widgets
 * @author  imagient
 */

if ( ! defined( 'ABSPATH' ) ) exit;

final class Pavva_W_Quote_Prefill {

    public static function init() {
        add_action( 'wp_footer', array( __CLASS__, 'print_script' ), 99 );
    }

    /* Numele parametrilor din adresă (aceleași ca în setările linkului). */
    public static function params(): array {
        return (array) apply_filters( 'pavva_w_prefill_params', array( 'produs' => 'produs', 'categorie' => 'categorie' ) );
    }

    public static function print_script() {
        if ( is_admin() ) return;
        $p = self::params();
        if ( empty( $_GET[ $p['produs'] ] ) && empty( $_GET[ $p['categorie'] ] ) ) return; // phpcs:ignore WordPress.Security.NonceVerification -- doar citire
        ?>
<script id="pavva-oferta-prefill">
/* Pavva Widgets – completează produsul / categoria în formularul de ofertă */
(function () {
	'use strict';
	var P = <?php echo wp_json_encode( array( 'produs' => (string) $p['produs'], 'categorie' => (string) $p['categorie'] ) ); ?>;
	var q = new URLSearchParams(window.location.search);
	var want = { produs: q.get(P.produs) || '', categorie: q.get(P.categorie) || '' };
	if (!want.produs && !want.categorie) return;

	function norm(t) {
		return String(t || '').normalize('NFD').replace(/[̀-ͯ]/g, '')
			.toLowerCase().replace(/\s+/g, ' ').trim();
	}

	/* ce descrie câmpul: nume, id, placeholder, eticheta, prima opțiune */
	function hint(el) {
		var h = [el.name, el.id, el.getAttribute('placeholder'), el.getAttribute('aria-label')];
		if (el.id) {
			var lab = document.querySelector('label[for="' + el.id.replace(/"/g, '\\"') + '"]');
			if (lab) h.push(lab.textContent);
		}
		var grp = el.closest('.elementor-field-group, .wpforms-field, .wpcf7-form-control-wrap, .ff-el-group, .gfield, p, div');
		/* eticheta grupului, doar dacă grupul are un singur câmp (altfel ar fi eticheta altui câmp) */
		if (grp && grp.querySelectorAll('select, input:not([type="hidden"]), textarea').length === 1) {
			var l = grp.querySelector('label'); if (l) h.push(l.textContent);
		}
		if (el.tagName === 'SELECT' && el.options.length) h.push(el.options[0].textContent);
		return norm(h.join(' '));
	}

	function kind(el) {
		var h = hint(el);
		if (/categor/.test(h)) return 'categorie';
		if (/produs|product/.test(h)) return 'produs';
		return '';
	}

	function fire(el) {
		['input', 'change'].forEach(function (t) { el.dispatchEvent(new Event(t, { bubbles: true })); });
		if (window.jQuery) window.jQuery(el).trigger('change');
	}

	/* alege opțiunea potrivită; true dacă a reușit */
	function setField(el, value) {
		var v = norm(value);
		if (!v) return false;
		if (el.tagName === 'SELECT') {
			var best = null, bestScore = 0;
			Array.prototype.forEach.call(el.options, function (o, i) {
				if (!i && !o.value) return; /* „Alege…” */
				var t = norm(o.textContent), ov = norm(o.value), s = 0;
				if (t === v || ov === v) s = 3;
				else if (t.indexOf(v) === 0 || v.indexOf(t) === 0) s = 2;
				else if (t.indexOf(v) !== -1 || (t.length > 3 && v.indexOf(t) !== -1)) s = 1;
				if (s > bestScore) { best = o; bestScore = s; }
			});
			if (!best) return false;
			if (el.value !== best.value) { el.value = best.value; fire(el); }
			return true;
		}
		if (el.type === 'checkbox' || el.type === 'radio' || el.type === 'submit') return false;
		if (!el.value) { el.value = value; fire(el); }
		return true;
	}

	var done = { categorie: !want.categorie, produs: !want.produs };
	var tries = 0;

	function run() {
		tries++;
		var fields = document.querySelectorAll('form select, form input, form textarea');
		['categorie', 'produs'].forEach(function (k) {
			if (done[k]) return;
			if (k === 'produs' && !done.categorie && tries < 6) return; /* întâi categoria */
			Array.prototype.forEach.call(fields, function (el) {
				if (!done[k] && kind(el) === k && setField(el, want[k])) done[k] = true;
			});
		});
		/* lista de produse se poate încărca după categorie: mai încercăm câteva secunde */
		if ((!done.categorie || !done.produs) && tries < 40) setTimeout(run, 250);
	}

	if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', run);
	else run();
})();
</script>
        <?php
    }
}
