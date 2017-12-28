<?php

require_once __DIR__.'/BimpModelPDF.php';

class PropalPDF extends BimpModelPDF
{
    public $mode = "normal";

        function initData()
	{
            $this->typeObject = "propal";
            $this->prefName = "loyer_";
            
            
            $this->text  = "<style>h1{text-align:center;}</style>";
            
            
            $this->text  .= "<h1>Gros titre</h1>";
            
            if(isset($this->object) && is_object($this->object)){
                $this->text  .= "<h2>Propal ".$this->object->ref."</h2>";
            }
            
            if($this->mode == "loyer")
                $this->text .= "<h3>En mode Loyer</h3>"; 
            
            $espace = "";
            for($i=0;$i<100;$i++){
                $espace .= " - ";
                $this->text .= "<br/>".$espace."Ligne n°".$i;
            }
            
            
	}
    
    
}
