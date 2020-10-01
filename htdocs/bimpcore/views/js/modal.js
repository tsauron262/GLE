var modal_idx = 0;

function BimpModal($modal, var_name, open_btn_id, params) {
    if (typeof (params) === 'undefined') {
        params = {};
    }

    var modal = this;
    this.var_name = var_name;
    this.idx = 0;
    this.next_idx = 1;
    this.$modal = $modal;
    this.$titles = $modal.find('.modal-titles_container');
    this.$loading = $modal.find('.content-loading');
    this.$contents = $modal.find('.modal-contents_container');
    this.$footer = $modal.find('.modal-footer');
    this.$history = $modal.find('.modal-nav-history').find('ul.dropdown-menu');
    this.$prevBtn = $modal.find('.modal-nav-prev');
    this.$nextBtn = $modal.find('.modal-nav-next');
    this.$historyToggle = $modal.find('.modal-nav-history').find('.dropdown-toggle');

    if (open_btn_id) {
        this.$openBtn = $('#' + open_btn_id);
    } else {
        this.$openBtn = false;
    }

    this.content_removable = false;
    this.max_contents = 'none';

    for (var name in params) {
        modal[name] = params[name];
    }

    this.newContent = function (title, content_html, show_loading, loading_text, $button, format, show) {
        if (typeof (format) === 'undefined') {
            format = 'medium';
        }
        if (typeof (show) === 'undefined') {
            show = true;
        }

        hidePopovers(modal.$modal);

        modal.$footer.find('.extra_button').hide();
        modal.$titles.find('.modal_title').hide();
        modal.$contents.find('.modal_content').hide();

        var all_next_removed = false;
        modal.$contents.find('.modal_content').each(function () {
            if (!all_next_removed) {
                var idx = parseInt($(this).data('idx'));
                if (idx !== modal.idx) {
                    modal.removeContent(idx, false);
                } else {
                    all_next_removed = true;
                }
            }
        });

        modal.idx = modal.next_idx;
        modal.next_idx++;

        modal.$titles.append('<span class="modal_title" id="modal_title_' + modal.idx + '">' + title + '</span>');

        if (show_loading) {
            if (!loading_text || typeof (loading_text) !== 'string') {
                loading_text = 'Chargement';
            }

            modal.$loading.find('.loading-text').text(loading_text);
            modal.$loading.show();
        } else {
            modal.$loading.hide();
        }

        if (show) {
            modal.$modal.modal('show');
        }

        modal.$modal.on('hide.bs.modal', function (e) {
            modal.$loading.hide();
            if ($.isOk($button)) {
                $button.removeClass('disabled');
            }
        });

        var html = '<div class="modal_content" id="modal_content_' + modal.idx + '" data-idx="' + modal.idx + '" data-format="' + format + '" data-width="">';
        html += content_html;
        html += '</div>';

        modal.$contents.prepend(html);
        var $container = modal.$contents.find('#modal_content_' + modal.idx);

        $('body').trigger($.Event('contentLoaded', {
            $container: $container
        }));

        html = '<li id="modal_history_' + modal.idx + '"><span class="btn btn-light-primary" onclick="' + modal.var_name + '.displayContent(' + modal.idx + ')">' + title + '</span></li>';
        modal.$history.prepend(html);
        modal.checkContents();
        modal.checkCurrentContentFormat();
    };

    this.clearCurrentContent = function () {
        this.removeContent(modal.idx);
        var $content = modal.$contents.find('.modal_content').first();
        if ($content.length) {
            modal.displayContent(parseInt($content.data('idx')));
        }
        modal.hide();
    };

    this.clearAllContents = function () {
        modal.$contents.find('.modal_content').each(function () {
            var idx = parseInt($(this).data('idx'));
            if (idx) {
                modal.removeContent(idx, false, false);
            }
        });

        modal.checkContents();
        modal.hide();
    };

    this.removeContent = function (idx, check_contents, display_remaining_content) {
        if (!modal.content_removable) {
            return;
        }

        if (typeof (check_contents) === 'undefined') {
            check_contents = true;
        }
        if (typeof (display_remaining_content) === 'undefined') {
            display_remaining_content = true;
        }

        if (display_remaining_content && modal.idx === idx) {
            var hasPrev = false;
            var hasNext = false;
            modal.$contents.find('.modal_content').each(function () {
                var cur_idx = parseInt($(this).data('idx'));
                if (cur_idx < idx) {
                    hasPrev = true;
                } else if (cur_idx > idx) {
                    hasNext = true;
                }
            });
            if (hasPrev) {
                this.displayPrev();
            } else if (hasNext) {
                this.displayNext();
            }
        }

        modal.$titles.find('#modal_title_' + idx).remove();
        modal.$contents.find('#modal_content_' + idx).remove();
        modal.$footer.find('.extra_button.modal_' + idx).remove();
        modal.$history.find('#modal_history_' + idx).remove();

        if (check_contents) {
            modal.checkContents();
        }

        if (!modal.$contents.find('.modal_content').length) {
            modal.hide();
        }
    };

    this.removeComponentContent = function (component_id) {
        modal.$contents.find('.modal_content').each(function () {
            var idx = parseInt($(this).data('idx'));
            if (idx !== modal.idx) {
                var $component = $(this).find('#' + component_id);
                if ($component.length) {
                    modal.removeContent(idx, false);
                }
            }
        });

        modal.checkContents();
    };

    this.checkContents = function () {
        var n = 0;
        var prev = 0;
        var next = 0;

        modal.$contents.find('.modal_content').each(function () {
            n++;
            var idx = parseInt($(this).data('idx'));
            if (modal.max_contents !== 'none' && n > modal.max_contents) {
                modal.removeContent(idx, false);
            } else {
                if (idx > modal.idx) {
                    next++;
                } else if (idx < modal.idx) {
                    prev++;
                }
            }
        });

        if (n > 0) {
            if (modal.$openBtn) {
                modal.$openBtn.removeClass('closed');
            }
        } else {
            if (modal.$openBtn) {
                modal.$openBtn.addClass('closed');
            }
        }

        if (n > 1) {
            modal.$historyToggle.removeClass('disabled');
        } else {
            modal.$historyToggle.addClass('disabled');
        }

        if (prev > 0) {
            modal.$prevBtn.removeClass('disabled');
        } else {
            modal.$prevBtn.addClass('disabled');
        }

        if (next > 0) {
            modal.$nextBtn.removeClass('disabled');
        } else {
            modal.$nextBtn.addClass('disabled');
        }
    };

    this.displayContent = function (idx) {
        if (idx === modal.idx) {
            return;
        }

        var $content = modal.$contents.find('#modal_content_' + idx);
        if ($content.length) {
            var current_idx = modal.idx;
            modal.idx = idx;

            modal.$footer.find('.extra_button').hide();
            modal.$titles.find('.modal_title').hide();

            modal.$titles.find('#modal_title_' + idx).show();
            modal.$footer.find('.extra_button.modal_' + idx).show();
            modal.$contents.find('.modal_content').hide();
            $content.fadeIn(250);
        } else {
            modal.removeContent(idx, false);
        }

        modal.checkContents();
        modal.checkCurrentContentFormat();
    };

    this.displayPrev = function () {
        var next = false;
        var done = false;

        modal.$contents.find('.modal_content').each(function () {
            if (!done) {
                if (next) {
                    modal.displayContent(parseInt($(this).data('idx')));
                    done = true;
                } else {
                    if (parseInt($(this).data('idx')) === modal.idx) {
                        next = true;
                    }
                }
            }
        });
    };

    this.displayNext = function () {
        var prev_idx = 0;
        var done = false;

        modal.$contents.find('.modal_content').each(function () {
            if (!done) {
                var idx = parseInt($(this).data('idx'));
                if (idx === modal.idx) {
                    modal.displayContent(prev_idx);
                    done = true;
                } else {
                    prev_idx = idx;
                }
            }
        });
    };

    this.hide = function () {
        modal.checkContents();
        modal.$modal.modal('hide');
    };

    this.show = function () {
        modal.checkContents();
        modal.$modal.modal('show');
    };

    this.loadAjaxContent = function ($button, ajax_action, ajax_data, title, loading_text, success_callback, ajax_params, modal_format) {
        if ($button != null && $button.hasClass('disabled')) {
            return;
        }

        if (typeof (modal_format) === 'undefined') {
            modal_format = 'medium';
        }

        modal.newContent(title, '', true, loading_text, null, modal_format);

        if (!ajax_params || typeof (ajax_params) === 'undefined') {
            ajax_params = {};
        }

        var default_params = {
            display_success: false,
            error_msg: 'Echec du chargement du contenu'
        };

        for (var key in default_params) {
            if (typeof (ajax_params[key]) === 'undefined') {
                ajax_params[key] = default_params[key];
            }
        }

        ajax_params.$button = $button;
        ajax_params.$modal = modal.$modal;
        ajax_params.success_callback = success_callback;
        ajax_params.success = function (result, bimpAjax) {
            modal.$loading.hide();
            if (typeof (result.html) !== 'undefined') {
                bimpAjax.$resultContainer.html(result.html).stop().show().removeAttr('style');

                $('body').trigger($.Event('contentLoaded', {
                    $container: bimpAjax.$resultContainer
                }));

                modal.$contents.stop().slideDown(250, function () {
                    $(this).css('height', 'auto');
                    if (typeof (bimpAjax.success_callback) === 'function') {
                        bimpAjax.success_callback(result, bimpAjax);
                    }
                });
            }

            if (typeof (result.modal_format) !== 'undefined' && result.modal_format) {
                modal.setContentFormat(bimpAjax.modal_idx, result.modal_format);
            }

            bimpAjax.$modal.modal('handleUpdate');
        };
        ajax_params.error = function (result, bimpAjax) {
            modal.$loading.hide();
            bimpAjax.$modal.modal('handleUpdate');
        };

        ajax_data['modal_idx'] = modal.idx;
        ajax_params.modal_idx = modal.idx;

        BimpAjax(ajax_action, ajax_data, modal.$contents.find('#modal_content_' + modal.idx), ajax_params);
    };

    this.loadIframe = function ($button, url, title) {
        if ($button.hasClass('bs-popover')) {
            $button.popover('hide');
        }
        if ($button.hasClass('disabled')) {
            return;
        }

        $button.addClass('disabled');

        this.newContent(title, '', true, 'Chargement', $button, 'large');

        var $container = modal.$contents.find('#modal_content_' + modal.idx);
        var html = '<div style="overflow: hidden"><iframe class="page_modal_iframe" frameborder="0" src="' + url + '" width="100%" height="630px"></iframe></div>';
        $container.html(html);
        var $iframe = $container.find('.page_modal_iframe');
        $iframe.on("load", function () {
            var $head = $iframe.contents().find("head");
            $head.append($("<link/>", {rel: "stylesheet", href: DOL_URL_ROOT + "/bimpcore/views/css/content_only.css", type: "text/css"}));
            modal.$loading.hide();
            modal.$contents.slideDown(250);
        });
    };

    this.loadImage = function ($button, src, title) {
        if ($button.hasClass('bs-popover')) {
            $button.popover('hide');
        }
        if ($button.hasClass('disabled')) {
            return;
        }

        $button.addClass('disabled');
        modal.newContent(title, '', true, 'Chargement de l\'image', $button);

        var html = '<div class="align-center"><img class="page_modal_img" src="' + src + '" alt="' + title + '"/></div>';
        var $container = modal.$contents.find('#modal_content_' + modal.idx);
        $container.html(html);
        var $img = $container.find('.page_modal_img');
        $img.on("load", function () {
            modal.$loading.hide();
//            modal.$contents.slideDown(250, function () {
//                var width = $img.width() + 30;
//                modal.$modal.children('.modal-dialog').css('width', width);
//                $container.data('width', width);
//                modal.$modal.modal('handleUpdate');
//            });
        });
    };

    this.addButton = function (label, onclick, type, extra_class, idx) {
        if (!idx) {
            idx = modal.idx;
        }
        if (!type) {
            type = 'default';
        }

        var html = '<button type="button" class="extra_button modal_' + modal.idx + ' btn btn-' + type;
        if (extra_class && typeof (extra_class) === 'string') {
            html += ' ' + extra_class;
        }

        html += '" onclick="' + onclick + '"';
        if (idx !== modal.idx) {
            html += ' style="display: none"';
        }

        html += 'data-modal_idx="' + idx + '"';

        html += '>' + label + '</button>';
        modal.$footer.append(html);
    };

    this.addlink = function (label, href, type, extra_class, idx) {
        if (!idx) {
            idx = modal.idx;
        }

        if (!type) {
            type = 'default';
        }

        var html = '<a class="extra_button modal_' + modal.idx + ' btn btn-' + type;
        if (extra_class && typeof (extra_class) === 'string') {
            html += ' ' + extra_class;
        }

        html += '" href="' + href + '"';
        if (idx !== modal.idx) {
            html += ' style="display: none"';
        }
        html += '>' + label + '</a>';
        modal.$footer.append(html);
    };

    this.setContentFormat = function (idx, format) {
        var $content = modal.$contents.find('#modal_content_' + idx);
        if ($content.length) {
            $content.data('format', format);
        }

        modal.checkCurrentContentFormat();
    };

    this.checkCurrentContentFormat = function () {
        var $content = modal.$contents.find('#modal_content_' + modal.idx);
        var $dialog = modal.$modal.find('.modal-dialog');
        if ($content.length && $dialog.length) {
            var format = $content.data('format');

            switch (format) {
                case 'small':
                    $dialog.removeClass('modal-lg');
                    $dialog.removeClass('modal-md');
                    $dialog.addClass('modal-sm');
                    break;

                default:
                case 'medium':
                    $dialog.removeClass('modal-lg');
                    $dialog.removeClass('modal-sm');
                    $dialog.addClass('modal-ms');
                    break;

                case 'large':
                    $dialog.removeClass('modal-sm');
                    $dialog.removeClass('modal-md');
                    $dialog.addClass('modal-lg');
                    break;
            }
        }
    };

    this.getContent = function (modal_idx) {
        return modal.$contents.find('#modal_content_' + modal_idx);
    };

    this.scrollTop = function () {
        bimpScroller.newScrollValue(0, modal.$modal.find('.modal-body'), modal.$modal.$contents);
    };

    this.scrollBottom = function () {
        bimpScroller.newScrollToBottom(modal.$modal.find('.modal-body'), modal.$contents);
    };

    this.scrollUp = function () {

    };

    this.scrollDown = function () {

    };
}

var bimpModal = null;

$(document).ready(function () {
    bimpModal = new BimpModal($('#page_modal'), 'bimpModal', 'openModalBtn', {
        'content_removable': true,
        'max_contents': 10
    });
});
