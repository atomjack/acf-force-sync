(function ($) {
    $(document).on('click', 'span.force-sync a', (e) => {
        e.preventDefault();
        const $link = $(e.target);
        $.ajax({
            url: ajaxurl,
            dataType: 'json',
            data: {
                'action': 'acf_force_sync',
                'post': $link.data('post')
            },
            success: (result) => {
                if (result.success) {
                    let old_title = $link.text();
                    $link.text('Synced!');
                    setTimeout(() => {
                        $link.text(old_title);
                    }, 2000);
                } else {

                }
            }
        })
    });
})(jQuery);