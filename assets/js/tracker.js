jQuery(document).ready(function($) {
    $('.affiliate-button').on('click', function() {
        const $btn = $(this);
        const text = $btn.text().trim();
        const link = $btn.attr('href');
        let platform = 'general';

        try {
            // Create a URL object to extract the hostname
            const url = new URL(link, window.location.href);
            let host = url.hostname.replace(/^www\./i, ''); // Remove www.
            const parts = host.split('.');
            // Keep the last two segments (e.g., example.com or co.uk)
            platform = (parts.length > 2)
                ? parts.slice(-2).join('.')
                : host;
        } catch (e) {
            // If parsing fails, default to 'general'
            platform = 'general';
        }

        $.post(PlinkLyTracker.ajax_url, {
            action:   'plinkly_track_click',
            post_id:  PlinkLyTracker.post_id,
            nonce:    PlinkLyTracker.nonce,
            text:     text,
            link:     link,
            platform: platform
        });
    });
});
