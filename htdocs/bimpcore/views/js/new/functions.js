// successCallBack : soit fonction callback en cas de succès, soit message par défaut en cas de succès
// errorCallBack : soit fonction callback en cas d'erreur, soit message par défaut en cas d'erreur

// Traitements Ajax:
var ajaxRequestsUrl = './index.php';
function bimp_json_ajax(action, data, $resultContainer, successCallBack, errorCallBack, display_processing, ajax_params) {
    var display_result_errors_only = false;
    if ((typeof (successCallBack) === 'string') ||
            typeof (successCallBack) === 'function') {
        display_result_errors_only = true;
    }

    if (typeof (display_processing) === 'undefined') {
        display_processing = true;
    }

    if (display_processing && $resultContainer && typeof ($resultContainer) !== 'undefined' && $resultContainer.length) {
        bimp_display_msg('Traitement en cours', $resultContainer, 'info');
    }

    var ajaxRequestUrl = '';
    if (typeof (data.ajaxRequestUrl) !== 'undefined') {
        ajaxRequestUrl = data.ajaxRequestUrl;
    } else {
        ajaxRequestUrl = ajaxRequestsUrl;
    }

    if (!/\?/.test(ajaxRequestUrl)) {
        ajaxRequestUrl += '?';
    } else {
        ajaxRequestUrl += '&';
    }
    ajaxRequestUrl += 'ajax=1&action=' + action;

    if (typeof (ajax_params) === 'undefined') {
        var ajax_params = {};
    }

    if (typeof (ajax_params.type) === 'undefined') {
        ajax_params.type = "POST";
    }

    if (typeof (ajax_params.url) === 'undefined') {
        ajax_params.url = ajaxRequestUrl;
    }
    if (typeof (ajax_params.dataType) === 'undefined') {
        ajax_params.dataType = 'json';
    }
    if (typeof (ajax_params.data) === 'undefined') {
        ajax_params.data = data;
    }
    if (typeof (ajax_params.success) === 'undefined') {
        ajax_params.success = function (result) {
            bimp_displayAjaxResult(result, $resultContainer, display_result_errors_only);
            if (!result.errors.length) {
                if (typeof (successCallBack) === 'function') {
                    successCallBack(result);
                } else if (typeof (successCallBack) === 'string') {
                    if (successCallBack) {
                        bimp_display_msg(successCallBack, $resultContainer, 'success');
                    }
                } else if ((typeof (result.success) === 'string') && result.success) {
                    bimp_display_msg(result.success, $resultContainer, 'success');
                }
            } else {
                if (typeof (errorCallBack) === 'function') {
                    errorCallBack(result);
                }
            }
        };
    }
    if (typeof (ajax_params.error) === 'undefined') {
        ajax_params.error = function () {
            if (typeof (errorCallBack) === 'string') {
                bimp_display_msg(errorCallBack, $resultContainer, 'danger');
            } else {
                bimp_display_msg('Une erreur est survenue. La requête n\'a pas aboutie', $resultContainer, 'danger');
            }
            if (typeof (errorCallBack) === 'function') {
                errorCallBack();
            }
        };
    }

    $.ajax(ajax_params);
}

function bimp_display_msg(msg, $container, className) {
    if (!$container || (typeof ($container) === 'undefined') || !$container.length) {
        bimp_show_msg(msg, className);
        return;
    }
    var html = '';
    if (typeof (className) !== 'undefined') {
        html += '<p class="alert alert-' + className + ' alert-dismissible">';
        html += '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>';
    } else {
        html += '<p>';
    }
    html += msg + '</p>';
    $container.html(html).slideDown(250);
}

function bimp_displayAjaxResult(data, $container, errors_only) {
    if (typeof (errors_only) === 'undefined') {
        errors_only = false;
    }
    if (!$container || (typeof ($container) === 'undefined') || !$container || !$container.length) {
        $container = false;
    } else {
        $container.hide().html('');
    }

    if (typeof (data) === 'undefined') {
        bimp_display_msg('Une erreur est survenue: aucune données reçues', $container, 'danger');
        return;
    }

    if (!bimp_display_result_errors(data, $container)) {
        if (!errors_only) {
            bimp_display_result_success(data, $container);
        }
    }
}

function bimp_display_result_errors(result, $container) {
    if (typeof (result.errors) !== 'undefined') {
        if (result.errors.length) {
            var msg = result.errors.length;
            if (result.errors.length > 1) {
                msg += ' erreurs détectées';
            } else {
                msg += ' erreur détectée';
            }
            msg += '<br/><br/>';
            var n = 1;
            for (var i in result.errors) {
                msg += n + '- ' + result.errors[i] + '<br/>';
                n++;
            }
            bimp_display_msg(msg, $container, 'danger');
            return true;
        }
    }
    return false;
}

function bimp_display_result_success(result, $container) {
    if (typeof (result.success) !== 'undefined') {
        if (typeof (result.success) === 'string') {
            bimp_display_msg(result.success, $container, 'success');
        } else {
            bimp_display_msg('Opération réalisée avec succès', $container, 'success');
        }
    }
}

// Notifications

function bimp_msg(msg, className, $container) {
    if (typeof (className) === 'undefined') {
        className = 'info';
    }
    var html = '<p class="alert alert-' + className + '">';
    html += msg;
    html += '</p>';

    if ($container && (typeof ($container) === 'object') && $container.length) {
        $container.html(html).stop().slideDown(250);
    } else {
        $container = $('#page_notifications');

        if (!$container.length) {
            return;
        }

        $container.append(html).show();

        var $p = $container.find('p:last-child').css('margin-left', '370px').animate({
            'margin-left': 0
        }, {
            'duration': 250,
            complete: function () {
                setTimeout(function () {
                    $p.fadeOut(500, function () {
                        $p.remove();
                        if (!$container.find('p').length) {
                            $container.hide();
                        }
                    });
                }, 5000);
            }
        });
    }
}

function bimp_show_msg(msg, className) {
    bimp_msg(msg, className);
}

function bimp_display_element_popover($element, content, side) {
    if ($element.length) {
        if (typeof (side) === 'undefined') {
            side = 'right';
        }
//        $element.popover('destroy');
        $element.popover({
            html: true,
            content: content,
            placement: side,
            trigger: 'manual',
            container: 'body'
        }).popover('show');
    }
}

//Modals:

function loadModalIFrame($button, url, title) {
    if ($button.hasClass('disabled')) {
        return;
    }

    $button.addClass('disabled');

    var $modal = $('#page_modal');
    var $resultContainer = $modal.find('.modal-ajax-content');
    $resultContainer.html('').hide();

    $modal.find('.modal-title').html(title);
    $modal.modal('show');
    $modal.find('.content-loading').show().find('.loading-text').text('Chargement');

    $modal.on('hide.bs.modal', function (e) {
        $modal.find('.extra_button').remove();
        $modal.find('.content-loading').hide();
        $button.removeClass('disabled');
    });

    var html = '<div style="overflow: hidden"><iframe id="iframe" frameborder="0" src="' + url + '" width="100%" height="800px"></iframe></div>';
    $resultContainer.html(html);

    $('#iframe').on("load", function () {
            var $head = $("iframe").contents().find("head");                
            $head.append($("<link/>", {rel: "stylesheet", href: DOL_URL_ROOT + "/bimpcore/views/css/content_only.css", type: "text/css"}));
        $modal.find('.content-loading').hide();
        $resultContainer.slideDown(250);
      });
}

function loadImageModal($button, src, title) {
    if ($button.hasClass('disabled')) {
        return;
    }

    $button.addClass('disabled');

    var $modal = $('#page_modal');
    var $resultContainer = $modal.find('.modal-ajax-content');
    $resultContainer.html('').hide();

    $modal.find('.modal-title').html(title);
    $modal.modal('show');
    $modal.find('.content-loading').show().find('.loading-text').text('Chargement');

    $modal.on('hide.bs.modal', function (e) {
        $modal.find('.extra_button').remove();
        $modal.find('.content-loading').hide();
        $modal.children('.modal-dialog').removeAttr('style');
        $button.removeClass('disabled');
    });

    var html = '<div class="align-center"><img id="modalImg" src="' + src + '" alt="' + title + '"/></div>';
    $resultContainer.html(html);

    $('#modalImg').on("load", function () {
        var $img = $(this);

        $modal.find('.content-loading').hide();
        $resultContainer.slideDown(250, function () {
            $modal.children('.modal-dialog').css('width', $img.width() + 30);
            $modal.modal('handleUpdate');
        });
      });
}

// Actions: 

function toggleFoldableSection($caption) {
    var $section = $caption.parent('.foldable_section');
    if (!$section.length) {
        return;
    }
    var $content = $section.children('.foldable_section_content');
    if (!$content.length) {
        return;
    }

    if ($section.hasClass('open')) {
        $content.stop().slideUp(250, function () {
            $section.removeClass('open').addClass('closed');
            $(this).removeAttr('style');
        });
    } else {
        $content.stop().slideDown(250, function () {
            $section.removeClass('closed').addClass('open');
            $(this).removeAttr('style');
        });
    }
}

// Evenements: 

function setCommonEvents($container) {
    // foldable sections: 
    $container.find('.foldable_section_caption').each(function () {
        if (!parseInt($(this).data('foldable_event_init'))) {
            $(this).click(function () {
                toggleFoldableSection($(this));
            });
            $(this).data('foldable_event_init', 1);
        }
    });
    // foldable panels:
    $container.find('.panel.foldable').each(function () {
        if (!parseInt($(this).data('foldable_event_init'))) {
            var $panel = $(this);
            $panel.children('.panel-heading').click(function () {
                if ($panel.hasClass('open')) {
                    $panel.children('.panel-body, .panel-footer').slideUp(250, function () {
                        $panel.removeClass('open').addClass('closed');
                    });
                } else {
                    $panel.children('.panel-body, .panel-footer').slideDown(250, function () {
                        $panel.removeClass('closed').addClass('open');
                    });
                }
            });
            $panel.children('.panel-heading').find('.headerBtn').click(function (e) {
                e.stopPropagation();
            });
            $(this).data('foldable_event_init', 1);
        }
    });
    // foldable view tables:
    $container.find('.objectViewtable.foldable').each(function () {
        var $table = $(this);
        $table.children('thead').children('tr:first-child').each(function () {
            if (!parseInt($(this).data('foldable_event_init'))) {
                $(this).click(function () {
                    if ($table.hasClass('open')) {
                        $table.children('tbody,tfoot').add($table.children('thead').children('tr.col_headers')).fadeOut(250, function () {
                            $table.removeClass('open').addClass('closed');
                        });
                    } else {
                        $table.children('tbody,tfoot').add($table.children('thead').children('tr.col_headers')).fadeIn(250, function () {
                            $table.removeClass('closed').addClass('open');
                        });
                    }
                });
                $(this).data('foldable_event_init', 1);
            }
        });
    });
    // Popup
    $container.find('.displayPopupButton').each(function () {
        if (!parseInt($(this).data('event_init'))) {
            setDisplayPopupButtonEvents($(this));
            $(this).data('event_init', 1);
        }
    });
    // bootstrap popover:
    $container.find('.bs-popover').popover();
    // Auto-expand: 
    $container.on('input.auto_expand', 'textarea.auto_expand', function () {
        var minRows = $(this).data('min_rows'), rows;
        if (!minRows) {
            minRows = 3;
        }
        this.rows = minRows;
        rows = Math.floor((this.scrollHeight - this.baseScrollHeight) / 16);
        this.rows = rows + minRows;
    });
    $container.find('textarea.auto_expand').each(function () {
        if (typeof (this.baseScrollHeight) === 'undefined') {
            var minRows = parseInt($(this).data('min_rows')), rows;
            if (!minRows) {
                minRows = 3;
            }
            this.baseScrollHeight = minRows * 16;
            this.rows = minRows;
            if (this.scrollHeight) {
                rows = Math.floor((this.scrollHeight - this.baseScrollHeight) / 16);
                this.rows = rows + minRows;
            }
        }
    });
    $container.find('select').each(function () {
        if (!parseInt($(this).data('color_event_init'))) {
            checkSelectColor($(this));
            $(this).change(function () {
                checkSelectColor($(this));
            });
            $(this).data('color_event_init', 1);
        }
    });
    $container.find('.nav-tabs').each(function () {
        if (!parseInt($(this).data('event_init'))) {
            $(this).find('li > a').click(function (e) {
                e.preventDefault();
                $(this).tab('show');
            });
            $(this).data('event_init', 1);
        }
    });
}

function setInputsEvents($container) {
    $container.find('.switch').each(function () {
        if (!parseInt($(this).data('event_init'))) {
            setSwitchInputEvents($(this));
            $(this).data('event_init', 1);
        }
    });
    $container.find('.toggle_value').each(function () {
        if (!parseInt($(this).data('event_init'))) {
            setToggleInputEvent($(this));
            $(this).data('event_init', 1);
        }
    });
    $container.find('.searchListOptions').each(function () {
        if (!parseInt($(this).data('event_init'))) {
            setSearchListOptionsEvents($(this));
            $(this).data('event_init', 1);
        }
    });
    $container.find('input[type="text"]').each(function () {
        if (!$(this).data('check_event_init')) {
            $(this).keyup(function () {
                checkTextualInput($(this));
            });
            $(this).data('check_event_init', 1);
        }
    });
}

function setDisplayPopupButtonEvents($button) {
    if (!$button.length) {
        return;
    }

    if (parseInt($button.data('event_init'))) {
        return;
    }

    var $popup = $button.parent().find('#' + $button.data('popup_id'));
    if ($popup.length) {
        $button.add($popup).mouseover(function () {
            $popup.show();
        }).mouseout(function () {
            $popup.hide();
        });
    }
    $button.data('event_init', 1);
}

// Rendus HTML 

function renderLoading(msg, id_container) {
    var html = '<div class="content-loading"';
    if (id_container) {
        html += ' id="' + id_container + '">';
    }
    html += '>';
    html += '<div class="loading-spin"><i class="fa fa-spinner fa-spin"></i></div>';
    if (msg) {
        html += '<p class="loading-text">' + msg + '</p>';
    }
    html += '</div>';
    return html;
}

// Inputs

function selectSwitchOption($button) {
    if ($button.hasClass('selected')) {
        return;
    }

    var val = $button.data('value');
    var $container = $button.parent('div').parent('.switchInputContainer');
    $container.find('input').val(val).change();
    $container.find('.switchOption').each(function () {
        $(this).removeClass('selected');
    });
    $button.addClass('selected');
}

function updateTimerInput($input, input_name) {
    var $container = $input.parent('div.timer_input');
    var days = $container.find('[name=' + input_name + '_days]').val();
    if (!/^[0-9]+$/.test(days)) {
        days = 0;
        $container.find('[name=' + input_name + '_days]').val(days);
    }
    days = parseInt(days);
    var hours = parseInt($container.find('[name=' + input_name + '_hours]').val());
    var minutes = parseInt($container.find('[name=' + input_name + '_minutes]').val());
    var secondes = parseInt($container.find('[name=' + input_name + '_secondes]').val());
    var total_secs = secondes + (minutes * 60) + (hours * 3600) + (days * 86400);
    $container.find('input[name=' + input_name + ']').val(total_secs).change();
}

function checkSelectColor($select) {
    var color = $select.find('option:selected').data('color');
    if (color) {
        $select.css({'color': '#' + color, 'font-weight': 'bold', 'border-bottom-color': '#' + color});
    } else {
        $select.css({'color': '#3C3C3C', 'font-weight': 'normal', 'border-bottom-color': 'rgba(0, 0, 0, 0.2)'});
    }
}

// Affichages: 

function displayMoneyValue(value, $container, classCss, currency) {
    var negatif = false;
    if (!$container.length) {
        return;
    }

    if (typeof (currency) === 'undefined') {
        currency = '&euro;';
    }
    if (typeof (classCss) === 'undefined') {
        classCss = '';
    }

    value = parseFloat(value);
    if (value < 0) {
        negatif = true;
        value = -value;
    }

    if (value === null || isNaN(value)) {
        value = '0,00';
    } else {
        value = Math.round10(value, -2);
        value = '' + value;
        if (!/^[0-9]+\.[0-9]+/.test(value)) {
            value += ',0';
        }
        if (!/^[0-9]+\.[0-9]{2}$/.test(value)) {
            value += '0';
        }
    }

    value = value.replace(/^([0-9]+)\.?([0-9]?)([0-9]?)$/, '$1,$2$3');
    value = lisibilite_nombre(value);
    if (negatif)
        value = "-" + value;
    $container.html('<span class="' + classCss + '">' + value + ' ' + currency + '</span>');
}

function lisibilite_nombre(nbr)
{
    var nombre = '' + nbr;
    var retour = '';
    var count = 0;
    for (var i = nombre.length - 1; i >= 0; i--)
    {
        if (count != 0 && count % 3 == 0)
            retour = nombre[i] + ' ' + retour;
        else
            retour = nombre[i] + retour;
        count++;
    }
    return retour.replace(" ,", ",");
}
// Math:

(function () {
    function decimalAdjust(type, value, exp) {
        if (typeof exp === 'undefined' || +exp === 0) {
            return Math[type](value);
        }
        value = +value;
        exp = +exp;
        if (value === null || isNaN(value) || !(typeof exp === 'number' && exp % 1 === 0)) {
            return NaN;
        }
        if (value < 0) {
            return -decimalAdjust(type, -value, exp);
        }
        value = value.toString().split('e');
        value = Math[type](+(value[0] + 'e' + (value[1] ? (+value[1] - exp) : -exp)));
        value = value.toString().split('e');
        return +(value[0] + 'e' + (value[1] ? (+value[1] + exp) : exp));
    }

    if (!Math.round10) {
        Math.round10 = function (value, exp) {
            return decimalAdjust('round', value, exp);
        };
    }
    if (!Math.floor10) {
        Math.floor10 = function (value, exp) {
            return decimalAdjust('floor', value, exp);
        };
    }
    if (!Math.ceil10) {
        Math.ceil10 = function (value, exp) {
            return decimalAdjust('ceil', value, exp);
        };
    }
})();

// Divers:

function getUrlParam(param) {
    var search = window.location.search.replace('?', '');
    var args = search.split('&');
    var value = '';
    for (i in args) {
        var regex = new RegExp('^' + param + '=(.*)$');
        if (regex.test(args[i])) {
            value = args[i].replace(regex, '$1');
        }
    }
    return value;
}

// Ajouts jQuery:

$.fn.tagName = function () {
    return this.get(0).tagName.toLowerCase();
};

$.fn.findParentByClass = function (className) {
    if (!this.length) {
        return this;
    }

    var $parent = this.parent();
    while ($parent.length) {
        if ($parent.hasClass(className)) {
            return $parent;
        }
        $parent = $parent.parent();
    }
    return $();
};

$.fn.findParentByTag = function (tag) {
    if (!this.length) {
        return this;
    }

    tag = tag.toLowerCase();

    var $parent = this.parent();
    while ($parent.length) {
        if ($parent.tagName() === tag) {
            return $parent;
        }
        $parent = $parent.parent();
    }

    return $();
};

function findParentByClass($element, className) {
    return $element.findParentByClass(className);
}

function findParentByTag($element, tag) {
    return $element.findParentByTag(tag);
}

$(document).ready(function () {
    $('body').append('<div id="page_notifications"></div>');


});