jQuery(function($){
    'use strict';

    $('.pavva-repeater-list').sortable({
        handle: '.pavva-handle',
        items: '.pavva-row',
        placeholder: 'pavva-sort-placeholder',
        forcePlaceholderSize: true
    });

    $(document).on('click', '.pavva-add-row', function(){
        const $rep = $(this).closest('.pavva-repeater');
        const tpl = $rep.find('.pavva-row-template').html();
        const index = Date.now().toString() + Math.floor(Math.random()*1000);
        $rep.find('.pavva-repeater-list').append(tpl.replaceAll('{{INDEX}}', index));
    });

    $(document).on('click', '.pavva-delete-row', function(){
        if (window.confirm(PavvaAdmin.confirmDelete)) $(this).closest('.pavva-row').remove();
    });

    $(document).on('click', '.pavva-select-media', function(e){
        e.preventDefault();
        const $media = $(this).closest('.pavva-media');
        const type = $media.data('type');
        const isFile = type === 'file';

        const frame = wp.media({
            title: isFile ? PavvaAdmin.mediaFileTitle : PavvaAdmin.mediaImageTitle,
            button: { text: PavvaAdmin.mediaButton },
            library: isFile ? { type: 'application/pdf' } : { type: 'image' },
            multiple: false
        });

        frame.on('select', function(){
            const a = frame.state().get('selection').first().toJSON();
            $media.find('.pavva-media-id').val(a.id);

            if (isFile) {
                $media.find('.pavva-file-preview').html('<span>'+ (a.filename || a.title) +'</span>');
            } else {
                let url = a.url;
                if (a.sizes && a.sizes.medium) url = a.sizes.medium.url;
                $media.find('.pavva-media-preview').html('<img src="'+url+'" alt="">');
            }
            $media.find('.pavva-remove-media').show();
        });

        frame.open();
    });

    $(document).on('click', '.pavva-remove-media', function(e){
        e.preventDefault();
        const $media = $(this).closest('.pavva-media');
        $media.find('.pavva-media-id').val('');
        if ($media.data('type') === 'file') {
            $media.find('.pavva-file-preview').html('<span>Niciun fișier</span>');
        } else {
            $media.find('.pavva-media-preview').html('<span>Nicio imagine</span>');
        }
        $(this).hide();
    });
});


jQuery(function($){
    'use strict';

    function pavvaSyncDimensionLabel($source){
        const key = $source.data('pavva-label-source');
        const value = $.trim($source.val());
        if(!key || !value) return;
        $('[data-pavva-label-key="' + key + '"]').text(value);
    }

    $(document).on('input change','[data-pavva-label-source]',function(){
        pavvaSyncDimensionLabel($(this));
    });

    $('[data-pavva-label-source]').each(function(){
        pavvaSyncDimensionLabel($(this));
    });
});


jQuery(function($){
    'use strict';

    function initDimensionSpecSortable(scope){
        $(scope || document).find('.pavva-dimension-spec-list').each(function(){
            const $list = $(this);
            if($list.hasClass('ui-sortable')) return;

            $list.sortable({
                handle: '.pavva-spec-handle',
                items: '.pavva-dimension-spec-row',
                placeholder: 'pavva-spec-sort-placeholder',
                forcePlaceholderSize: true
            });
        });
    }

    initDimensionSpecSortable(document);

    $(document).on('click', '.pavva-add-dimension-row', function(){
        const $rep = $(this).closest('.pavva-dimensions-repeater');
        const tpl = $rep.find('.pavva-dimension-row-template').html();
        const index = 'd' + Date.now().toString() + Math.floor(Math.random()*1000);

        const html = tpl.replaceAll('{{DIM_INDEX}}', index);
        const $row = $(html);

        $rep.find('.pavva-dimension-list').append($row);
        initDimensionSpecSortable($row);
    });

    $(document).on('click', '.pavva-add-dimension-spec', function(){
        const $row = $(this).closest('.pavva-dimension-row');
        const $rep = $row.closest('.pavva-dimensions-repeater');
        const dimIndex = $row.attr('data-dim-index');
        const specIndex = 's' + Date.now().toString() + Math.floor(Math.random()*1000);
        const tpl = $rep.find('.pavva-dimension-spec-template').html();

        const html = tpl
            .replaceAll('{{DIM_INDEX}}', dimIndex)
            .replaceAll('{{SPEC_INDEX}}', specIndex);

        $row.find('.pavva-dimension-spec-list').append(html);
        initDimensionSpecSortable($row);
    });

    $(document).on('click', '.pavva-delete-dimension-spec', function(){
        $(this).closest('.pavva-dimension-spec-row').remove();
    });
});
