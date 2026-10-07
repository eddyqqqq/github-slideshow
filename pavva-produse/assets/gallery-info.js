/*
 * Pavva Products – informații la imaginile din galeria WooCommerce (admin)
 * Creat de imagient pentru Pavva.
 *
 * Imaginile deja salvate primesc informațiile din PHP. Aici le completăm doar
 * pentru imaginile adăugate acum (înainte de salvare), citind ID-ul pus de
 * WooCommerce pe fiecare imagine (data-attachment_id). Selectorul wp.media,
 * reordonarea și eliminarea rămân ale WooCommerce, neatinse.
 */
jQuery(function ($) {
	'use strict';

	var C = window.PavvaGalleryInfo || {};
	var list = document.querySelector('#product_images_container ul.product_images');
	if (!list || !window.MutationObserver) return;

	function basename(url) {
		return String(url || '').split('?')[0].split('#')[0].split('/').pop();
	}

	function linkFor(id, data) {
		if (C.link) return C.link.split(C.token).join(String(id));
		if (C.useEdit && data && data.editLink) return data.editLink;
		return '';
	}

	function render($box, id, data) {
		$box.empty().removeClass('is-missing has-note is-loading');

		if (!data) {
			$box.addClass('is-missing')
				.append($('<span class="pvp-ginfo-name"/>').text(C.missing || ''))
				.append($('<span class="pvp-ginfo-meta"/>').text(C.noAtt || ''));
			return;
		}

		var name = data.filename || basename(data.url) || ('#' + id);
		var note = data.type && 'image' !== data.type ? (C.notImg || '') : '';
		var $meta = $('<span class="pvp-ginfo-meta"/>').append($('<span class="pvp-ginfo-id"/>').text('ID: ' + id));

		if (note) {
			$box.addClass('has-note');
			$meta.append($('<span class="pvp-ginfo-note dashicons dashicons-warning"/>').attr({ title: note, 'aria-label': note }));
		}

		var href = linkFor(id, data);
		if (href) {
			$meta.append(
				$('<a class="pvp-ginfo-link" target="_blank" rel="noopener"/>')
					.attr({ href: href, title: C.title || '', 'aria-label': (C.title || '') + ': ' + name })
					.append('<span class="dashicons dashicons-external" aria-hidden="true"></span>')
			);
		}

		$box.append($('<span class="pvp-ginfo-name"/>').text(name).attr('title', name))
			.append($meta);
	}

	function enhance(li) {
		if (!li || li.nodeType !== 1 || !li.matches('li.image') || li.querySelector('.pvp-ginfo')) return;
		var id = parseInt(li.getAttribute('data-attachment_id'), 10);
		if (!id) return;

		var $box = $('<div class="pvp-ginfo is-loading"/>').attr('data-pvp-id', id)
			.append($('<span class="pvp-ginfo-name"/>').text('…'))
			.append($('<span class="pvp-ginfo-meta"/>').text('ID: ' + id));
		$(li).append($box);

		var model = window.wp && wp.media && wp.media.attachment ? wp.media.attachment(id) : null;
		if (!model) { render($box, id, { id: id }); return; }

		/* imaginea tocmai aleasă e deja încărcată de wp.media; altfel o cerem o dată */
		if (model.get('filename') || model.get('url')) {
			render($box, id, model.toJSON());
			return;
		}
		model.fetch()
			.done(function () { render($box, id, model.toJSON()); })
			.fail(function () { render($box, id, null); });
	}

	new MutationObserver(function (mutations) {
		mutations.forEach(function (m) {
			Array.prototype.forEach.call(m.addedNodes, enhance);
		});
	}).observe(list, { childList: true });

	/* siguranță: imaginile care au apărut înainte de pornirea scriptului */
	Array.prototype.forEach.call(list.querySelectorAll('li.image'), enhance);
});
