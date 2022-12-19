
class hashtag extends AbstractNotification {
    
    /**
     * Overrides
     */
    
    constructor(nom, id_notification) {
        super(nom, id_notification);
    }
    
    init() {
        if(typeof object_labels['BimpNote'] === 'undefined')
            object_labels['BimpNote'] = 'Hashtag';

        if($('a#' + this.dropdown_id).length == 0) {
       
            if(theme != 'BimpTheme')
                var notif_white = 'notif_white';
            else
                var notif_white = '';

            var html = '<a class="nav-link dropdown-toggle header-icon ' + notif_white + '" id="' + this.dropdown_id + '" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">';
            html +='<i class="fas fa5-hashtag atoplogin"></i></a>';
            html += '<div class="dropdown-menu dropdown-menu-right notification-dropdown bimp_notification_dropdown" aria-labelledby="' + this.dropdown_id +'">';
            html += '<h4 class="header">';
            html += 'Objets liés' + this.getBoutonReload(this.dropdown_id);
            html += '<a style="float:right" onclick="loadModalObjectCustomContent($(this), {module: \'bimpcore\', object_name: \'Bimp_User\', id_object: id_user}, \'renderLinkedObjectsLists\', [\'sources\'], \'Objets liés par citation\', null, \'medium\');"><i class="fas fa5-comments iconLeft"></i>Mes objets liés</a>';
            html += '</h4>';
            html += '<div class="notifications-wrap list_notification ' + this.nom + '">';
            html += '</div>';
            html += '</div>';

            $(this.parent_selector).prepend(html);
            
            super.init(this);
        }
    }
    
    formatElement(element, key) {
                        
        if($('div.list_part[key="' + key + '"]').length) {
            $('div.list_part[key="' + key + '"]').remove();
            AbstractNotification.prototype.elementRemoved(1, this.dropdown_id);
        }
            
        var html = '';

        element.is_new = this.isNew(element);
                        
        var style = 'petit';

        // Initiales
        
        // Client
        if(element.obj == undefined || element.obj.nom_url == undefined) {
            html += "Pièce supprimée, aucun URL n'est disponible";
        } else {
            html += element.obj.nom_url;
        }
        
        var callback_set_as_viewed = this.ptr +'.setAsViewed("'+key+'","'+element.id+'")';
        
        // Voir conversation
//        html += '<span isclass="rowButton bs-popover" data-toggle="popover" data-trigger="hover" data-placement="top" data-content="Vue rapide" data-html="false" data-viewport="{&quot;selector&quot;: &quot;body&quot;, &quot;padding&quot;: 0}" ';
//        html += 'onclick=\'' + callback_set_as_viewed + ' ; loadModalObjectNotes($(this), "' + element.obj_module +'", "' + element.obj_name +'", "' + element.id_obj + '", "chat", true);\' data-original-title="" title="Voir toute la conversation"><i class="far fa5-eye"></i></span>'
        
        // Marquer comme lu
        if(!element.is_viewed){
            html += '<span class="rowButton bs-popover nonLu my" data-toggle="popover" data-trigger="hover" data-placement="top" data-content="Vue rapide" data-html="false" data-viewport="{&quot;selector&quot;: &quot;body&quot;, &quot;padding&quot;: 0}" ';
            html += 'onclick=\'' + callback_set_as_viewed + ';\' data-original-title="" title="Marquer comme lu"><i class="far fa5-envelope-open"></i></span>';
        }
        

        
        // Content
        html += '<div class="msg_cotainer">' + element.content + '</div>';
        
        

        html += '</div>';

        return html;
    }
    
    isNew(element) {

        if(parseInt(element.is_viewed) === 1)
            return 0;
        
        return 1;
    }
    
    getKey(element) {
        return element.id;
    }
    
    displayNotification(element) {
        var bn = this;

        if (window.Notification && Notification.permission === "granted") {
            
            var titre = "Nouveau message de ";
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
            
            var n = new Notification("Vous avez " + nb_valid + " objés liés.", {
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


}
