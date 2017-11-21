$(window).on("load", function () {
    if ($("#idprof1").length > 0) 
    {
        var infoSoc = new InfoSoc($("#idprof1"));
    }
});



function InfoSoc(elem) {
    this.elem = elem;
    this.erreur = "";
    this.typeTier = $("#typent_id");
    
    
    
    

    this.insertValue = function(result) {
        $("#name").val(result.Nom);
        $("#address").val(result.Adresse);
        $("#capital").val(result.Capital);
        $("#zipcode").val(result.CodeP);
        $("#town").val(result.Ville);
        $("#intra_vat").val(result.Tva);
        $("#phone").val(result.Tel);
        $("#idprof3").val(result.Naf);
        $("#name_alias_input").val("Note : " + result.Note);   
    }
    
    this.init = function() {
        var moi = this;
        this.elem.change(function () {
            moi.checkData();
        });
        
        this.elem.parent().append("<input type='button' id='checkData' value='Vérifié'/>");
        
        $('#checkData').click(function(){
            moi.checkData();
        });
        
        var isParticulier = ($("#typent_id").length == 0 || this.typeTier.val() == 8);
        
        if(!isParticulier && (this.elem.val() == "" || !this.isSiren(this.elem.val()))){
            this.promptSiren(this.elem.val());
        }
    }
    
    
    this.promptSiren = function(textDef = "") {
        if(textDef == "")
            textDef = "SIREN";
        var siren = window.prompt("Numéro de SIREN ou p pour un PARTICULIER \n "+this.erreur, textDef);
        if(siren == null)
            siren = "";
        siren = siren.replace(" ", "").replace(" ", "").replace("	", "").replace("   ", "");
        if(siren == "PARTICULIER" || siren == "P" || siren == "p"){
            this.typeTier.val(8);
            return "";
        }
        if(!this.isSiren(siren) || siren == textDef){
            this.promptSiren(siren);
        }
        else{
            this.elem.val(siren);
            this.checkData();
        }
    }
    
    this.isSiren = function(siren) {
        this.erreur = "";
        if(siren == "" || siren == null){
            this.erreur = "SIREN VIDE";
            return false;
        }
        if(siren.length != 9){
            this.erreur = "UN SIREN COMPORTE 9 chiffres";
            return false;
        }
        if(siren != parseInt(siren)){
            this.erreur = "Un SIREN est composé de chiffres";
            return false;
        }
        return true;
    }
        

    this.checkData = function() {
        moi =this;
        var siren = this.elem.val();
        if(this.isSiren(siren)){
            datas = "siren=" + siren;
            jQuery.ajax({
                url: DOL_URL_ROOT + "/bimpcreditsafe/test.php",
                data: datas,
                datatype: "json",
                type: "POST",
                cache: false,
                success: function (msg) {
                    var result = JSON.parse(msg);
                    if (typeof result.Erreur !== "undefined")
                        alert("SIREN non reconnue par CREDIT SAFE");
                    else {
                        moi.insertValue(result);
                    }
                }
            });
        }
        else{
            alert("SIREN non conforme !! : "+siren+"  "+this.erreur);
        }
    };
    
    
    this.init();
}