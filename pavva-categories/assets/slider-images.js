/*
 * Pavva Widgets – câmpul „Imagini slider header” (admin)
 * Creat de imagient pentru Pavva.
 */
jQuery(function ($) {
	'use strict';

	function thumbUrl(att) {
		return att.sizes && att.sizes.thumbnail ? att.sizes.thumbnail.url : att.url;
	}

	/* numele câmpurilor urmează ordinea din listă */
	function reindex($box) {
		var name = $box.data('name');
		$box.find('.pvsi-item').each(function (i) {
			$(this).find('.pvsi-id').attr('name', name + '[' + i + '][id]');
			$(this).find('.pvsi-mobile').attr('name', name + '[' + i + '][mobile]');
		});
	}

	function init($box) {
		if ($box.data('pvsiReady')) return;
		$box.data('pvsiReady', true);
		$box.find('.pvsi-list').sortable({
			items: '.pvsi-item',
			tolerance: 'pointer',
			placeholder: 'pvsi-placeholder',
			update: function () { reindex($box); }
		});
	}

	$('.pvsi').each(function () { init($(this)); });

	$(document).on('click', '.pvsi-add', function (e) {
		e.preventDefault();
		var $box = $(this).closest('.pvsi');
		init($box);
		var frame = wp.media({
			title: 'Imagini slider header',
			button: { text: 'Adaugă în slider' },
			multiple: 'add',
			library: { type: 'image' }
		});
		frame.on('select', function () {
			var tpl = $box.find('.pvsi-template').html();
			frame.state().get('selection').each(function (model) {
				var att = model.toJSON();
				var $row = $(tpl.replace(/\{\{INDEX\}\}/g, '0'));
				$row.find('.pvsi-id').val(att.id);
				$row.find('.pvsi-main').html('<img src="' + thumbUrl(att) + '" alt="">');
				$box.find('.pvsi-list').append($row);
			});
			reindex($box);
		});
		frame.open();
	});

	$(document).on('click', '.pvsi-remove', function (e) {
		e.preventDefault();
		var $box = $(this).closest('.pvsi');
		$(this).closest('.pvsi-item').remove();
		reindex($box);
	});

	$(document).on('click', '.pvsi-pick-mobile', function (e) {
		e.preventDefault();
		var $item = $(this).closest('.pvsi-item');
		var frame = wp.media({
			title: 'Imagine pentru mobil (ideal verticală)',
			button: { text: 'Folosește imaginea' },
			multiple: false,
			library: { type: 'image' }
		});
		frame.on('select', function () {
			var att = frame.state().get('selection').first().toJSON();
			$item.find('.pvsi-mobile').val(att.id);
			$item.find('.pvsi-mob').addClass('has-image');
			$item.find('.pvsi-mob-thumb').html('<img src="' + thumbUrl(att) + '" alt="">');
			$item.find('.pvsi-pick-mobile').text('Schimbă mobil');
			$item.find('.pvsi-remove-mobile').prop('hidden', false);
		});
		frame.open();
	});

	$(document).on('click', '.pvsi-remove-mobile', function (e) {
		e.preventDefault();
		var $item = $(this).closest('.pvsi-item');
		$item.find('.pvsi-mobile').val('');
		$item.find('.pvsi-mob').removeClass('has-image');
		$item.find('.pvsi-mob-thumb').empty();
		$item.find('.pvsi-pick-mobile').text('+ Imagine mobil');
		$(this).prop('hidden', true);
	});

	/* categorie nouă (formular AJAX): lista se golește după salvare */
	$(document).ajaxComplete(function (event, xhr, settings) {
		if (settings && typeof settings.data === 'string' && settings.data.indexOf('action=add-tag') !== -1) {
			$('#addtag .pvsi-list').empty();
		}
	});
});
