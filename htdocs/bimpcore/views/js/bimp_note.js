
class bimp_note extends AbstractNotification {
    
    /**
     * Overrides
     */
    
    constructor(nom) {
        super(nom);
    }
    
    init() {
        
        if(typeof object_labels['BimpNote'] === 'undefined')
            object_labels['BimpNote'] = 'Note';

        if($('a#' + this.dropdown_id).length == 0) {
       
            var html = '<a class="nav-link dropdown-toggle" href="#" id="' + this.dropdown_id + '" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">';
            html +='<i class="fa fa-envelope atoplogin"></i></a>';
            html += '<div class="dropdown-menu dropdown-menu-right notification-dropdown bimp_notification_dropdown" aria-labelledby="' + this.dropdown_id +'">';
            html += '<h4 class="header">Messages</h4>';
            html += '<div class="notifications-wrap list_notification ' + this.nom + '">';
            html += '</div>';
            html += '</div>';

            $(this.parent_selector).append(html);
            
            super.init(this);
        }
    }
    
    formatElement(element, key) {
                        
        if($('div.list_part[key="' + key + '"]').length) {
            $('div.list_part[key="' + key + '"]').remove();
            AbstractNotification.prototype.elementRemoved(1, this.dropdown_id);
        }
            
        var html = '';

        // User courant est l'auteur
        if(id_user == element.author.id)
            var i_am_author = 1;
        else
            var i_am_author = 0;
        
//        if(i_am_author === 1 || element.viewed === 1)
        element.is_new = this.isNew(element);
                        
        var style = 'petit';

        // Initiales
        var author_initiale = this.getInitiales(element.author.nom);
        if (element.is_user_or_grp)
            var dest_initiale = this.getInitiales(element.dest.nom);
        
        // Client
        html += element.obj.nom_url;
        if(typeof element.obj.client_nom_url !== 'undefined')
            html += ' - ' + element.obj.client_nom_url;
        
        var callback_set_as_viewed = 'bimp_note.setAsViewed("' + element.obj_type +'", "' + element.obj_module +'", "' + element.obj_name +'", "' + element.id_obj + '", "' + key + '")';
        
        // Voir conversation
        html += '<span class="rowButton bs-popover" data-toggle="popover" data-trigger="hover" data-placement="top" data-content="Vue rapide" data-html="false" data-viewport="{&quot;selector&quot;: &quot;body&quot;, &quot;padding&quot;: 0}" '
        html += 'onclick=\'' + callback_set_as_viewed + ' ; loadModalObjectNotes($(this), "' + element.obj_module +'", "' + element.obj_name +'", "' + element.id_obj + '", "chat", true);\' data-original-title="" title="Voir toute la conversation"><i class="far fa5-eye"></i></span>'
        
        // Marquer comme lu
        html += '<span class="rowButton bs-popover" data-toggle="popover" data-trigger="hover" data-placement="top" data-content="Vue rapide" data-html="false" data-viewport="{&quot;selector&quot;: &quot;body&quot;, &quot;padding&quot;: 0}" '
        html += 'onclick=\'' + callback_set_as_viewed + ';\' data-original-title="" title="Marquer comme lu"><i class="far fa5-envelope-open"></i></span>'
               
        
        html += '<div class="d-flex justify-content-' + (element.i_am_dest ? "start" : (i_am_author ? "end" : "")) + (style == "petit" ? ' petit' : '') + ' mb-4">';
        
        // Author
        html += '<span data-toggle="tooltip" data-placement="top" title="' + element.author.nom + '" class="chat-img pull-left">';
        html += '<img src="https://placehold.it/' + (style === "petit" ? '35' : '55') + '/' + (element.type_author ? '55C1E7' : '5500E7') + '/fff&amp;text=' + author_initiale + '" alt="User Avatar" class="img-circle">';
        html += '</span>';
        
        // Content
        html += '<div class="msg_cotainer">' + element.content + '</div>';
        
        if(element.author.id !== id_user && 0 < parseInt(element.author.id)) {// TODO rep à autre que 1224
            var js_repondre = "setObjectAction($(this), {module: 'bimpcore', object_name: 'BimpNote', id_object: '" + element.id + "'}, 'repondre', {type_dest: 1, fk_user_dest: " + element.author.id + ", content: '', id: ''}, 'rep', null, null, null, null, false, 'medium')";
            html += '<span class="rowButton bs-popover" onclick="' + js_repondre + '"><i class="fas fa-share link"></i></span>';
        }
        
        // Dest
        if (element.is_user_or_grp) {
            html += '<span data-toggle="tooltip" data-placement="top" title="' + element.dest.nom + '"';
            html += ' class="chat-img pull-left ' + (element.is_viewed ? "" : "nonLu") + (element.i_am_dest ? " my" : "") + '">';
            html += '<img src="https://placehold.it/' + (style == "petit" ? '28' : '45') + '/' + (element.is_user ? '55C1E7' : '5500E7') + '/fff&amp;text=' + dest_initiale + '" alt="User Avatar" class="img-circle">';
            html += '</span>';
        }

        html += '</div>';

        return html;
    }
    
    isNew(element) {

        if(id_user === parseInt(element.author.id) || parseInt(element.is_viewed) === 1)
            return 0;
        
        return 1;
    }
    
    getKey(element) {
        return element.obj_type + '_' + element.obj_module + '_' + element.obj_name + '_' + element.id_obj;
    }
    
    displayNotification(element) {
        var bn = this;

        if (window.Notification && Notification.permission === "granted") {
            
            var titre = "Nouveau message de " + element.author.nom;
            var n = new Notification(titre, {
                body: element.content,
                icon: DOL_URL_ROOT + '/theme/BimpTheme/img/favicon.ico'
            });
            
            n.onclick = function() {
                if(parseInt($('div[aria-labelledby="' + bn.dropdown_id + '"]').attr('is_open')) !== 1)
                    $('#' + bn.dropdown_id).trigger('click');
            }
        }
        
    }
    
    displayMultipleNotification(elements) {
        var nb_valid = elements.length;
        var bn = this;

        if (window.Notification && Notification.permission === "granted") {
            
            var n = new Notification("Vous avez reçu " + nb_valid + " messages.", {
                body: '',
                icon: DOL_URL_ROOT + '/theme/BimpTheme/img/favicon.ico'
            });
            
            n.onclick = function() {
                $('#' + bn.dropdown_id).trigger('click');
            }
        }
    }
    
    
    /**
     * Fonctions spécifique à la classe
     */
    
    static setAsViewed(obj_type, obj_module, obj_name, id_obj, key) {
        var bn = this;

        var data = {
            obj_type: obj_type,
            obj_module: obj_module,
            obj_name: obj_name,
            id_obj: id_obj
        };
        
        var initAjaxRequestsUrl = ajaxRequestsUrl;
        ajaxRequestsUrl = dol_url_root + '/bimpmsg/index.php';
        
        BimpAjax('setAsViewed', data, null, {
            display_errors: false,
            display_warnings: false,
            display_success: false,
            success: function (result, bimpAjax) {
                bimp_note.isViewed(key, result.nb_set_as_viewed);
            }
        });
        ajaxRequestsUrl = initAjaxRequestsUrl;

    }
    
    static isViewed(key, qty) {
        
        if(0 < qty) {
            $('div.list_part[key="' + key + '"] span.nonLu').removeClass('nonLu');
            AbstractNotification.prototype.elementRemoved(1, 'dropdown_' + this.name);
        }
    }
    
    getInitiales(nom) {
        var full_name = nom.split(' ');
        var initials = full_name.shift().charAt(0) + full_name.pop().charAt(0);
        return initials.toUpperCase();
    }

}