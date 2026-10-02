jQuery(function($){
    'use strict';

    $(document).on('click', '.pavva-category-select-image', function(e){
        e.preventDefault();

        const $wrap = $(this).closest('.pavva-category-image-field');
        const frame = wp.media({
            title: 'Selectează imaginea hero',
            button: { text: 'Folosește imaginea' },
            multiple: false,
            library: { type: 'image' }
        });

        frame.on('select', function(){
            const attachment = frame.state().get('selection').first().toJSON();
            $wrap.find('.pavva-category-image-id').val(attachment.id);
            $wrap.find('.pavva-category-image-preview').html(
                '<img src="' + attachment.url + '" alt="" style="max-width:260px;height:auto;display:block">'
            );
            $wrap.find('.pavva-category-remove-image').show();
        });

        frame.open();
    });

    $(document).on('click', '.pavva-category-remove-image', function(e){
        e.preventDefault();
        const $wrap = $(this).closest('.pavva-category-image-field');
        $wrap.find('.pavva-category-image-id').val('');
        $wrap.find('.pavva-category-image-preview').empty();
        $(this).hide();
    });
});
