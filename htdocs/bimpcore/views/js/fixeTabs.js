$(document).ready(function () {
    $('#bimp_fixe_tabs_captions').find('.fixe_tab_caption').each(function () {
        $(this).mouseenter(function () {
            $('#bimp_fixe_tabs_captions').find('.fixe_tab_caption').removeClass('active');
            $(this).addClass('active');
            $('#bimp_fixe_tabs_contents').find('.fixe_tab_content').hide();
            var id = $(this).data('id_tab');
            var $content = $('#fixe_tab_content_' + id);
            if ($content.length) {
                $content.show();
            }
            $('#bimp_fixe_tabs_contents').stop().slideDown(250);
        });
    });
    $('#bimp_fixe_tabs').mouseleave(function () {
        $(this).find('.fixe_tab_caption').removeClass('active');
        $('#bimp_fixe_tabs_contents').stop().slideUp(250, function () {
            $(this).find('.fixe_tab_content').hide();
        });
    });
});