
class notif_task extends AbstractNotification {
    
    /**
     * Overrides
     */
    
    constructor(nom) {
        super(nom);
    }
    
    init() {
        
//        if(typeof object_labels['BimpNote'] === 'undefined')
//            object_labels['BimpNote'] = 'Note';

        if($('a#' + this.dropdown_id).length == 0) {
       
            var html = '<a class="nav-link dropdown-toggle" href="#" id="' + this.dropdown_id + '" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">';
            html +='<i class="fa fa-tasks atoplogin"></i></a>';
            html += '<div class="dropdown-menu dropdown-menu-right notification-dropdown bimp_notification_dropdown" aria-labelledby="' + this.dropdown_id +'">';
            html += '<h4 class="header">Tâche</h4>';
            html += '<div class="notifications-wrap list_notification ' + this.nom + '">';
            html += '</div>';
            html += '</div>';

            $(this.parent_selector).append(html);
            
            super.init(this);
        }
    }
    
    formatElement(element, key) {
            
        var html = 'OK';

        return html;
    }
    
//    isNew(element) {
//
//        if(id_user === parseInt(element.author.id) || parseInt(element.is_viewed) === 1)
//            return 0;
//        
//        return 1;
//    }
    
//    getKey(element) {
//        return element.obj_type + '_' + element.obj_module + '_' + element.obj_name + '_' + element.id_obj;
//    }
    
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


}