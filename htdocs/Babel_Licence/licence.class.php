<?php

class licence {
    public $db;
    public $id;
    public $datec;
    public $datef;
    public $dater;
    public $label;
    public $note;
    public $active;


    public function licence($db) {
        $this->db = $db;
        global $langs;
        $langs->load("affaire");
        $this->labelstatut[0]=$langs->trans("licenceInactive");
        $this->labelstatut[1]=$langs->trans("licenceActive");
        $this->labelstatut[2]=$langs->trans("licenceActive");
        $this->labelstatut[3]=$langs->trans("licenceActive");
        $this->labelstatut[4]=$langs->trans("licenceActive");
        $this->labelstatut[5]=$langs->trans("licenceActive");
        $this->labelstatut_short=$this->labelstatut;
    }
    public function fetch($id)
    {
        $this->id = $id;
        $requete = "SELECT * FROM Babel_Licence WHERE id = ".$id;
        $sql = $this->db->query($requete);
        $res = $this->db->fetch_object($sql);
        $this->datec=strtotime($res->datec);
        $this->datef=strtotime($res->datef);
        $this->dater=strtotime($res->dater);
        $this->label=strtotime($res->label);
        $this->note=strtotime($res->note);
        $this->active=strtotime($res->active);
    }
    public function getLibStatut($mode=0)
    {
        return $this->LibStatut($this->statut,$mode);
    }
    private function LibStatut($statut=0,$mode=1)
    {
        global $langs;
        $langs->load("affaire");
        if ($mode == 0)
        {
            return $this->labelstatut[$statut];
        }
        if ($mode == 1)
        {
            return $this->labelstatut_short[$statut];
        }
        if ($mode == 2)
        {
            if ($statut==0) return img_picto($langs->trans('AffaireStatusDraftShort'),'statut0').' '.$this->labelstatut_short[$statut];
            if ($statut==1) return img_picto($langs->trans('AffaireStatusOpenedShort'),'statut3').' '.$this->labelstatut_short[$statut];
        }
        if ($mode == 3)
        {
            if ($statut==0) return img_picto($langs->trans('AffaireStatusDraftShort'),'statut0');
            if ($statut==1) return img_picto($langs->trans('AffaireStatusOpenedShort'),'statut3');
        }
        if ($mode == 4)
        {
            if ($statut==0) return img_picto($langs->trans('AffaireStatusDraft'),'statut0').' '.$this->labelstatut[$statut];
            if ($statut==1) return img_picto($langs->trans('AffaireStatusOpened'),'statut3').' '.$this->labelstatut[$statut];
        }
        if ($mode == 5)
        {
            if ($statut==0) return $this->labelstatut_short[$statut].' '.img_picto($langs->trans('AffaireStatusDraftShort'),'statut0');
            if ($statut==1) return $this->labelstatut_short[$statut].' '.img_picto($langs->trans('AffaireStatusOpenedShort'),'statut3');
        }
    }

}
?>