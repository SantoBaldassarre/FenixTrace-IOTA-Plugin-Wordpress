jQuery(function ($) {
    $(document).on('click', '.fenixtrace-sync-btn', function (e) {
        e.preventDefault();
        var btn = $(this);
        var postId = btn.data('post-id');
        btn.prop('disabled', true).text('Syncing...');

        $.post(fenixtrace.ajax_url, {
            action: 'fenixtrace_sync',
            post_id: postId,
            nonce: fenixtrace.nonce
        }, function (res) {
            if (res.success) {
                location.reload();
            } else {
                alert('FenixTrace Error: ' + (res.data || 'Unknown error'));
                btn.prop('disabled', false).text('Retry');
            }
        }).fail(function () {
            alert('FenixTrace: Connection failed');
            btn.prop('disabled', false).text('Retry');
        });
    });
});
