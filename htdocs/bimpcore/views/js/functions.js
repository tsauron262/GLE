// successCallBack : soit fonction callback en cas de succès, soit message par défaut en cas de succès
// errorCallBack : soit fonction callback en cas d'erreur, soit message par défaut en cas d'erreur

// Traitements Ajax:
var ajaxRequestsUrl = './index.php';

function bimp_json_ajax(action, data, $resultContainer, successCallBack, errorCallBack, display_processing) {
    var display_result_errors_only = false;
    if ((typeof (successCallBack) === 'string') ||
            typeof (successCallBack) === 'function') {
        display_result_errors_only = true;
    }

    if (typeof (display_processing) === 'undefined') {
        display_processing = true;
    }

    if (display_processing) {
        bimp_display_msg('Traitement en cours', $resultContainer, 'info');
    }

    if (typeof (data) === 'object') {
        data.ajax = 1;
        data.action = action;
    } else if (typeof (data) === 'string') {
        data += '&ajax=1&action=' + action;
    }

    var ajaxRequestUrl = '';
    if (typeof (data.ajaxRequestUrl) !== 'undefined') {
        ajaxRequestUrl = data.ajaxRequestUrl;
    } else {
        ajaxRequestUrl = ajaxRequestsUrl;
    }

    $.ajax({
        type: "POST",
        url: ajaxRequestUrl,
        dataType: 'json',
        data: data,
        success: function (result) {
            bimp_displayAjaxResult(result, $resultContainer, display_result_errors_only);
            if (!result.errors.length) {
                if ((typeof (result.success) === 'string') && result.success) {
                    bimp_display_msg(result.success, $resultContainer, 'success');
                } else if (typeof (successCallBack) === 'string') {
                    bimp_display_msg(successCallBack, $resultContainer, 'success');
                }
                if (typeof (successCallBack) === 'function') {
                    successCallBack(result);
                }
            } else {
                if (typeof (errorCallBack) === 'function') {
                    errorCallBack(result);
                }
            }
        },
        error: function () {
            if (typeof (errorCallBack) === 'string') {
                bimp_display_msg(errorCallBack, $resultContainer, 'danger');
            } else {
                bimp_display_msg('Une erreur est survenue. La requête n\'a pas aboutie', $resultContainer, 'danger');
            }
            if (typeof (errorCallBack) === 'function') {
                errorCallBack();
            }
        }
    });
}

function bimp_display_msg(msg, $container, className) {
    if (!$container || (typeof ($container) === 'undefined') || !$container.length) {
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
            if ($container) {
                bimp_display_msg(msg, $container, 'danger');
            }
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

// Actions: 

function toggleFoldableSection($caption) {
    var $section = $caption.parent('.foldable_section');
    if (!$section.length) {
        alert('ici');
        return;
    }
    var $content = $section.children('.foldable_section_content');
    if (!$content.length) {
        alert('la');
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
    $container.find('.foldable_section_caption').click(function () {
        toggleFoldableSection($(this));
    });
    $container.find('.displayPopupButton').each(function () {
        setDisplayPopupButtonEvents($(this));
    });
}

function setDisplayPopupButtonEvents($button) {
    if (!$button.length) {
        return;
    }
    var $popup = $('#' + $button.data('popup_id'));
    if ($popup.length) {
        $button.add($popup).mouseover(function () {
            $popup.show();
        }).mouseout(function () {
            $popup.hide();
        });
    }
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