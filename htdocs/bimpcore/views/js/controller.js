function loadTabContent(url, tab_name) {
    var $container = $('#controllerTabContentContainer');
    var $tabs = $('.tabs');
    $tabs.find('.tabactive').each(function () {
        $(this).removeClass('tabactive').addClass('tabunactive');
    });

    $tabs.find('#' + tab_name).removeClass('tabunactive').addClass('tabactive');

    if ($container.length) {
        $container.fadeOut(250, function () {
            var $content = $(this).find('#controllerTabContent');
            $content.html('').hide();
            $(this).find('.content-loading').show();
            $(this).show();

            var data = {
                ajaxRequestUrl: url
            };

            bimp_json_ajax('loadControllerTab', data, $content, function (result) {
                if (typeof (result.html) !== 'undefined') {
                    $container.find('.content-loading').hide();
                    $content.html(result.html).fadeIn(250);
                    $('body').trigger($.Event('controllerTabLoaded', {
                        $container: $container,
                        tab_name: tab_name
                    }));
                }
            }, function (result) {
                $(this).find('.content-loading').hide();
            }, false);
        });
    }
}