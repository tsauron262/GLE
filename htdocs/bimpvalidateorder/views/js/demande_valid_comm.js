
class demande_valid_comm extends AbstractNotification {
    /**
     * Overrides
     */
    
    constructor(nom) {
        super(nom);
    }
    
    init() {

        if($('a#' + this.dropdown_id).length == 0) {
            
            var link = '<span class="objectIcon" title="Voir mes demandes à valider" \n\
onclick="window.open(\'' + DOL_URL_ROOT +'/bimpvalidateorder/index.php?fc=index&tab=my_validations\')">\n\ <i class="fas fa5-external-link-alt"></i></span>';
       
            var html = '<a class="nav-link dropdown-toggle" href="#" id="' + this.dropdown_id + '" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">';
            html +='<i class="fa fa-check atoplogin"></i></a>';
            html += '<div class="dropdown-menu dropdown-menu-right notification-dropdown bimp_notification_dropdown" aria-labelledby="' + this.dropdown_id + '">';
            html += '<h4 class="header">Demandes de validation ' + link + '</h4>';
            html += '<div class="notifications-wrap list_notification ' + this.nom + '">';
            html += '</div>';
            html += '</div>';
            
            $(this.parent_selector).append(html);
            
            super.init(this);
        }
    }
    
    formatElement(element, key) {
                
        var html = 'Demande de validation ' + element.type + '<br/>';
        
        if(typeof element.ref !== 'undefined')
            html += 'Ref : <strong>' + element.ref + '</strong><br/>';
        
        if(typeof element.secteur !== 'undefined')
            html += 'Secteur : ' + element.secteur + '<br/>';
        
        if(typeof element.montant !== 'undefined')
            html += 'Montant : ' + element.montant.toFixed(2) + ' €<br/>';
        
        if(typeof element.remise !== 'undefined')
            html += 'Remise : ' + element.remise.toFixed(2) + ' %<br/>';
        
        if(typeof element.user_firstname !== 'undefined' && element.user_lastname !== 'undefined')
            html += 'Demandeur : ' + element.user_firstname + ' ' + element.user_lastname;
        
        return html;
    }
    
    displayNotification(element) {
        var dvn = this;

        if (window.Notification && Notification.permission === "granted") {
            
            var body = 'Secteur: ' + element.secteur + ', ';
            
            if(typeof element.montant !== 'undefined')
                body += 'montant: ' + element.montant.toFixed(2) + '€';
            
            if(typeof element.remise !== 'undefined')
                body += 'remise: ' + element.remise.toFixed(2) + '€';
            
            body += ' demandeur: ' + element.user_firstname + ' ' + element.user_lastname ;;
            var n = new Notification("Nouvelle demande de validation " + element.type + ' ' + element.ref, {
                body: body,
                icon: DOL_URL_ROOT + '/theme/BimpTheme/img/favicon.ico'
            });
            
            n.onclick = function() {
                if(parseInt($('div[aria-labelledby="' + dvn.dropdown_id + '"]').attr('is_open')) !== 1)
                    $('#' + dvn.dropdown_id).trigger('click');
            }


        }
        
    }
    
    displayMultipleNotification(elements) {
        var nb_valid = elements.length;
        var dvn = this;
        
        if (window.Notification && Notification.permission === "granted") {
            
            var n = new Notification("Vous avez " + nb_valid + " demandes de validation en attente", {
                body: '',
                icon: DOL_URL_ROOT + '/theme/BimpTheme/img/favicon.ico'
            });
            
            n.onclick = function() {
                $('#' + dvn.dropdown_id).trigger('click');
            }
        }
    }
    
}