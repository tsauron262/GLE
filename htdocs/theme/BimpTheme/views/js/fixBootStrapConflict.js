/*****************************************************************************
* Agenda : résolution du conflit graçe à ce correctif 
* Voir ce lien pour plus d'infos : https://getbootstrap.com/docs/3.3/javascript/
******************************************************************************/

var bootstrapButton = $.fn.button.noConflict() // return $.fn.button to previously assigned value
$.fn.bootstrapBtn = bootstrapButton            // give $().bootstrapBtn the Bootstrap functionality