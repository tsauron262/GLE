<?php

class plaquette {

    public $pDb;
    public $filename;
    public $filesize;
    public $filemime ;
    public $fileurl;
    public $label;
    public $model = array();

    public function plaquette($pDb) {
        $this->db=$pDb;
    }

    public function fetch($id)
    {

          $requete = "SELECT * FROM ".MAIN_DB_PREFIX."ecm_document WHERE rowid = ".$id;
          $sql = $this->db->query($requete);
          $res = $this->db->fetch_object($sql);

          $requete1 = "SELECT * FROM Babel_Plaquette_label WHERE ecm_id = ".$id;
          $sql1 = $this->db->query($requete1);
          $res1 = $this->db->fetch_object($sql1);

          $filesize = $res->filesize;
          if ($filesize > 1024 * 1024)
          {
            $filesize = round(($filesize * 10)/(1024 * 1024))/10 . " Mo";
          } else if ($filesize > 1024)
          {
            $filesize = round(($filesize * 100)/(1024))/100 . " Ko";
          } else {
            $filesize = round(($filesize * 100)/(1024))/100 . " o";
          }
          $label = $res1->label;
          if ('x'.$label == 'x')
          {
            $label = false;
          }
          $this->id = $id;
          $this->filename = $res->filename;
          $this->filesize = $res->filesize;
          $this->filemime = $res->filemime;
          $this->fileurl = $res->fullpath_dol;
          $this->fullPath = $res->fullpath_orig;
          $this->label = $label;
          if ('x'.$this->label == "x")
          {
            $this->label = $this->filename;
          }

    }
    public $modelDet = array();
    public $modelId;
    public function fetch_model($id)
    {
        $this->modelId = $id;
        $requete = "SELECT * FROM Babel_Plaquette WHERE id = ".$id;
        $sql = $this->db->query($requete);
        while ($res = $this->db->fetch_object($sql))
        {
            $this->modelDet[$res->id]['label']=$res->label;
            $this->modelDet[$res->id]['content']=$res->content;
        }
    }
    public function listModel()
    {
        $requete = "SELECT * FROM Babel_Plaquette";
        $sql = $this->db->query($requete);
        while ($res = $this->db->fetch_object($sql))
        {
            $this->model[$res->id]=$this->label;
        }
    }

    public function convertText($pText,$contact_id=false,$arr=null)
    {
        $text = $pText;
        global $user, $mysoc;

        if ($contact_id)
        {
            require_once(DOL_DOCUMENT_ROOT."/contact/class/contact.class.php");
            $contact = new Contact();
            $contact->fetch($contact_id);
            $text = preg_replace("/##GENRE_DEST##/",$contact->civility_id,$text);
            $text = preg_replace("/##NOM_DEST##/",$contact->nom,$text);
            $text = preg_replace("/##PRENOM_DEST##/",$contact->prenom,$text);
            $text = preg_replace("/##EMAIL_DEST##/",$contact->email,$text);

        } else if ($arr)
        {
            $text = preg_replace("/##GENRE_DEST##/",$arr['genre'],$text);
            $text = preg_replace("/##NOM_DEST##/",$arr['nom'],$text);
            $text = preg_replace("/##PRENOM_DEST##/",$arr['prenom'],$text);
            $text = preg_replace("/##EMAIL_DEST##/",$arr['email'],$text);
        }

        $text = preg_replace("/##LABEL##/",$this->label,$text);
        $text = preg_replace("/##Date##/",date('d/m/Y'),$text);

        $text = preg_replace("/##MON_PRENOM##/",$user->firstname,$text);
        $text = preg_replace("/##MON_NOM##/",$user->lastname,$text);
        $text = preg_replace("/##MON_EMAIL##/",$user->email,$text);
        $text = preg_replace("/##MON_TELBUREAU##/",$user->office_phone,$text);
        $text = preg_replace("/##MON_FAX##/",$user->office_fax,$text);
        $text = preg_replace("/##MON_MOBILE##/",$user->user_mobile,$text);
        $text = preg_replace("/##MA_SOCIETE_EMAIL##/",$mysoc->email,$text);
        $text = preg_replace("/##MA_SOCIETE_TEL##/",$mysoc->phone,$text);
        $text = preg_replace("/##MA_SOCIETE_FAX##/",$mysoc->fax,$text);
        $text = preg_replace("/##MA_SOCIETE_NOM##/",$mysoc->nom,$text);

        return ($text);

    }
    public function prepareMail($modelId,$contact_id=false,$arr=null)
    {
        $this->fetch_model($modelId);
        $text = $this->convertText($this->modelDet[$modelId]['content'],$contact_id,$arr);
        return $text;

    }

    public function sendMail($subject,$to,$from,$model=0,$contact_id,$arr,$filename_list=array(),$mimetype_list=array(),$mimefilename_list=array(),$addr_cc='',$addr_bcc='',$deliveryreceipt=0,$msgishtml=1,$errors_to='')
    {
        global $mysoc;

        if (!$this->fk_soc > 0)
        {
            $this->fk_soc = $mysoc->id;
        }

          require_once(DOL_DOCUMENT_ROOT.'/core/class/CMailFile.class.php');

          global $langs, $user;
          if ($model == 0)
          {
                $model = $this->model;
          }

          $filename_list[]=$this->fullPath;
          $mimetype_list[]=$this->filemime;
          $mimefilename_list[]=$this->filename;

          $msg = $this->prepareMail($model,$contact_id,$arr);
          $subject = $this->convertText($subject,$contact_id,$arr);

          $mail = new CMailFile($subject,$to,$from,$msg,
                                $filename_list,$mimetype_list,$mimefilename_list,
                                $addr_cc,$addr_bcc,$deliveryreceipt,$msgishtml,$errors_to);

          $res = $mail->sendfile();
          $requete = "INSERT INTO Babel_societe_prop_history
                                (dateNote, note,
                                 source, importance,
                                 societe_refid, source_refid)
                         VALUES (now(),'Envoie de la plaquette ".$this->label."',
                                 'CRM - Plaquette',1,
                                 ".$this->fk_soc.",".$this->id.")";
          $sql = $this->db->query($requete);
          if ($res && $sql)
          {
              return (1);
          } else {
              return -1;
          }

    }
}
?>