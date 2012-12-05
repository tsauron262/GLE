<?php
/*
  * GLE by Synopsis & DRSI
  *
  * Author: Tommy SAURON <tommy@drsi.fr>
  * Licence : Artistic Licence v2.0
  *
  * Version 1.1
  * Create on : 4-1-2009
  *
  * Infos on http://www.Synopsis-erp.com
  *
  */
/*
 * or see http://www.gnu.org/
 */

/**
 \file       htdocs/core/modules/contratGA/pdf_contratGA_finaproStd.modules.php
 \ingroup    contratGA
 \brief      Fichier de la classe permettant de generer les contratGAs au modele finaproStd
 \author        Laurent Destailleur
 \version    $Id: pdf_contratGA_finaproStd.modules.php,v 1.121 2008/08/07 07:47:38 eldy Exp $
 */

require_once(DOL_DOCUMENT_ROOT."/core/modules/contratGA/modules_contratGA.php");
require_once(DOL_DOCUMENT_ROOT."/product/class/product.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/company.lib.php");


/**
 \class      pdf_contratGA_finaproStd
 \brief      Classe permettant de generer les contratGAs au modele finaproStd
 */

class pdf_contratGA_finaproStd extends ModelePDFContratGA
{
    var $emetteur;    // Objet societe qui emet


    /**
    \brief      Constructeur
    \param        db        Handler acces base de donnee
    */
    function pdf_contratGA_finaproStd($db)
    {
        global $conf,$langs,$mysoc,$outputlangs;

        $outputlangs=$langs;
        $langs->load("main");
        $langs->load("bills");

        $this->db = $db;
        $this->name = "finaproStd";
        $this->libelle = "Finapro Standard";
        $this->description = $langs->trans('PDFcontratGAfinaproStdDescription');

        // Dimension page pour format A4
        $this->type = 'pdf';
        $this->page_largeur = 210;
        $this->page_hauteur = 297;
        $this->format = array($this->page_largeur,$this->page_hauteur);
        $this->marge_gauche=10;
        $this->marge_droite=10;
        $this->marge_haute=50;
        $this->marge_basse=10;

        $this->option_logo = 1;                    // Affiche logo

        // Recupere emmetteur
        $this->emetteur=$mysoc;
        if (! $this->emetteur->pays_code) $this->emetteur->pays_code=substr($langs->defaultlang,-2);    // Par defaut, si n'etait pas defini


    }

    /**
    \brief      Fonction generant la contratGA sur le disque
    \param        contratGA            Objet contratGA a generer (ou id si ancienne methode)
        \param        outputlangs        Lang object for output language
        \return        int             1=ok, 0=ko
        */
    function write_file($contratGA,$outputlangs='')
    {
        global $user,$langs,$conf;
        if (! is_object($outputlangs)) $outputlangs=$langs;
        $outputlangs->load("main");
        $outputlangs->load("dict");
        $outputlangs->load("companies");
        $outputlangs->load("bills");
        $outputlangs->load("contrat");
        $outputlangs->load("products");

        $outputlangs->setPhpLang();

        if ($conf->CONTRATGA->dir_output)
        {
            // Definition de l'objet $contratGA (pour compatibilite ascendante)
            $id = $contratGA;
            require_once(DOL_DOCUMENT_ROOT."/Babel_GA/ContratGA.class.php");
            $contratGA = new ContratGA($this->db);
            $ret=$contratGA->fetch($id);

            // Definition de $dir et $file
            if ($contratGA->specimen)
            {
                $dir = $conf->CONTRATGA->dir_output;
                $file = $dir . "/SPECIMEN.pdf";
            } else {
                $propref = sanitize_string($contratGA->ref);
                $dir = $conf->CONTRATGA->dir_output . "/".$contratGA->fk_user . $propref;
                $file = $dir ."/" . $propref . ".pdf";

            }

            if (! file_exists($dir))
            {
                if (dol_mkdir($dir) < 0)
                {
                    $this->error=$langs->trans("ErrorCanNotCreateDir",$dir);
                    return 0;
                }
            }

            if (file_exists($dir))
            {
                $nblignes = sizeof($contratGA->lignes);
                // Protection et encryption du pdf
                if ($conf->global->PDF_SECURITY_ENCRYPTION)
                {
                    $pdf=new FPDI_Protection('P','mm',$this->format);
                    $pdfrights = array('print'); // Ne permet que l'impression du document
                    $pdfuserpass = ''; // Mot de passe pour l'utilisateur final
                    $pdfownerpass = NULL; // Mot de passe du proprietaire, cree aleatoirement si pas defini
                    $pdf->SetProtection($pdfrights,$pdfuserpass,$pdfownerpass);
                } else  {
                    $pdf=new FPDI('P','mm',$this->format);
                }
                $pdf->Open();
//                $pdf->AddPage();

                $pdf->SetDrawColor(128,128,128);

                $pdf->SetTitle($contratGA->ref);
                $pdf->SetSubject($outputlangs->transnoentities("Contract"));
                $pdf->SetCreator("GLE ".GLE_VERSION);
                $pdf->SetAuthor($user->fullname);

                $pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite);   // Left, Top, Right
                $pdf->SetAutoPageBreak(0,0);


                $pdf->AddFont('Vera','','Vera.php');
                $pdf->AddFont('Vera','B','VeraBd.php');
                $pdf->AddFont('Vera','BI','VeraBI.php');
                $pdf->AddFont('Vera','I','VeraIt.php');

                $tmpSignature = new User($contratGA->db);
                $tmpSignature->id = $contratGA->commercial_signature_id;
                $tmpSignature->fetch();


                $pdf->SetDrawColor(128,128,128);

                $pdf->SetTitle($contratGA->ref);
                $pdf->SetSubject($outputlangs->transnoentities("Contract"));
                $pdf->SetCreator("GLE ".GLE_VERSION);
                $pdf->SetAuthor($user->fullname);

                $pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite);   // Left, Top, Right
                $pdf->SetAutoPageBreak(0,0);

                $this->page_conditionParticuliereSiemens(&$pdf,$contratGA,$tmpSignature);
                $this->page_conditionParticuliereSiemens_Maintenance(&$pdf,$contratGA,$tmpSignature);
                $this->page_CGV3(&$pdf,$contratGA,$tmpSignature);
                $this->page_CGV2(&$pdf,$contratGA,$tmpSignature);
                $this->page_CGV1(&$pdf,$contratGA,$tmpSignature);

                $this->page_conditionParticuliereBNP(&$pdf,$contratGA,$tmpSignature);
                $this->page_PV_reception(&$pdf,$contratGA,$tmpSignature);

                $this->page_presentation($pdf,$contratGA,$tmpSignature);

                $this->page_retourcontratGA($pdf,$contratGA,$tmpSignature);

                $this->page_cessioncontratGA($pdf,$contratGA,$tmpSignature);

                $this->page_conditionParticuliereBNP36_12($pdf,$contratGA,$tmpSignature);

                $pdf->AliasNbPages();
                $pdf->Close();
                $this->file = $file;$pdf->Output($file);


                $langs->setPhpLang();    // On restaure langue session


                return 1;   // Pas d'erreur
            } else {
                $this->error=$langs->trans("ErrorCanNotCreateDir",$dir);
                $langs->setPhpLang();    // On restaure langue session
                return 0;
            }
        } else {
            $this->error=$langs->trans("ErrorConstantNotDefined","CONTRATGA->dir_output");
            $langs->setPhpLang();    // On restaure langue session
            return 0;
        }

        $this->error=$langs->trans("ErrorUnknown");
        $langs->setPhpLang();    // On restaure langue session
        return 0;   // Erreur par defaut
    }
    private $iter =1;
    function page_CGV3(&$pdf,$contratGA,$tmpSignature)
    {
        global $outputlangs, $langs, $conf;
        $pdf->AddPage();

        $pdf->SetDrawColor(128,128,128);
        $pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite);   // Left, Top, Right
        $pagenb = 1;
        $tab_top = 48;
        $tab_top_newpage = 7;
        $tab_height = 50;
        $tab_height_newpage = 50;
        $tab_height_middlepage = 50;
        $iniY = $tab_top + 8;
        $curY = $tab_top + 8;
        $nexY = $tab_top + 8;

        $pdf->SetFillColor(255,255,255);
        $pdf->SetDrawColor(0,0,0);
        $pdf->SetTextColor(0,0,0);
        $pdf->SetFont('Vera','',7);
        $pdf->Ln(8);
        $pdf->SetAutoPageBreak(0,0);
        $pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite);   // Left, Top, Right

        $this->_pagehead3($pdf, $contratGA, 1);

        $pdf->SetY(40);
        $pdf->SetFont('Vera','',18);

        $html = 'CONDITIONS GENERALES';
        $curY = $pdf->GetY();
        $pdf->SetX($this->marge_gauche );
        $pdf->MultiCell($this->page_largeur - ($this->marge_gauche + $this->marge_droite),'6',utf8_decode($html),0,'C',0);

        $pdf->SetFont('Vera','',7);

        $html =          '<bi>Article 1</bi><br><br><strong>VALIDITE :</strong><br><br>Le présent contrat n'.chr(146).'a de validité que par la signature des parties. Il annule et remplace tout accord antérieur concernant le matériel désigné dans les conditions particulières . ci-annexées.<br>Toutes stipulations modifiant les clauses et conditions du présent contratGA seront considérées comme nulles et non avenues, à moins qu'.chr(146).'elles ne résultent d'.chr(146).'un avenant écrit et signé par les parties.';
        $html .= '<br><br><bi>Article 2</bi><br><br><strong>OBJET :</strong><br><br>Le présent contrat a pour objet la location du matériel ci-après dénommé « équipement » désigné dans les conditions particulières ci-annexées.';
        $html .= '<br><br><bi>Article 3</bi><br><br><strong>CHOIX DU MATERIEL :</strong><br><br>Le locataire choisit son équipement sous sa seule responsabilité auprès des fournisseurs de son choix.  Il ne pourra remettre en cause le présent contrat en cas de difficultés d'.chr(146).'utilisation ou de performance liées au choix du matériel.';
        $html .= '<br><br><bi>Article 4</bi><br><br><strong>LIVRAISON :</strong><br><br>Les frais de livraison et/ou d'.chr(146).'installation sont à la charge du locataire.<br>Le loueur n'.chr(146).'assume aucune responsabilité en cas de retard de livraison et/ou d'.chr(146).'installation imputable au fournisseur.<br>A réception du matériel et en cas de conformité et de parfait état, le locataire s'.chr(146).'engage à signer un procès-verbal de réception, qu'.chr(146).'il doit retourner dans un délai de 48 heures au loueur ou au cessionnaire.  La signature de ce procès verbal de réception  entraine la prise d'.chr(146).'effet du contrat et autorise le loueur ou le cessionnaire à payer le fournisseur.<br><br>En cas de livraison partielle, le locataire s'.chr(146).'engage à signer un Procès verbal de livraison et/ou d'.chr(146).'installation partielle constatant la conformité et le bon fonctionnement du matériel livré et/ou installé.<br><br>En cas de non-conformité ou de mauvais fonctionnement de l'.chr(146).'équipement livré, le locataire s'.chr(146).'engage dans un délai de 7 jours ouvrés, à informer le loueur par courrier stipulant son refus de signé le procès verbal de réception du matériel ainsi que les raisons de ce refus.';
        $html .= '<br><br><bi>Article 5</bi><br><br><strong>LIEU :</strong><br><br>Le locataire s'.chr(146).'engage à installer l'.chr(146).'équipement à l'.chr(146).'adresse indiquée dans les conditions particulières ci-annexées.  Le déplacement de l'.chr(146).'équipement en dehors de ces lieux est soumis à l'.chr(146).'autorisation du loueur. Les frais relatifs au transport, à l'.chr(146).'installation de l'.chr(146).'équipement reste à la charge du locataire.  Il souscrira une assurance couvrant les dommages occasionnés lors de ce déplacement.';
        $html .= '<br><br><bi>Article 6</bi><br><br><strong>DATE D'.chr(146).'EFFET :</strong><br><br>Le présent contrat prend effet à la réception et/ou à l'.chr(146).'installation totale de l'.chr(146).'équipement désigné dans les conditions particulières ci-annexées et fera l'.chr(146).'objet d'.chr(146).'un procès verbal de réception et/ou d'.chr(146).'installation signé par le locataire et remis en main propre ou retourné sous 48h au loueur.<br><br>Au cas où le Locataire ne retournerait pas l'.chr(146).'ensemble des documents contractuels au plus tard dans les 48 heures (le cachet de la poste faisant foi) après la livraison des produits commandés au fournisseur (dûment justifiée par ce dernier) une redevance de mise à disposition doit être  facturée prorata temporis jusqu'.chr(146).'à la date de prise d'.chr(146).'effet de la location. Elle doit être calculée sur la base des loyers prévus pour ces produits aux conditions particulières.';
        $pdf->lasth=4;
        $this->writeHTML($html, 4, 0,$pdf,true);
        $this->SetY($this->longueur_page - 20);
        $this->_pagefoot($pdf);
        $pdf->AddPage();
        $pdf->SetFont('Vera','',7);
        $nexY= 40;
        if ($this->iter == 1){ $this->iter = 0; $nexY = 10;} else { $this->iter = 1; $nexY = 40;}
        $this->_pagehead3($pdf, $contratGA, $this->iter);
        $pdf->SetFont('Vera','',7);
        $pdf->SetY($nexY);
        $html = '<br><br><bi>Article 7</bi><br><br><strong>DUREE :</strong><br><br>La durée de la location est irrévocable et fixée dans les conditions particulières ci-annexées.';
        $html .= '<br><br><bi>Article 8</bi><br><br><strong>PROPRIETE :</strong><br><br>Si les locaux dans lesquels est installé l'.chr(146).'équipement n'.chr(146).'est pas la propriété du locataire, ce dernier doit notifier au propriétaire de ces locaux que l'.chr(146).'équipement appartient au loueur.<br><br>Le loueur est le propriétaire exclusif de l'.chr(146).'équipement loué.<br><br>Le locataire est tenu de notifier aux créanciers nantis et hypothécaires qu'.chr(146).'il n'.chr(146).'est pas le propriétaire de l'.chr(146).'équipement faisant l'.chr(146).'objet de ce contrat, sous peine d'.chr(146).'engager sa responsabilité à l'.chr(146).'égard du loueur. Si une saisie a eu lieu, le locataire s'.chr(146).'oblige à supporter tous les frais et honoraires de la procédure de mainlevée.<br><br>Le locataire ne pourra ni sous-louer, ni prêter, ni céder, ni nantir, ni donner en gage l'.chr(146).'équipement faisant l'.chr(146).'objet de ce contrat sauf accord écrit et préalable du loueur.';
        $html .= '<br><br><bi>Article 9</bi><br><br><strong>LOYERS :</strong><br><br>Les loyers sont portables par tous les moyens du loueur et non quérables.<br><br>La location est consentie sous réserve du paiement des loyers par le locataire. Leurs montants et leurs périodicités sont précisées dans les conditions particulières ci-annexées. Ils restent fixes pendant toute la période de location sous réserve d'.chr(146).'une modification de la fiscalité liée à ce type de contrat.<br><br>Le premier loyer est exigible à la date d'.chr(146).'effet de la location. Si la prise d'.chr(146).'effet intervient après le premier du mois, un  loyer « « complémentaire » », calculé prorata temporis, est payable par chèque ou prélèvement automatique bancaire.<br><br>En cas de livraison et/ou installation partielle, une redevance de mise à disposition du matériel livré et/ou installé est facturée prorata temporis au fur et à mesure des livraisons, sur la base des loyers fixés aux conditions particulières.  Elle est payable par chèque ou prélèvement automatique bancaire.<br><br>Le locataire autorise le loueur à recouvrer le montant des loyers directement ou par l'.chr(146).'intermédiaire de tout mandataire de son choix. Pour cela, le locataire remet une autorisation de prélèvement permanent au profit du loueur ou de tout organisme  qui se substitue éventuellement au loueur.<br><br>Tout retard de paiement des loyers par le locataire entraînera, de plein droit et sans mise en demeure préalable une majoration de 1,5% HT par mois à compter de l'.chr(146).'échéance impayée jusqu'.chr(146).'à complet paiement sans que cette stipulation puisse permettre au Locataire de différer ou retarder le règlement sans préjudice du droit pour le bailleur de prononcer la résiliation du contrat de location, conformément à l'.chr(146).'article 15. Tous les frais afférents au recouvrement de ces sommes sont à l'.chr(146).'entière charge du locataire.';
        $html .= '<br><br><bi>Article 10</bi><br><br><strong>EXPLOITATION ET ENTRETIEN :</strong><br><br>Le locataire s'.chr(146).'engage à utiliser l'.chr(146).'équipement loué suivant les spécifications du constructeur.<br><br>Le locataire doit maintenir l'.chr(146).'équipement en bon état durant toute la durée du contrat et notamment prendre en charge les réparations ou remplacements de pièces usées. Le locataire s'.chr(146).'interdit de modifier l'.chr(146).'équipement loué sans l'.chr(146).'accord préalable du loueur. Toutes pièces ou éléments remplacés ou ajoutés en cours de location, deviennent de plein droit l'.chr(146).'entière propriété de loueur sans aucune compensation.<br><br>Le locataire s'.chr(146).'engage à souscrire, à ses frais, un contrat de maintenance auprès du constructeur ou d'.chr(146).'une société de maintenance notoirement reconnue.<br><br>Le locataire ne peut prétendre à aucune remise, prorogation ou diminution de loyer, ni à résiliation ou dommages et intérêts de la part du loueur en cas de non utilisation du matériel, pour quelque cause que ce soit, notamment détérioration, avaries, grève, arrêts nécessités par l'.chr(146).'entretien, les réparations et même dans le cas où matériel serait hors d'.chr(146).'usage pendant plus de 40 jours, par dérogation aux articles 1721 et 1724 du Code Civil.';
        $pdf->lasth=4;
        $this->writeHTML($html, 4, 0,$pdf,true);
        $this->SetY($this->longueur_page - 20);
        $this->_pagefoot($pdf);
        $pdf->SetFont('Vera','',7);
        $pdf->AddPage();
        $pdf->SetFont('Vera','',7);
        $nexY= 40;
        if ($this->iter == 1){ $this->iter = 0; $nexY = 10;} else { $this->iter = 1; $nexY = 40;}
        $this->_pagehead3($pdf, $contratGA, $this->iter);
        $pdf->SetFont('Vera','',7);
        $pdf->SetY($nexY);
        $html = '<bi>Article 11</bi><br><br><strong>EVOLUTION DE L'.chr(146).'EQUIPEMENT  :</strong><br><br>Pendant toute la durée du présent contrat, le locataire peut obtenir un ajout ou un remplacement de l'.chr(146).'équipement loué. Cette évolution sera matérialisée soit par avenant d'.chr(146).'ajout au présent contrat, soit par la signature d'.chr(146).'un nouveau contrat qui annulera et remplacera le présent contrat. Ce nouveau contrat sera soumis à l'.chr(146).'acceptation des deux parties.';
        $html .= '<br><br><bi>Article 12</bi><br><br><strong>RESPONSABILITE, ASSURANCE et DOMMAGES  :</strong><br><br>Pendant toute la durée de la location, le locataire est responsable de tout dommage pouvant affecter l'.chr(146).'équipement loué. Il devra donc souscrire, dès la date de livraison de l'.chr(146).'équipement, une police d'.chr(146).'assurance TOUS RISQUES et RESPONSABILITE CIVILE CHEF D'.chr(146).'ENTREPRISE couvrant tous les dommages ou vols subis par l'.chr(146).'équipement loué sans aucune restriction que ce soit ainsi que ceux que l'.chr(146).'équipement pourrait causés.<br><br>Le locataire s'.chr(146).'engage à prévenir par LRAR, sous 48h, le loueur du sinistre affectant l'.chr(146).'équipement faisant l'.chr(146).'objet du présent contrat, nonobstant toutes les déclarations d'.chr(146).'usage auprès de son assureur et des services de police et administratifs compétents.<br><br>En cas de sinistre total, le contrat de location est résilié de plein droit et le locataire doit verser au loueur, à titre forfaitaire, une indemnité de résiliation égale aux loyers restant dus jusqu'.chr(146).'à l'.chr(146).'issue de la période irrévocable de location de laquelle sera déduite ultérieurement l'.chr(146).'indemnité versée par la compagnie d'.chr(146).'assurances. Le locataire s'.chr(146).'engage à conclure un nouveau contrat aux mêmes conditions que celui résilié avec un équipement équivalent, cette nouvelle location prenant effet au jour de la livraison du nouveau matériel.<br><br>En cas de sinistre partiel, le présent contrat est poursuivi de plein droit et le locataire renonce expressément à toute indemnité ou droit à résiliation vis à vis du loueur pendant la durée nécessaire au remplacement du matériel. Dans cette hypothèse, si l'.chr(146).'indemnité reçue par la compagnie d'.chr(146).'assurances est insuffisante, le locataire est tenu de parfaire la remise en état complète du matériel à ses frais.<br><br>Dans tous les cas, la franchise éventuelle prévue par la police d'.chr(146).'assurance reste à l'.chr(146).'entière charge du locataire.';
        $html .= '<br><br><bi>Article 13</bi><br><br><strong>CESSION  ET TRANSFERT DE PROPRIETE DU MATERIEL :</strong><br><br>Le loueur se réserve la possibilité de céder le matériel objet du présent contrat en tout ou partie et de procéder à toute délégation au profit d'.chr(146).'une entreprise ou d'.chr(146).'un établissement financier, ci-après dénommé le cessionnaire. Ce dernier sera lié par les termes et conditions du présent contrat. Le locataire consent dès à présent et sans réserve, à une telle opération et s'.chr(146).'engage à signer, à la première demande du loueur, tous documents nécessaires à la régularisation juridique et administrative de l'.chr(146).'opération. Le loueur cède, par le présent acte, l'.chr(146).'équipement, objet de ce contrat, et sa créance de loyers. Cette cession se matérialisera par la signature du présent contrat par le cessionnaire.<br><br>Le locataire s'.chr(146).'engage à payer toute somme due en vertu des présentes, au cessionnaire de l'.chr(146).'opération, sans faire de compensation, de déduction ou de demande reconventionnelle en raison de droit de créances, d'.chr(146).'exceptions qu'.chr(146).'il pourrait faire valoir contre le Loueur.<br><br>Pendant toute la durée du présent contrat de location, le loueur conserve l'.chr(146).'exclusivité commerciale avec le locataire.';
        $html .= '<br><br><bi>Article 14</bi><br><br><strong>AUTONOMIE DU contrat, INOPPOSABILITE DES EXCEPTIONS, RECOURS :</strong><br><br>Les parties reconnaissent que le  matériel loué à un rapport direct avec l'.chr(146).'activité du locataire et que ce faisant le code de la consommation ne s'.chr(146).'applique pas .<br><br>De même le locataire reconnaît que le contrat de location est totalement indépendant du contrat de prestation qu'.chr(146).'il aurait éventuellement signé et que de ce fait il s'.chr(146).'interdit de refuser le paiement des loyers relatifs au contrat de location, et ce quand bien même la prestation ne serait pas réalisé correctement;';
        $pdf->lasth=4;
        $this->writeHTML($html, 4, 0,$pdf,true);
        $this->SetY($this->longueur_page - 20);
        $this->_pagefoot($pdf);
        $pdf->SetFont('Vera','',7);
        $pdf->AddPage();
        $pdf->SetFont('Vera','',7);
        $nexY= 40;
        if ($this->iter == 1){ $this->iter = 0; $nexY = 10;} else { $this->iter = 1; $nexY = 40;}
        $this->_pagehead3($pdf, $contratGA, $this->iter);
        $pdf->SetFont('Vera','',7);
        $pdf->SetY($nexY);
        $html = '<br><br>Par ailleurs en cas de défaillance du prestataire, il reconnaît qu'.chr(146).'il peut s'.chr(146).'adresser à tout autre prestataire de son choix , compte tenu de l'.chr(146).'absence de spécificité du matériel loué.<br><br>Les parties conviennent que, le locataire ayant choisi le fournisseur, l'.chr(146).'équipement, et ayant assuré la réception de celui-ci dans le cadre d'.chr(146).'un mandat assorti dune obligation de résultat, supportera seul le risque des carences ou défaillances de l'.chr(146).'un ou de l'.chr(146).'autre, et supportera seul la responsabilité de tout dommage subi par l'.chr(146).'équipement, même par cas fortuits ou de force majeure. Les loyers devront être réglés à bonne date, même au cas ou les équipements seraient atteints de vices cachés, seraient impropres à l'.chr(146).'usage auquel ils sont destinés, seraient détruits, ou ne pourraient être utilisés pour quelque cause que ce soit, y compris dans l'.chr(146).'hypothèse d'.chr(146).'un cas fortuit ou de force majeure. II en sera de même des logiciels ou plus généralement de tous les accessoires contractuellement prévus.<br><br>En contrepartie le locataire bénéficiera d'.chr(146).'un mandat d'.chr(146).'ester lui permettant d'.chr(146).'introduire à  l'.chr(146).'encontre du ou des fournisseurs, toutes actions qu'.chr(146).'il estimera opportunes, y compris l'.chr(146).'action en  résolution de vente et en réfaction de prix. Cette qualité de mandataire étant liée à la qualité de locataire, le mandat, par ailleurs révocable pour justes motifs, cessera en cas de résiliation du contrat, faute de paiement des loyers. II est convenu entre les parties que les loyers seront réglés à bonne date, même en cas d'.chr(146).'introduction d'.chr(146).'une instance contre le fournisseur et ce jusqu'.chr(146).'à ce que leurs relations financières soient liquidées. En toute hypothèse, le locataire garantit le loueur ou, le cessionnaire en cas de cession en application de l'.chr(146).'article 13 de tout préjudice et s'.chr(146).'oblige à le couvrir notamment de tous honoraires, frais, débours, même non répétibles, engagés à l'.chr(146).'occasion de sa représentation judiciaire ou amiable.<br><br>En cas de résolution de la vente, cette résolution entrainera de plein droit la résiliation du présent contrat et le paiement immédiat par le locataire au  loueur ou, au cessionnaire en cas de cession en application de l'.chr(146).'article 13 de l'.chr(146).'indemnité de résiliation. Cependant, le loueur ou, le cessionnaire en cas de cession en application de l'.chr(146).'article 13 imputera au paiement de cette indemnité les sommes effectivement reçues du fournisseur de l'.chr(146).'équipement en vertu d'.chr(146).'une décision judiciaire devenue définitive en restitution du prix au titre de la résolution de la vente. Dans tous les cas, le locataire sera garant du vendeur pour les sommes dues par celui-ci au titre de la résolution de la vente.';
        $html .= '<br><br><bi>Article 15</bi><br><br><strong>ANNULATION, RESILIATION ET PROLONGATION :</strong><br><br>En cas de demande d'.chr(146).'annulation du contrat par le locataire avant sa date d'.chr(146).'effet, le locataire sera redevable envers le loueur d'.chr(146).'une indemnité d'.chr(146).'annulation du contrat égale aux six premiers mois de loyer HT prévus au contrat, à titre de dommages et intérêts.<br><br>    L'.chr(146).'annulation du contrat ne sera reconnue effective qu'.chr(146).'après l'.chr(146).'acceptation par le fournisseur de l'.chr(146).'annulation de la commande et le règlement de  l'.chr(146).'indemnité définie ci-dessus.<br><br>    Le contrat de location pourra être résilié de plein droit par le loueur, sans qu'.chr(146).'il soit besoin de remplir aucune formalité judiciaire en cas de non paiement à échéance d'.chr(146).'un seul terme de loyer ou en cas d'.chr(146).'inexécution par le locataire d'.chr(146).'une seule des conditions générales ou particulières de location visées dans le présent contrat, et ce, 8 jours après une mise en demeure demeurée infructueuse. Les offres de payer ou d'.chr(146).'exécuter, ainsi que le paiement ou l'.chr(146).'exécution intervenus après expiration du délai susvisé ne remettent pas en cause l'.chr(146).'acquisition de la clause résolutoire.<br><br>Dans tous les cas de résiliation, le locataire devra :<br><br>-   Restituer l'.chr(146).'équipement dans les conditions visées ci-dessous.<br><br>-   Verser la totalité des loyers échus non payés et restant à courir à la date de résiliation. La somme ainsi obtenue est augmentée d'.chr(146).'une indemnité de retard égale à 20% du montant des loyers HT restant à courir au titre de ce présent contrat, à compter du jour de la résiliation. Elle est majorée des frais et honoraires éventuels rendus nécessaires pour en assurer le recouvrement.<br><br>Tous les frais occasionnés au loueur du fait de la résiliation du contrat ainsi que tous les frais afférents au démontage, emballage et transport des Produits en retour sont à la charge exclusive du locataire.<br><br>Le Locataire doit informer le loueur ou le cessionnaire en cas de cession en application de l'.chr(146).'article 13,  avec un préavis de trois mois, avant le terme de la période irrévocable de location par lettre recommandée avec accusé de réception , de son intention de ne pas poursuivre la location et donc de restituer les produits au terme de la période irrévocable. Dans le cas contraire, au-delà de la durée irrévocable , le contrat est prolongé aux mêmes conditions par tacite reconduction pour un an minimum.<br><br>    A l'.chr(146).'issue de cette année de reconduction, le locataire pourra  y mettre fin à tout moment avec un préavis de trois mois.';
        $pdf->lasth=4;
        $this->writeHTML($html, 4, 0,$pdf,true);
        $this->SetY($this->longueur_page - 20);
        $this->_pagefoot($pdf);
        $pdf->SetFont('Vera','',7);
        $pdf->AddPage();
        $pdf->SetFont('Vera','',7);
        $nexY= 40;
        if ($this->iter == 1){ $this->iter = 0; $nexY = 10;} else { $this->iter = 1; $nexY = 40;}
        $this->_pagehead3($pdf, $contratGA, $this->iter);
        $pdf->SetFont('Vera','',7);
        $pdf->SetY($nexY);
        $html = '<br><br><bi>Article 16</bi><br><br><strong>RESTITUTION DE L'.chr(146).'EQUIPEMENT :</strong><br><br>Dès la fin de la location, dès la résiliation anticipée de celle ci ou à l'.chr(146).'expiration de la tacite reconduction, le locataire doit restituer immédiatement au loueur et à l'.chr(146).'endroit désigné par celui ci, le matériel en bon état de propreté et de fonctionnement avec la documentation relative aux dits produits. Les frais de transport incombant dans tous les cas au locataire.<br><br>Le bailleur se réserve de déléguer toute personne susceptible de prendre possession du matériel en ses lieu et place Si pour quelque cause que ce soit, le locataire est dans l'.chr(146).'incapacité de restituer le matériel lorsqu'.chr(146).'il lui est réclamé par le loueur, il suffira pour l'.chr(146).'y contraindre d'.chr(146).'une ordonnance de référé ou d'.chr(146).'une requête et il devra régler au loueur une indemnité de jouissance journalière sur la base du dernier loyer, jusqu'.chr(146).'à la restitution effective.';
        $html .= '<br><br><bi>Article 17</bi><br><br><strong>VALIDITE, ELECTION DE DOMICILE ET COMPETENCE :</strong><br><br>Pour l'.chr(146).'exécution dudit contrat, les parties font élection de domicile au siège de leur société. Tout litige auquel peut donner lieu le présent contrat est de la compétence, à l'.chr(146).'égard des commerçants, du Tribunal de Commerce dans le ressort duquel le loueur, ou, en cas de cession, le cessionnaire, a son domicile.<br><br>Fait en trois exemplaires';

        $pdf->lasth=4;
        $this->writeHTML($html, 4, 0,$pdf,true);
        $this->SetY($this->longueur_page - 20);

        $pdf->lasth=4;
        $pdf->SetFont('Vera','B',7);
        $block1 = 'Pour le Locataire'."\n".'Signature et cachet (lu et approuvé)'."\n".'Qualité'."\n".'Nom';
        $block2 = 'Pour le Loueur'."\n".'Signature et cachet';
        $block3 = 'Pour le Cessionnaire'."\n".'Signature et cachet';
        $curY = $pdf->getY() + 5;
        $larg1 = ($this->page_largeur - ($this->marge_gauche + $this->marge_droite)) / 3;
        $pdf->SetY($pdf->getY()  + 5);
        $pdf->SetX($this->marge_gauche);
        $pdf->MultiCell($larg1,'4',utf8_decode($block1),0,"L",0);
        $pdf->SetY($curY);
        $pdf->SetX($this->marge_gauche + $larg1);
        $pdf->MultiCell($larg1,'4',utf8_decode($block2),0,"C",0);
        $pdf->SetY($curY);
        $pdf->SetX($this->marge_gauche + $larg1 * 2);
        $pdf->MultiCell($larg1,'4',utf8_decode($block3),0,"R",0);

        $pdf->SetAutoPageBreak(0,0);

        $pdf->SetFont('Vera','',7);
        // Pied de page
        $this->_pagefoot($pdf);


    }
    function page_CGV1(&$pdf,$contratGA,$tmpSignature)
    {
        global $outputlangs, $langs, $conf;
        $pdf->AddPage();

        $pdf->SetDrawColor(128,128,128);
        $pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite);   // Left, Top, Right
        $pagenb = 1;
        $tab_top = 48;
        $tab_top_newpage = 7;
        $tab_height = 50;
        $tab_height_newpage = 50;
        $tab_height_middlepage = 50;
        $iniY = $tab_top + 8;
        $curY = $tab_top + 8;
        $nexY = $tab_top + 8;

        $pdf->SetFillColor(255,255,255);
        $pdf->SetDrawColor(0,0,0);
        $pdf->SetTextColor(0,0,0);
        $pdf->SetFont('Vera','',7);
        $pdf->Ln(8);
        $this->_pagehead3($pdf, $contratGA, 1);

        $pdf->SetFont('Vera','',10);
        $pdf->SetY(40);
        $html = 'Feuillet A : CONDITIONS GENERALES DU CONTRAT DE LOCATION N°';

        $tmpWidth = $pdf->GetStringWidth($html . " ".$contratGA->ref );
        $tmpWidth1 = $pdf->GetStringWidth($html);
        $curY = $pdf->GetY();
        $pdf->SetX($this->page_largeur / 2 - $tmpWidth / 2   );
        $pdf->MultiCell($tmpWidth1,'6',utf8_decode($html),0,'R',0);
        $pdf->SetTextColor(38,0,255);
        $pdf->SetY($curY);
        $pdf->SetX($this->page_largeur / 2 + $tmpWidth / 2  - ($tmpWidth - $tmpWidth1) - 5);
        $pdf->MultiCell($tmpWidth - $tmpWidth1 + 5,'6',utf8_decode($contratGA->ref),0,'R',0);

        $pdf->SetTextColor(0,0,0);

        $pdf->SetFont('Vera','',5);

        $ptFond = 3;
        $larg1 = $this->page_largeur - ($this->marge_gauche + $this->marge_droite) - 3 * $ptFond;
        $larg1 /= 3;
        $curY=$pdf->GetY() + 10;
        $pdf->SetY($pdf->GetY()+10);
        $pdf->SetX($this->marge_gauche);
        $col1head='Art1 - VALIDITE';
        $col1 =  'Le présent contrat n'.chr(146).'a de validité que par la signature des parties. Il annule et remplace tout accord antérieur concernant l'.chr(146).'équipement. Toutes stipulations modifiant les clauses et conditions du présent contrat seront considérées comme nulles et non avenues, à moins qu'.chr(146).'elles ne résultent d'.chr(146).'un avenant écrit et signé par les parties. En cas de financement d'.chr(146).'exemplaire de logiciel le locataire s'.chr(146).'engage à utiliser celui qui sera mis à disposition dans le cadre du présent contrat dans le respect de la licence d'.chr(146).'utilisation qu'.chr(146).'il aura régularisée(s) avec le fournisseur et/ ou l'.chr(146).'éditeur. Le locataire ne peut prétendre à aucune remise, prorogation ou diminution de loyer, ni à résiliation ou à dommages et intérêts de la part du loueur  en cas de manquement à une  disposition de la licence et ce même si cela conduit à une interdiction d'.chr(146).'utilisation du logiciel. Entre le loueur et le locataire, les dispositions du présent contrat prévalent sur celles qui régissent ou constituent la licence.';
        $col1ahead = 'Art2 - CHOIX - LIVRAISON DE L'.chr(146).'EQUIPEMENT';
        $col1a = 'Le locataire choisit son équipement sous sa seule responsabilité auprès des fournisseurs de son choix.  Il ne pourra remettre en cause le présent contrat en cas de difficultés d'.chr(146).'utilisation ou de performance liées au choix de l'.chr(146).'équipement. Les frais de livraison et/ou d'.chr(146).'installation sont à la charge du locataire. Le loueur n'.chr(146).'assume aucune responsabilité en cas de retard de livraison et/ou d'.chr(146).'installation imputable au fournisseur. A réception de l'.chr(146).'équipement  et en cas de conformité et de parfait état, le locataire s'.chr(146).'engage à signer un procès-verbal de réception et/ou d'.chr(146).'installation qu'.chr(146).'il doit remettre en main propre ou retourner dans un délai de 48 heures au loueur.  En cas de livraison partielle, le locataire s'.chr(146).'engage à signer un Procès verbal de livraison et/ou d'.chr(146).'installation partielle constatant la conformité et le bon fonctionnement de l'.chr(146).'équipement livré et/ou installé. En cas de non-conformité ou de mauvais fonctionnement de l'.chr(146).'équipement livré, le locataire s'.chr(146).'engage dans un délai de 7 jours ouvrés, à informer le loueur par courrier stipulant son refus de signer  le procès verbal de réception de l'.chr(146).'équipement ainsi que les raisons de ce refus.';
        $col1bhead = 'Art3 - DATE D'.chr(146).'EFFET';
        $col1b = 'Le présent contrat prend effet à la réception et/ou à l'.chr(146).'installation totale de l'.chr(146).'équipement. Au cas où le locataire ne retournerait pas l'.chr(146).'ensemble des documents contractuels au plus tard dans les 48 heures (le cachet de la poste faisant foi) après la livraison de l'.chr(146).'équipement commandé au fournisseur (dûment justifiée par ce dernier) une redevance de mise à disposition doit être  facturée prorata temporis jusqu'.chr(146).'à la date de prise d'.chr(146).'effet de la location. Elle doit être calculée sur la base des loyers prévus pour ces produits aux conditions particulières.';
        $col1chead = 'Art4 - LIEU';
        $col1c = 'Le locataire s'.chr(146).'engage à installer l'.chr(146).'équipement à l'.chr(146).'adresse indiquée dans les conditions particulières.  Le déplacement de l'.chr(146).'équipement en dehors de ces lieux est soumis à l'.chr(146).'autorisation du loueur. Les frais relatifs au transport, à l'.chr(146).'installation de l'.chr(146).'équipement reste à la charge du locataire.  Il souscrira une assurance couvrant les dommages occasionnés lors de ce déplacement.  Si les locaux dans lesquels est installé l'.chr(146).'équipement n'.chr(146).'est pas la propriété du locataire, ce dernier doit notifier au propriétaire de ces locaux que l'.chr(146).'équipement appartient au loueur. Le locataire est tenu de notifier aux créanciers nantis et hypothécaires qu'.chr(146).'il n'.chr(146).'est pas le propriétaire de l'.chr(146).'équipement faisant l'.chr(146).'objet de ce contrat, sous peine d'.chr(146).'engager sa responsabilité à l'.chr(146).'égard du loueur. Si une saisie a eu lieu, le locataire s'.chr(146).'oblige à supporter tous les frais et honoraires de la procédure de mainlevée. Le locataire ne pourra ni sous-louer, ni prêter, ni céder, ni nantir, ni donner en gage l'.chr(146).'équipement faisant l'.chr(146).'objet de ce contrat sauf accord écrit et préalable du loueur.';
        $col1dhead = 'Art5 - LOYERS';
        $col1d = 'Les loyers sont portables par tous les moyens du loueur et non quérables et sont dus pour la durée de la location irrévocable fixée dans les conditions particulières. La location est consentie sous réserve du paiement des loyers par le locataire. Leurs montants et leurs périodicités sont précisées dans les conditions particulières visées ci-dessus. Les loyers prévus au ';

        $pdf->SetFont('Vera','B',5);
        $pdf->MultiCell($larg1,'3',utf8_decode($col1head),0,'J',0);
        $pdf->SetFont('Vera','',5);
        $pdf->MultiCell($larg1,'3',utf8_decode($col1),0,'J',0);
        $pdf->SetFont('Vera','B',5);
        $pdf->MultiCell($larg1,'3',utf8_decode($col1ahead),0,'J',0);
        $pdf->SetFont('Vera','',5);
        $pdf->MultiCell($larg1,'3',utf8_decode($col1a),0,'J',0);
        $pdf->SetFont('Vera','B',5);
        $pdf->MultiCell($larg1,'3',utf8_decode($col1bhead),0,'J',0);
        $pdf->SetFont('Vera','',5);
        $pdf->MultiCell($larg1,'3',utf8_decode($col1b),0,'J',0);
        $pdf->SetFont('Vera','B',5);
        $pdf->MultiCell($larg1,'3',utf8_decode($col1chead),0,'J',0);
        $pdf->SetFont('Vera','',5);
        $pdf->MultiCell($larg1,'3',utf8_decode($col1c),0,'J',0);
        $pdf->SetFont('Vera','B',5);
        $pdf->MultiCell($larg1,'3',utf8_decode($col1dhead),0,'J',0);
        $pdf->SetFont('Vera','',5);
        $pdf->MultiCell($larg1,'3',utf8_decode($col1d),0,'J',0);
        $pdf->SetY($curY);

        $col2 = 'contrat pourront être révisés par le loueur au moment de la prise d'.chr(146).'effet du contrat, en cas d'.chr(146).'évolution du taux de référence suivant : moyenne des derniers taux connus et publiés au jour du contrat de l'.chr(146).'Euribor 12 mois et du TEC 5. (Euribor 12 mois : Taux Interbancaire Offert en Euro publié quotidiennement par la Fédération Bancaire de l'.chr(146).'Union Européenne et TEC 5 : Taux des Echéances constantes à 5 ans, publiés quotidiennement par la Caisse des Dépôts et Consignations.)Entre le jour de l'.chr(146).'accord de financement et le jour de la livraison. Lors de la mise en loyer ils restent fixes pendant toute la période de location sous réserve d'.chr(146).'une modification de la fiscalité liée à ce type de contrat. Sauf dispositions contraires prévues aux conditions particulières, le paiement de toutes les sommes dues au titre du présent contrat, pour quelque raison que ce soit, s'.chr(146).'effectue par prélèvement automatique permanent sur le compte bancaire du locataire au jour d'.chr(146).'échéance ou en cas d'.chr(146).'impossibilité au jour ouvré précédent Le premier loyer est exigible à la date d'.chr(146).'effet de la location. Si la prise d'.chr(146).'effet intervient après le premier du mois, un  loyer  complémentaire, calculé prorata temporis, est payable par chèque ou prélèvement automatique bancaire. Au cas où le locataire ne serait pas assujetti à la taxe professionnelle, les loyers pourront être majorés de l'.chr(146).'incidence de cette taxe. En cas de livraison et/ou installation partielle, une redevance de mise à disposition de l'.chr(146).'équipement livré et/ou installé est facturée prorata temporis au fur et à mesure des livraisons, sur la base des loyers fixés aux conditions particulières.  Elle est payable par chèque ou prélèvement automatique bancaire. Le locataire autorise le loueur à recouvrer le montant des loyers directement ou par l'.chr(146).'intermédiaire de tout mandataire de son choix. Pour cela, le locataire remet une autorisation de prélèvement permanent au profit du loueur ou de tout organisme  qui se substitue éventuellement au loueur. Tout retard de paiement d'.chr(146).'une  somme due au loueur par le locataire entraînera, de plein droit et sans mise en demeure préalable l'.chr(146).'application d'.chr(146).'intérêts de retard à un taux fixé à trois fois le taux d'.chr(146).'intérêt légal en vigueur et ce à compter de l'.chr(146).'échéance impayée jusqu'.chr(146).'à complet paiement sans que cette stipulation puisse permettre au locataire de différer ou retarder le règlement sans préjudice du droit pour le loueur de prononcer la résiliation du présent contrat. Tous les frais afférents au recouvrement de ces sommes sont à l'.chr(146).'entière charge du locataire.';
        $col2ahead = 'Art6 - EXPLOITATION ET ENTRETIEN';
        $col2a = 'Le locataire s'.chr(146).'engage à utiliser l'.chr(146).'équipement loué suivant les spécifications du constructeur. Le locataire doit maintenir l'.chr(146).'équipement en bon état durant toute la durée du présent  contrat et notamment prendre en charge les réparations ou remplacements de pièces usées. Le locataire s'.chr(146).'interdit de modifier l'.chr(146).'équipement loué sans l'.chr(146).'accord préalable du loueur. Toutes pièces ou éléments remplacés ou ajoutés en cours de location, deviennent de plein droit l'.chr(146).'entière propriété de loueur sans aucune compensation. Le locataire assume l'.chr(146).'entière responsabilité de l'.chr(146).'usage fait de l'.chr(146).'équipement loué et de sa mise en service, muni des documents, inscriptions et équipements requis par la réglementation en vigueur. D'.chr(146).'une manière générale, le locataire doit remplir toutes obligations administratives et fiscales et se conformer, en toutes circonstances, aux lois et règlements afférents à la détention et à l'.chr(146).'utilisation de l'.chr(146).'équipement loué. Pendant toute la durée de la location, le locataire a également la charge de l'.chr(146).'entretien, de la maintenance et des réparations de l'.chr(146).'équipement loué de manière à en assurer constamment le bon état général et de fonctionnement. Il prend à sa charge tous les coûts qui peuvent résulter de l'.chr(146).'obligation de mettre en conformité l'.chr(146).'équipement auxdites réglementations, que cette obligation incombe au loueur ou au locataire. Le loueur peut procéder ou faire procéder à toute inspection de l'.chr(146).'équipement et vérification de son fonctionnement. Le locataire ne peut prétendre à aucune remise, prorogation ou diminution de loyer, ni à résiliation ou à dommages et';

        $pdf->SetFont('Vera','',5);
        $pdf->SetX($larg1 + $this->marge_gauche + $ptFond);
        $pdf->MultiCell($larg1,'3',utf8_decode($col2),0,'J',0);
        $pdf->SetFont('Vera','B',5);
        $pdf->SetX($larg1 + $this->marge_gauche + $ptFond);
        $pdf->MultiCell($larg1,'3',utf8_decode($col2ahead),0,'J',0);
        $pdf->SetX($larg1 + $this->marge_gauche + $ptFond);
        $pdf->SetFont('Vera','',5);
        $pdf->MultiCell($larg1,'3',utf8_decode($col2a),0,'J',0);
        $pdf->SetY($curY);
        $pdf->SetX($larg1 * 2 + $this->marge_gauche + $ptFond * 2);

        $col3 = 'intérêts de la part du loueur en cas de défaut de rendement ou d'.chr(146).'insuffisance technique de l'.chr(146).'équipement, ainsi qu'.chr(146).'en cas de non utilisation de l'.chr(146).'équipement, pour quelque cause que ce soit. Il doit accomplir aux lieu et place du loueur toute formalité imposée aux propriétaires ou aux utilisateurs de l'.chr(146).'équipement, le loueur lui donnant en tant que de besoin mandat à cet effet. Le locataire assure le financement et l'.chr(146).'organisation de l'.chr(146).'élimination des déchets issus de l'.chr(146).'équipement et prend en charge les taxes afférentes. Toute disposition contraire est inopposable au loueur. Le locataire est seul responsable des déclarations et paiement de tous droits, taxes et redevances concernant l'.chr(146).'équipement. Le locataire s'.chr(146).'engage à souscrire, à ses frais, un contrat de maintenance auprès du constructeur ou d'.chr(146).'une société de maintenance notoirement reconnue.';
        $col3aheader = ' Art7 - RESPONSABILITE, ASSURANCE';
        $col3a = 'Dès sa mise à disposition et jusqu'.chr(146).'à la restitution effective de celui-ci, et tant que l'.chr(146).'équipement reste sous sa garde, le locataire assume tous les risques de détérioration et de perte, même par cas fortuit ; il est responsable de tout dommage causé par l'.chr(146).'équipement dans toutes circonstances. Il s'.chr(146).'oblige en conséquence à souscrire une assurance couvrant sa responsabilité civile ainsi que celle du loueur. Il s'.chr(146).'engage de même à souscrire une (ou des) assurance(s) couvrant tous les risques de dommages ou de vol subis par l'.chr(146).'équipement loué avec une clause de délégation d'.chr(146).'indemnités au profit du loueur et une clause de renonciation aux recours contre ce dernier Le locataire s'.chr(146).'engage à prévenir par LRAR, sous 48h, le loueur du sinistre affectant l'.chr(146).'équipement, nonobstant toutes les déclarations d'.chr(146).'usage auprès de son assureur et des services de police et administratifs compétents. En cas de sinistre total, le présent contrat est résilié de plein droit et le locataire doit verser au loueur, à titre forfaitaire, une indemnité de résiliation calculée et exigible à la date de résiliation  Elle est égale aux loyers restant dus jusqu'.chr(146).'à l'.chr(146).'issue de la période irrévocable de location augmentée de la valeur estimée de l'.chr(146).'équipement détruit ou volé au terme de cette période de laquelle sera déduite ultérieurement l'.chr(146).'indemnité versée par la compagnie d'.chr(146).'assurances. En cas de sinistre partiel, le présent contrat est poursuivi de plein droit et le locataire renonce expressément à toute indemnité ou droit à résiliation vis à vis du loueur pendant la durée nécessaire au remplacement de l'.chr(146).'équipement. Dans cette hypothèse, si l'.chr(146).'indemnité reçue par la compagnie d'.chr(146).'assurances est insuffisante, le locataire est tenu de parfaire la remise en état complète de l'.chr(146).'équipement  à ses frais. Dans tous les cas, la franchise éventuelle prévue par la police d'.chr(146).'assurance reste à l'.chr(146).'entière charge du locataire.';
        $col3bheader = 'Art8 - CESSION';
        $col3b = 'Le loueur  se réserve expressément la faculté de céder l'.chr(146).'équipement et de déléguer le présent contrat à un  cessionnaire de son choix. Ce dernier, intervenant à titre purement financier, ne prendra en charge que l'.chr(146).'obligation de laisser au locataire la jouissance paisible de l'.chr(146).'équipement. En conséquence malgré cette cession le suivi commercial et technique  continuera à être assuré par le loueur d'.chr(146).'origine  qui reste dès lors l'.chr(146).'interlocuteur du locataire. Le présent acte sera à cet effet soumis par le loueur d'.chr(146).'origine à l'.chr(146).'acceptation et à la signature du cessionnaire. Le cessionnaire ne sera engagé qu'.chr(146).'après acceptation du dossier matérialisée par sa signature du présent contrat. Jusqu'.chr(146).'à l'.chr(146).'apposition de cette signature il n'.chr(146).'existe aucun engagement du cessionnaire. Sauf disposition spécifique contraire portées aux conditions particulières,  la cession porte sur tous les loyers dus à compter de la date de mise en loyer définitive. Le locataire accepte dès à présent et sans réserve cette substitution éventuelle de loueur et s'.chr(146).'engage à signer à première demande une autorisation de prélèvement au nom du cessionnaire. En cas d'.chr(146).'acceptation par  le cessionnaire  qui se substitue ainsi au loueur d'.chr(146).'origine, le locataire reconnaît donc comme loueur  le cessionnaire  et s'.chr(146).'engage notamment à lui ';


        $pdf->SetFont('Vera','',5);
        $pdf->MultiCell($larg1,'3',utf8_decode($col3),0,'J',0);
        $pdf->SetFont('Vera','B',5);
        $pdf->SetX($larg1 * 2 + $this->marge_gauche + $ptFond * 2);
        $pdf->MultiCell($larg1,'3',utf8_decode($col3aheader),0,'J',0);
        $pdf->SetFont('Vera','',5);
        $pdf->SetX($larg1 * 2 + $this->marge_gauche + $ptFond * 2);
        $pdf->MultiCell($larg1,'3',utf8_decode($col3a),0,'J',0);
        $pdf->SetFont('Vera','B',5);
        $pdf->SetX($larg1 * 2 + $this->marge_gauche + $ptFond * 2);
        $pdf->MultiCell($larg1,'3',utf8_decode($col3bheader),0,'J',0);
        $pdf->SetFont('Vera','',5);
        $pdf->SetX($larg1 * 2 + $this->marge_gauche + $ptFond * 2);
        $pdf->MultiCell($larg1,'3',utf8_decode($col3b),0,'J',0);


        $pdf->SetFont('Vera','',7);
        // Pied de page
        $this->_pagefoot($pdf);

    }

    function page_CGV2(&$pdf,$contratGA,$tmpSignature)
    {
        global $outputlangs, $langs, $conf;
        $pdf->AddPage();

        $pdf->SetDrawColor(128,128,128);
        $pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite);   // Left, Top, Right
        $pagenb = 1;
        $tab_top = 48;
        $tab_top_newpage = 7;
        $tab_height = 50;
        $tab_height_newpage = 50;
        $tab_height_middlepage = 50;
        $iniY = $tab_top + 8;
        $curY = $tab_top + 8;
        $nexY = $tab_top + 8;

        $pdf->SetFillColor(255,255,255);
        $pdf->SetDrawColor(0,0,0);
        $pdf->SetTextColor(0,0,0);
        $pdf->SetFont('Vera','',7);
        $pdf->Ln(8);

        $pdf->SetFont('Vera','',10);
        $pdf->SetY(10);
        $html = 'Feuillet B : CONDITIONS GENERALES DU CONTRAT DE LOCATION N°';

        $tmpWidth = $pdf->GetStringWidth($html . " ".$contratGA->ref );
        $tmpWidth1 = $pdf->GetStringWidth($html);
        $curY = $pdf->GetY();
        $pdf->SetX($this->page_largeur / 2 - $tmpWidth / 2   );
        $pdf->MultiCell($tmpWidth1,'6',utf8_decode($html),0,'R',0);
        $pdf->SetTextColor(38,0,255);
        $pdf->SetY($curY);
        $pdf->SetX($this->page_largeur / 2 + $tmpWidth / 2  - ($tmpWidth - $tmpWidth1) - 5);
        $pdf->MultiCell($tmpWidth - $tmpWidth1 + 5,'6',utf8_decode($contratGA->ref),0,'R',0);

        $pdf->SetTextColor(0,0,0);

        $pdf->SetFont('Vera','',5);

        $ptFond = 3;
        $larg1 = $this->page_largeur - ($this->marge_gauche + $this->marge_droite) - 3 * $ptFond;
        $larg1 /= 3;
        $curY=$pdf->GetY() + 10;
        $pdf->SetY($pdf->GetY()+10);
        $pdf->SetX($this->marge_gauche);
        $col1 =  'verser directement ou à son ordre la totalité des loyers en principal, intérêts et accessoires. Le cessionnaire  intervenant à titre purement financier, le locataire en acceptant cette intervention renonce à effectuer toute compensation, déduction, demande reconventionnelle en raison du droit qu'.chr(146).'il pourrait faire valoir à l'.chr(146).'encontre du loueur d'.chr(146).'origine, ainsi qu'.chr(146).'à tout recours contre le cessionnaire du fait notamment de défaillance ou vice caché ou du fait de l'.chr(146).'assurance, prestations de services, construction,  livraison ou l'.chr(146).'installation de l'.chr(146).'équipement, le locataire conservant sur ces points tous les recours contre le fournisseur et le loueur  d'.chr(146).'origine. Si une action aboutit à une résolution judiciaire de la vente, objet du présent contrat, celui-ci est résilié à compter du jour où cette résolution sera devenue définitive. Le locataire est alors redevable, outre des loyers impayés à cette date, d'.chr(146).'une indemnité de résiliation égale au montant de l'.chr(146).'investissement réalisé par le cessionnaire augmentée d'.chr(146).'une somme forfaitaire égale à 5 % du montant total des loyers prévus aux conditions particulières. L'.chr(146).'indemnité est exigible au jour de la résiliation. Le cessionnaire  imputera au paiement de cette indemnité les sommes effectivement reçues notamment du fournisseur de l'.chr(146).'équipement en restitution du prix au titre de la résolution de la vente et ce, dans la limite du montant de l'.chr(146).'indemnité. En outre, le locataire reste garant solidaire avec le loueur d'.chr(146).'origine, le fournisseur ou le constructeur pour les sommes dues par ceux-ci au cessionnaire A défaut d'.chr(146).'avoir adressé au plus tard dans les huit jours de la livraison de l'.chr(146).'équipement  la justification des assurances couvrant les risques de perte et dommage à l'.chr(146).'équipement  souscrites directement par ses soins, le locataire demande au cessionnaire d'.chr(146).'adhérer pour son compte à son contrat d'.chr(146).'assurance collective couvrant le risque de perte et de dommage. S'.chr(146).'il procède à cette adhésion, le cessionnaire en informera par tout moyen le locataire et lui en communiquera les conditions notamment financières. Le locataire pourra renoncer à cette adhésion jusqu'.chr(146).'au 30ème jour suivant le règlement de la première prime d'.chr(146).'assurance, par LRAR au service Qualité du cessionnaire accompagnée de tout document attestant de la bonne couverture d'.chr(146).'assurance dudit équipement, les primes échues restant dues. Le loueur d'.chr(146).'origine  et le locataire déclarent, sous leur responsabilité : -que pour la location de l'.chr(146).'équipement il n'.chr(146).'existe aucun autre document ou convention  que ceux signés par le cessionnaire  En tout état de cause seuls seront opposables à ce dernier les documents ou convention signés par lui.    -que l'.chr(146).'équipement est  conforme aux lois, règlements, au choix du locataire, qu'.chr(146).'il bénéficie de toutes les garanties légales ou conventionnelles et qu'.chr(146).'ils peuvent le céder et /ou concéder les droits y attachés sans restriction ni réserve. Dans l'.chr(146).'éventualité où le cessionnaire vend l'.chr(146).'équipement à un acheteur, le présent contrat lui est simultanément délégué. Toutefois, le cessionnaire pourra facturer, pour le compte de l'.chr(146).'acheteur, les sommes dues par le locataire. ';
        $col1ahead = 'Art9 - AUTONOMIE DU CONTRAT, INOPPOSABILITE DES EXCEPTIONS, RECOURS ';
        $col1a = 'Les parties reconnaissent que l'.chr(146).'équipement loué à un rapport direct avec l'.chr(146).'activité du locataire.Par commodité de gestion le loueur peut facturer des prestations pour compte de tiers en même temps que ses loyers. Dans ce cas le locataire reconnaît que le contrat de location est totalement indépendant du contrat de prestation qu'.chr(146).'il aurait éventuellement signé et que de ce fait il s'.chr(146).'interdit de refuser le paiement des loyers relatifs au contrat de location, et ce quand bien même la prestation ne serait pas réalisé correctement. Par ailleurs en cas de défaillance du prestataire, il reconnaît qu'.chr(146).'il peut s'.chr(146).'adresser à tout autre prestataire de son choix, compte tenu de l'.chr(146).'absence de spécificité de l'.chr(146).'équipement loué. Les parties conviennent que, le locataire ayant choisi le fournisseur, l'.chr(146).'équipement, et ayant assuré la réception ';

        $pdf->SetFont('Vera','',5);
        $pdf->MultiCell($larg1,'3',utf8_decode($col1),0,'J',0);
        $nexY=($pdf->GetY()>$nexY?$pdf->GetY():$nexY);
        $pdf->SetFont('Vera','B',5);
        $pdf->MultiCell($larg1,'3',utf8_decode($col1ahead),0,'J',0);
        $nexY=($pdf->GetY()>$nexY?$pdf->GetY():$nexY);
        $pdf->SetFont('Vera','',5);
        $pdf->MultiCell($larg1,'3',utf8_decode($col1a),0,'J',0);
        $nexY=($pdf->GetY()>$nexY?$pdf->GetY():$nexY);
        $pdf->SetY($curY);

        $col2 = 'de celui-ci dans le cadre d'.chr(146).'un mandat assorti dune obligation de résultat, supportera seul le risque des carences ou défaillances de l'.chr(146).'un ou de l'.chr(146).'autre, et supportera seul la responsabilité de tout dommage subi par l'.chr(146).'équipement, même par cas fortuits ou de force majeure. Les loyers devront être réglés à bonne date, même au cas ou l'.chr(146).'équipement serait atteint de vices cachés, serait impropre à l'.chr(146).'usage auquel il est destiné, serait détruit, ou ne pourrait être utilisé pour quelque cause que ce soit, y compris dans l'.chr(146).'hypothèse d'.chr(146).'un cas fortuit ou de force majeure. II en sera de même des logiciels ou plus généralement de tous les accessoires contractuellement prévus. En contrepartie le locataire bénéficiera d'.chr(146).'un mandat d'.chr(146).'ester lui permettant d'.chr(146).'introduire à  l'.chr(146).'encontre du ou des fournisseurs, toutes actions qu'.chr(146).'il estimera opportunes, y compris l'.chr(146).'action en  résolution de vente et en réfaction de prix. Cette qualité de mandataire étant liée à la qualité de locataire, le mandat, par ailleurs révocable pour justes motifs, cessera en cas de résiliation du présent contrat, faute de paiement des loyers. II est convenu entre les parties que les loyers seront réglés à bonne date, même en cas d'.chr(146).'introduction d'.chr(146).'une instance contre le fournisseur et ce jusqu'.chr(146).'à ce que leurs relations financières soient liquidées. En toute hypothèse, le locataire garantit le loueur de tout préjudice et s'.chr(146).'oblige à le couvrir notamment de tous honoraires, frais, débours, même non répétibles, engagés à l'.chr(146).'occasion de sa représentation judiciaire ou amiable. En cas de résolution de la vente, cette résolution entraînera de plein droit la résiliation du présent contrat et le paiement immédiat par le locataire au  loueur de l'.chr(146).'indemnité de résiliation telle que prévue a l'.chr(146).'article résiliation  ci après. Cependant, le loueur imputera au paiement de cette indemnité les sommes effectivement reçues du fournisseur de l'.chr(146).'équipement en vertu d'.chr(146).'une décision judiciaire devenue définitive en restitution du prix au titre de la résolution de la vente. Dans tous les cas, le locataire sera garant du fournisseur pour les sommes dues par celui-ci au titre de la résolution de la vente.';
        $col2ahead = 'Art10 - RESILIATION ET PROLONGATION';
        $col2a = 'En cas de demande d'.chr(146).'annulation du présent contrat par le locataire avant sa date d'.chr(146).'effet, le locataire sera redevable envers le loueur d'.chr(146).'une indemnité d'.chr(146).'annulation égale aux six premiers mois de loyer HT prévus au présent contrat, à titre de dommages et intérêts. L'.chr(146).'annulation du présent contrat ne sera reconnue effective qu'.chr(146).'après l'.chr(146).'acceptation par le fournisseur de l'.chr(146).'annulation de la commande et le règlement de  l'.chr(146).'indemnité définie ci-dessus.Le présent contrat pourra être résilié de plein droit par le loueur, sans qu'.chr(146).'il soit besoin de remplir aucune formalité judiciaire en cas :  - de non paiement à échéance d'.chr(146).'un seul terme de loyer  - d'.chr(146).'inexécution par le locataire d'.chr(146).'une seule des conditions générales ou particulières de location  - modification de la situation du locataire et notamment décès, redressement judiciaire, liquidation amiable ou judiciaire, cessation d'.chr(146).'activité, cession du fonds de commerce, de parts ou d'.chr(146).'actions du locataire, changement de forme sociale ; - modification concernant l'.chr(146).'équipement loué et notamment détérioration, destruction ou aliénation de l'.chr(146).'équipement loué (apport en société, fusion absorption, scission, ...) ou perte ou diminution des garanties fournies. Et ce, 8 jours après une mise en demeure faite par lettre recommandée avec AR demeurée infructueuse. Les offres de payer ou d'.chr(146).'exécuter, ainsi que le paiement ou l'.chr(146).'exécution intervenus après expiration du délai susvisé ne remettent pas en cause l'.chr(146).'acquisition de la clause résolutoire. Dans tous les cas de résiliation, le locataire devra : -Restituer l'.chr(146).'équipement dans les conditions visées ci-dessous.  -Verser la totalité des loyers échus non payés et restant à courir à la date de résiliation. La somme ainsi obtenue est augmentée d'.chr(146).'une indemnité forfaitaire égale à 20% du montant des loyers HT restant à courir au titre du ';

        $pdf->SetFont('Vera','',5);
        $pdf->SetX($larg1 + $this->marge_gauche + $ptFond);
        $pdf->MultiCell($larg1,'3',utf8_decode($col2),0,'J',0);
        $nexY=($pdf->GetY()>$nexY?$pdf->GetY():$nexY);
        $pdf->SetFont('Vera','B',5);
        $pdf->SetX($larg1 + $this->marge_gauche + $ptFond);
        $pdf->MultiCell($larg1,'3',utf8_decode($col2ahead),0,'J',0);
        $nexY=($pdf->GetY()>$nexY?$pdf->GetY():$nexY);
        $pdf->SetX($larg1 + $this->marge_gauche + $ptFond);
        $pdf->SetFont('Vera','',5);
        $pdf->MultiCell($larg1,'3',utf8_decode($col2a),0,'J',0);
        $nexY=($pdf->GetY()>$nexY?$pdf->GetY():$nexY);
        $pdf->SetY($curY);
        $pdf->SetX($larg1 * 2 + $this->marge_gauche + $ptFond * 2);

        $col3 = 'présent contrat, à compter du jour de la résiliation. Elle est majorée des frais et honoraires éventuels rendus nécessaires pour en assurer le recouvrement. Tous les frais occasionnés au loueur du fait de la résiliation du présent contrat ainsi que tous les frais afférents au démontage, emballage et transport de l'.chr(146).'équipement en retour sont à la charge exclusive du locataire. Le locataire doit informer le loueur,  avec un préavis de trois mois, avant le terme de la période irrévocable de location par lettre recommandée avec accusé de réception, de son intention de ne pas poursuivre la location et donc de restituer l'.chr(146).'équipement au terme de la période irrévocable. Dans le cas contraire, au-delà de la durée irrévocable, le contrat est prolongé aux mêmes conditions par tacite reconduction pour un an minimum. A l'.chr(146).'issue de cette année de reconduction, le locataire pourra  y mettre fin à tout moment avec un préavis de trois mois';
        $col3aheader = 'Art11 - RESTITUTION DE L'.chr(146).'EQUIPEMENT';
        $col3a = 'Dès la fin de la location, dès la résiliation anticipée de celle ci ou à l'.chr(146).'expiration de la tacite reconduction, le locataire doit restituer immédiatement au loueur et à l'.chr(146).'endroit désigné par celui ci, l'.chr(146).'équipement en bon état de propreté et de fonctionnement avec sa documentation ses pièces et accessoires indispensables à son bon fonctionnement, muni de ses papiers, de son carnet d'.chr(146).'entretien, ainsi que de toute la documentation afférente aux logiciels. Les frais de transport incombant dans tous les cas au locataire. Le loueur se réserve de déléguer toute personne susceptible de prendre possession de l'.chr(146).'équipement  en ses lieu et place En cas de retard de restitution le locataire est redevable d'.chr(146).'une indemnité de privation de jouissance égale au loyer du dernier terme écoulé et ce pour chaque période de retard correspondant à la durée de ce terme, toute période commencée étant due en entier. La restitution de l'.chr(146).'équipement implique que le locataire s'.chr(146).'engage à ne plus utiliser les logiciels et détruise et/ou efface de ses bibliothèques ou de ses dispositifs de stockage informatique toutes les copies des logiciels autorisées.';
        $col3bheader = 'Art12 - ELECTION DE DOMICILE';
        $col3b = 'Pour l'.chr(146).'exécution du présent contrat, les parties font élection de domicile au siège de leur société. Tout litige auquel peut donner lieu le présent contrat est de la compétence, à l'.chr(146).'égard des commerçants, du Tribunal de Commerce dans le ressort duquel le loueur a son domicile. Les informations figurant dans les présentes ont un caractère obligatoire pour le traitement de la  demande du locataire. Ces informations ou celles recueillies ultérieurement ne seront utilisées et ne feront l'.chr(146).'objet de communication aux destinataires déclarés à la Commission Nationale de l'.chr(146).'Informatique et des Libertés que pour les seules nécessités de gestion ou d'.chr(146).'actions commerciales. Ces informations pourront toutefois être communiquées aux entreprises extérieures liées contractuellement au loueur pour la gestion et l'.chr(146).'exécution des présentes dans la stricte limite de leurs attributions respectives ainsi qu'.chr(146).'aux seuls Etablissements de Crédit  soumis au secret professionnel bancaire en vertu des dispositions des articles L.511-33 et suivants du Code monétaire et financier lié au loueur en vue de la gestion de leurs financements. Elles pourront donner lieu à exercice du droit d'.chr(146).'accès et de rectification auprès du loueur dans les conditions prévues par la loi du 6 janvier 1978, en particulier après paiement de la redevance légale sauf rectification justifiée. Le locataire peut recevoir des propositions commerciales de sociétés auxquelles le loueur peut communiquer ses nom et adresse, sauf si le locataire l'.chr(146).'avise  de son souhait que ceux-ci ne soient pas communiqués';


        $pdf->SetFont('Vera','',5);
        $pdf->MultiCell($larg1,'3',utf8_decode($col3),0,'J',0);
        $nexY=($pdf->GetY()>$nexY?$pdf->GetY():$nexY);
        $pdf->SetFont('Vera','B',5);
        $pdf->SetX($larg1 * 2 + $this->marge_gauche + $ptFond * 2);
        $pdf->MultiCell($larg1,'3',utf8_decode($col3aheader),0,'J',0);
        $nexY=($pdf->GetY()>$nexY?$pdf->GetY():$nexY);
        $pdf->SetFont('Vera','',5);
        $pdf->SetX($larg1 * 2 + $this->marge_gauche + $ptFond * 2);
        $pdf->MultiCell($larg1,'3',utf8_decode($col3a),0,'J',0);
        $nexY=($pdf->GetY()>$nexY?$pdf->GetY():$nexY);
        $pdf->SetFont('Vera','B',5);
        $pdf->SetX($larg1 * 2 + $this->marge_gauche + $ptFond * 2);
        $pdf->MultiCell($larg1,'3',utf8_decode($col3bheader),0,'J',0);
        $nexY=($pdf->GetY()>$nexY?$pdf->GetY():$nexY);
        $pdf->SetFont('Vera','',5);
        $pdf->SetX($larg1 * 2 + $this->marge_gauche + $ptFond * 2);
        $pdf->MultiCell($larg1,'3',utf8_decode($col3b),0,'J',0);
        $nexY=($pdf->GetY()>$nexY?$pdf->GetY():$nexY);

        $pdf->lasth=4;
        $pdf->SetFont('Vera','B',7);
        $block1 = 'Pour le Locataire'."\n".'Signature et cachet (lu et approuvé)'."\n".'Qualité'."\n".'Nom';
        $block2 = 'Pour le Loueur'."\n".'Signature et cachet';
        $block3 = 'Pour le Cessionnaire'."\n".'Signature et cachet';
        $curY = $nexY + 5;
        $larg1 = ($this->page_largeur - ($this->marge_gauche + $this->marge_droite)) / 3;
        $pdf->SetY($nexY + 5);
        $pdf->SetX($this->marge_gauche);
        $pdf->MultiCell($larg1,'4',utf8_decode($block1),0,"L",0);
        $pdf->SetY($curY);
        $pdf->SetX($this->marge_gauche + $larg1);
        $pdf->MultiCell($larg1,'4',utf8_decode($block2),0,"C",0);
        $pdf->SetY($curY);
        $pdf->SetX($this->marge_gauche + $larg1 * 2);
        $pdf->MultiCell($larg1,'4',utf8_decode($block3),0,"R",0);



        $pdf->SetFont('Vera','',7);
        // Pied de page
        $this->_pagefoot($pdf);

    }

    function page_conditionParticuliereSiemens(&$pdf,$contratGA,$tmpSignature,$maintenance=false)
    {
        global $outputlangs, $langs, $conf, $mysoc;
        $pdf->AddPage();

        $pdf->SetDrawColor(128,128,128);
        $pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite);   // Left, Top, Right
        $pagenb = 1;
        $tab_top = 48;
        $tab_top_newpage = 7;
        $tab_height = 50;
        $tab_height_newpage = 50;
        $tab_height_middlepage = 50;
        $iniY = $tab_top + 8;
        $curY = $tab_top + 8;
        $nexY = $tab_top + 8;



        $pdf->SetFillColor(255,255,255);
        $pdf->SetDrawColor(0,0,0);
        $pdf->SetTextColor(0,0,0);
        $pdf->SetFont('Vera','',7);
        $pdf->Ln(8);
        $this->_pagehead3($pdf, $contratGA, 0);
        $pdf->SetFont('Vera','',18);

        //Titre
        $pdf->SetY(10);
        $pdf->MultiCell($this->page_largeur - ($this->marge_gauche + $this->marge_droite),10,'CONDITIONS PARTICULIERES',0,'C',0);
        $html = 'CONTRAT DE LOCATION N° '.$contratGA->ref;
        $pdf->SetFont('Vera','',10);
        $pdf->MultiCell($this->page_largeur - ($this->marge_gauche + $this->marge_droite),10,utf8_decode($html),0,'C',0);
        $pdf->SetFont('Vera','',8);

        $tmpFourn = new Societe($this->db);
        $tmpFourn->fetch($contratGA->fournisseur_refid);
        $fournisseur_name = utf8_encode($tmpFourn->nom);

        $html = 'ENTRE : '.$mysoc->nom. '  ET ' . $fournisseur_name;
        $pdf->MultiCell($this->page_largeur - ($this->marge_gauche + $this->marge_droite),10,utf8_decode($html),0,'C',0);



        $pdf->SetY(35);
        $loyerGlobal = $contratGA->getLoyerTot();
        $html = "<strong>ARTICLE 1 - DESIGNATION</strong><br>";
        $this->writeHTML($html, 4, 0,$pdf);
        $larg1=($this->page_largeur) / 6;
        $pdf->SetMargins($larg1, $this->marge_haute, $larg1);   // Left, Top, Right
        $pdf->setX($larg1);
        $pdf->SetFillColor(255,255,255);

        $pdf->SetY(50);
        $curY = 50;
        $pdf->SetFillColor(91,148,226);
        $pdf->MultiCell($larg1,'6', utf8_decode('Quantité'),0,'C',1 );
        $pdf->SetY($curY);
        $pdf->SetX($pdf->GetX()+$larg1);
        $pdf->MultiCell($larg1*2,'6', utf8_decode('Designation'),0,'C',1 );
        $pdf->SetY($curY);
        $pdf->SetX($pdf->GetX()+$larg1*3);
        $pdf->MultiCell($larg1,'6', utf8_decode('Marque'),0,'C',1 );
        $pdf->SetFont('Vera','',7);

        $contratGA->fetch_lignes();
        foreach($contratGA->lignes as $key=>$val)
        {
            if ($val->fk_product > 0)
            {
                $curY = $pdf->GetY();
                $pdf->MultiCell($larg1,'4', utf8_decode($val->qty),0,'C',0 );
                $pdf->SetY($curY);
                $pdf->SetX($pdf->GetX()+$larg1);
                $pdf->MultiCell($larg1*2,'4', utf8_decode($val->description),0,'C',0 );
                $pdf->SetY($curY);
                $pdf->SetX($pdf->GetX()+$larg1*3);
                $pdf->MultiCell($larg1,'4', utf8_decode('Marque'),0,'C',0 );
            }
        }

        $pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite);   // Left, Top, Right
        $pdf->SetFillColor(255,255,255);

//$this->duree = $amortperiod;
//            $this->NbIterTot = $payperyear * ($res->duree / 12);
//            $this->periodicite = $res->periodicite;
//            $this->echu = $res->echu;

        $html ="<br><strong>ARTICLE 2 -  DUREE ET TAXE</strong><br><strong>Durée de la location : ".$contratGA->duree." mois</strong><br><br>Le présent contrat est conclu pour une durée de voir ci-dessus, commençant à courir le jour suivant la mise en ordre de marche de l".chr(146)."équipement, constatée par un procès verbal tel que défini à l".chr(146)."article 6 des conditions générales. Il s".chr(146)."y ajoutera, le cas échéant, les mois ou fractions de mois intervenus entre le jour de la mise en ordre de marche dûment constatée du premier équipement et le jour du procès verbal définitif dont il est fait mention ci-dessus.<br>";
        $html .="<br><strong>ARTICLE 3 -  EVOLUTION DE L".chr(146)."EQUIPEMENT</strong><br>";
        $html .= "Le locataire pourra demander au bailleur, au cours de la période de validité du présent contrat la modification de l".chr(146)."équipement informatique remis en location. Les modifications éventuelles du contrat seront déterminées par l".chr(146)."accord des parties. Cette modification pourra porter sur tout ou partie des équipements, par adjonction, remplacement et/ou enlèvement des matériels repris dans l".chr(146)."article 1 ci-dessus.<br>";
        $html .="<br><strong>ARTICLE 4 -  LOYERS HORS MAINTENANCE</strong><br>";
        $html .=" Le loyer ferme et non révisable en cours de contrat, payable par terme à échoir, par prélèvements automatiques est fixé à :<br>";
//MultiCell
        $pdf->lasth=4;
        $this->writeHTML($html, 4, 0,$pdf);
        $pdf->SetFont('Vera','BU',7);
        $curY = $pdf->GetY();
        $larg1 = ($this->page_largeur - ($this->marge_gauche + $this->marge_droite)) / 3;
        $pdf->SetY($curY);
        $pdf->SetX($this->marge_gauche);
        $pdf->MultiCell($larg1,'4',utf8_decode("NOMBRE DE LOYER"),0,"C",0);
        $pdf->SetY($curY);
        $pdf->SetX($this->marge_gauche + $larg1);
        $pdf->MultiCell($larg1,'4',utf8_decode("MONTANT"),0,"C",0);
        $pdf->SetY($curY);
        $pdf->SetX($this->marge_gauche + $larg1 * 2);
        $pdf->MultiCell($larg1,'4',utf8_decode("PERIODICITE"),0,"C",0);
        $curY += 5;
        $pdf->SetFont('Vera','B',8);
        $larg1 = ($this->page_largeur - ($this->marge_gauche + $this->marge_droite)) / 3;
        $pdf->SetY($curY);
        $pdf->SetX($this->marge_gauche);
        $pdf->MultiCell($larg1,'4',$contratGA->NbIterTot,0,"C",0);
        $pdf->SetY($curY);
        $pdf->SetX($this->marge_gauche + $larg1);
        $pdf->MultiCell($larg1,'4',price(round($loyerGlobal*100)/100) . " ".chr(128),0,"C",0);
        $pdf->SetY($curY);
        $pdf->SetX($this->marge_gauche + $larg1 * 2);
        $pdf->MultiCell($larg1,'4',utf8_decode($contratGA->periodicite),0,"C",0);
        if ($maintenance)
        {
            $pdf->SetTextColor(128,128,128);
            $pdf->SetFont('Vera','I',7);
            $html = "<br><bi>Autre(s) condition(s) : Ces termes de loyers seront majorés des montants des prestations dont le Loueur a reçu manda d'encaissement, qui s'élèvent à   Euros H.T par terme de loyer.</bi><br>";
            $pdf->lasth=5;
            $this->writeHTML($html, 4, 0,$pdf);
        }

        $pdf->SetTextColor(0,0,0);
        $pdf->SetFont('Vera','',7);

        $pdf->SetDrawColor(128,128,128);
        $pdf->Rect($this->marge_gauche - 3,$pdf->GetY() + 3, $this->page_largeur - ($this->marge_gauche + $this->marge_droite) , 11 );
        $pdf->SetDrawColor(0,0,0);
        $html = "<br><bui>Les signataires acceptent l".chr(146)."ensemble des clauses ci-dessus, ainsi que les conditions générales annexées.<br>Toutes modifications ou clauses spécifiques devront faire l".chr(146)."objet d".chr(146)."un avenant séparé.</bui><br>";
        $pdf->lasth=4;
        $this->writeHTML($html, 4, 0,$pdf);

        $html ="<br><strong>ARTICLE 5 -  SITE D".chr(146)."INSTALLATION PREVU :</strong> <br>";

        $date_contratGA = date('d/m/Y',($contratGA->mise_en_service > 0?$contratGA->mise_en_service:time()));

        $html .="<br><strong>ARTICLE 6 -  DATE D".chr(146)."INSTALLATION PREVUE :</strong>  ". $date_contratGA ."<br>";
        $html .="<br><strong>ARTICLE 7 -  CLAUSE SPECIFIQUE : Assurance souscrite par le locataire.</strong><br>";
        $pdf->lasth=4;
        $this->writeHTML($html, 4, 0,$pdf);
        $pdf->SetFont('Vera','',7);

        $pdf->lasth=4;
        $pdf->SetFont('Vera','B',7);
        $block1 = 'Pour le Locataire'."\n".'Signature et cachet (lu et approuvé)'."\n".'Qualité'."\n".'Nom';
        $block2 = 'Pour le Loueur'."\n".'Signature et cachet';
        $block3 = 'Pour le Cessionnaire'."\n".'Signature et cachet';
        $curY = $pdf->GetY() + 5;
        $larg1 = ($this->page_largeur - ($this->marge_gauche + $this->marge_droite)) / 3;
        $pdf->SetY($curY);
        $pdf->SetX($this->marge_gauche);
        $pdf->MultiCell($larg1,'4',utf8_decode($block1),0,"L",0);
        $pdf->SetY($curY);
        $pdf->SetX($this->marge_gauche + $larg1);
        $pdf->MultiCell($larg1,'4',utf8_decode($block2),0,"C",0);
        $pdf->SetY($curY);
        $pdf->SetX($this->marge_gauche + $larg1 * 2);
        $pdf->MultiCell($larg1,'4',utf8_decode($block3),0,"R",0);

        $pdf->SetFont('Vera','',7);
        // Pied de page
        $this->_pagefoot($pdf);


    }
    function page_conditionParticuliereSiemens_Maintenance(&$pdf,$contratGA,$tmpSignature)
    {
        global $outputlangs, $langs, $conf;
        $this->page_conditionParticuliereSiemens(&$pdf,$contratGA,$tmpSignature,1);
    }

    function page_conditionParticuliereBNP36_12(&$pdf,$contratGA,$tmpSignature)
    {
        global $outputlangs, $langs, $conf;
        $pdf->AddPage();

        $pdf->SetDrawColor(128,128,128);
        $pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite);   // Left, Top, Right
        $pagenb = 1;
        $tab_top = 48;
        $tab_top_newpage = 7;
        $tab_height = 50;
        $tab_height_newpage = 50;
        $tab_height_middlepage = 50;
        $iniY = $tab_top + 8;
        $curY = $tab_top + 8;
        $nexY = $tab_top + 8;

        $pdf->SetFillColor(255,255,255);
        $pdf->SetDrawColor(0,0,0);
        $pdf->SetTextColor(0,0,0);
        $pdf->SetFont('Vera','',7);
        $pdf->Ln(8);
        $this->_pagehead3($pdf, $contratGA, 1);

        $pdf->SetY(30);

        $html = $tmpSignature->fullname;
        $pdf->SetX($this->marge_gauche * 4);
        $pdf->MultiCell($this->page_largeur- 3.5 * ($this->marge_gauche + $this->marge_droite),'6', utf8_decode($html),0,'R',0 );
        $pdf->lasth=4;
        $pdf->SetFont('Vera','',10);
        $html='CONTRAT DE LOCATION N° ';
        $tmpWidth = $pdf->GetStringWidth($html . " ".$contratGA->ref );
        $tmpWidth1 = $pdf->GetStringWidth($html);
        $pdf->setX($this->page_largeur / 2  - ($tmpWidth/2));

        $pdf->MultiCell(50,'6', utf8_decode($html),0,'R',0 );
        $html = $contratGA->ref;
        $pdf->SetY($pdf->GetY()-12);
        $pdf->setX($this->page_largeur / 2 - ($tmpWidth/2) + $tmpWidth1 );
        $pdf->SetTextColor(38,0,255);
        $pdf->MultiCell($this->page_largeur / 2 - ($this->marge_gauche / 2),'6', utf8_decode($html),0,'L',0 );
        $pdf->SetTextColor(0,0,0);

        $pdf->SetFont('Vera','B',8);
        $tmpFourn = new Societe($this->db);
        $tmpFourn->fetch($contratGA->fournisseur_refid);
        $fournisseur_name = utf8_encode($tmpFourn->nom);
        $html ='<br><br>Le locataire :<br>';
        $pdf->lasth=4;
        $this->writeHTML($html, 4, 0,$pdf);
        $pdf->SetFont('Vera','',7);
        $html ='La société '.$fournisseur_name.', au capital de 10 000,00 '.chr(128).'<br>Immatriculée sous le n° : 429 710 700 00031 auprés du RCS de LA ROCHELLE<br>Dont le siège social est situé  : '.utf8_encode($tmpFourn->adresse).' '.$tmpFourn->cp.' '.strtoupper(utf8_encode($tmpFourn->ville)).'<br>Représenté par : Monsieur DUBOIS Patrice intervenant en qualité de  : Gérant<br>';
        $this->writeHTML($html, 4, 0,$pdf);
        $pdf->SetFont('Vera','B',8);
        $html ='Le loueur :<br>';
        $this->writeHTML($html, 4, 0,$pdf);
        $pdf->SetFont('Vera','',7);
        $html = 'La Société FINAPRO, SARL au capital de 50 000 '.chr(128).' dont le siège social est situé  à Jouques (13490), Parc du Deffend - 23 boulevard du Deffend, enregistrée sous le n° 443 247 978 au RCS d'.chr(146).'Aix en Provence,<br>Représentée par Mademoiselle Patricia RODDIER, intervenant en qualité de Gérante<br>Le loueur donne en location, l'.chr(146).'équipement désigné ci-dessous (ci-après « équipement »), au locataire qui l'.chr(146).'accepte, aux Conditions Générales ci-annexées composées de deux pages recto : Feuillet A et Feuillet B et aux Conditions Particulières suivantes :';
        $this->writeHTML($html, 4, 0,$pdf);
        $pdf->SetFont('Vera','B',8);
        $html = '<br>Description de l'.chr(146).'équipement<br>';
        $this->writeHTML($html, 4, 0,$pdf);
        $pdf->SetFont('Vera','B',8);

        $larg1=($this->page_largeur) / 6;
        $pdf->SetMargins($larg1, $this->marge_haute, $larg1);   // Left, Top, Right
        $pdf->setX($larg1);
        $curY = $pdf->GetY();
        $pdf->SetFillColor(255,255,255);

        $pdf->SetFillColor(91,148,226);
        $pdf->MultiCell($larg1,'6', utf8_decode('Quantité'),0,'C',1 );
        $pdf->SetY($curY);
        $pdf->SetX($pdf->GetX()+$larg1);
        $pdf->MultiCell($larg1*2,'6', utf8_decode('Designation'),0,'C',1 );
        $pdf->SetY($curY);
        $pdf->SetX($pdf->GetX()+$larg1*3);
        $pdf->MultiCell($larg1,'6', utf8_decode('Marque'),0,'C',1 );
        $pdf->SetFont('Vera','',7);

        $contratGA->fetch_lignes();
        foreach($contratGA->lignes as $key=>$val)
        {
            if ($val->fk_product > 0)
            {
                $curY = $pdf->GetY();
                $pdf->MultiCell($larg1,'4', utf8_decode($val->qty),0,'C',0 );
                $pdf->SetY($curY);
                $pdf->SetX($pdf->GetX()+$larg1);
                $pdf->MultiCell($larg1*2,'4', utf8_decode($val->description),0,'C',0 );
                $pdf->SetY($curY);
                $pdf->SetX($pdf->GetX()+$larg1*3);
                $pdf->MultiCell($larg1,'4', utf8_decode('Marque'),0,'C',0 );
            }
        }

        $pdf->SetFillColor(255,255,255);
        $pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite);   // Left, Top, Right
        $pdf->SetX($this->marge_gauche);
        $pdf->SetFont('Vera','B',8);
        $html = '<br>Evolution de l'.chr(146).'équipement';
        $pdf->lasth=4;
        $this->writeHTML($html, 4, 0,$pdf);
        $pdf->SetFont('Vera','',7);
        $html ='Le locataire pourra demander au bailleur, au cours de la période de validité du présent contrat la modification de l'.chr(146).'équipement informatique remis en location. Les modifications éventuelles du contrat seront déterminées par l'.chr(146).'accord des parties.<br>Cette modification pourra porter sur tout ou partie des équipements, par adjonction, remplacement et/ou enlèvement des matériels repris dans  l'.chr(146).'article 1 ci-dessus.';
        $this->writeHTML($html, 4, 0,$pdf);
        $html = 'Loyers';
        $pdf->SetFont('Vera','B',8);
        $this->writeHTML($html, 4, 0,$pdf);
        $pdf->SetFont('Vera','',7);
        $echu = ($contratGA->echu=='1'?'à terme échu':'terme à échoir');
        $html ='Le loyer ferme et non révisable en cours de contratGA, payable par '.$echu.', par prélèvements automatiques est fixé à :';
        $pdf->lasth=4;
        $this->writeHTML($html, 4, 0,$pdf);
//TODO Valeur !!!
        $larg1 = ($this->page_largeur - $this->marge_gauche - $this->marge_droite) / 4;
        $pdf->SetX($this->marge_gauche);
        $curY = $pdf->GetY();
        $pdf->MultiCell($larg1,'6', utf8_decode('NOMBRE DE LOYERS'."\n".'12'),1,'C',0 );
        $pdf->SetY($curY);
        $pdf->SetX($larg1+$this->marge_gauche);
        $pdf->MultiCell($larg1,'6', utf8_decode('MONTANT'."\n".'12 000'),1,'C',0 );
        $pdf->SetY($curY);
        $pdf->SetX($larg1*2+$this->marge_gauche);
        $pdf->MultiCell($larg1,'6', utf8_decode('PERIODICITE'."\n".'trimestre'),1,'C',0 );
        $pdf->SetY($curY);
        $pdf->SetX($larg1*3+$this->marge_gauche);
        $pdf->MultiCell($larg1,'6', utf8_decode('DUREE'."\n".'36 mois'),1,'C',0 );

        $pdf->SetX($this->marge_gauche);
        $curY = $pdf->GetY();
        $pdf->SetY($curY + 3);
        $pdf->SetFont('Vera','B',8);
        $pdf->MultiCell($larg1-20,'6', utf8_decode('Suivi de'),0,'L',0 );
        $pdf->SetFont('Vera','',7);
        $pdf->SetY($curY);
        $pdf->SetX($larg1+$this->marge_gauche);
        $pdf->MultiCell($larg1,'6', utf8_decode('NOMBRE DE LOYERS'."\n".'12'),1,'C',0 );
        $pdf->SetY($curY);
        $pdf->SetX($larg1*2+$this->marge_gauche);
        $pdf->MultiCell($larg1,'6', utf8_decode('MONTANT'."\n".'12 000'),1,'C',0 );
        $pdf->SetY($curY);
        $pdf->SetX($larg1*3+$this->marge_gauche);
        $pdf->MultiCell($larg1,'6', utf8_decode('PERIODICITE'."\n".'mensuelle'),1,'C',0 );

        $datecontratGA = date('d/m/Y',($contratGA->mise_en_service > 0?$contratGA->mise_en_service:time()));
        $html = '<br><strong>Site d'.chr(146).'installation :</strong><br><strong>Date d'.chr(146).'installation :</strong> '.$datecontratGA.'<br><strong>Clause spécifique :</strong><br>Fait en autant d'.chr(146).'exemplaires que de parties, un pour chacune des parties<br>ANNEXE : Conditions Générales composées de deux pages recto : Feuillet A et Feuillet B';
        $pdf->lasth=5;
        $this->writeHTML($html, 4, 0,$pdf);

        $pdf->lasth=4;
        $html =  '<br>Fait à '.strtoupper($tmpFourn->ville).' le : '.$datecontratGA.'<br><br>';
        $this->writeHTML($html, 4, 0,$pdf);
        $pdf->SetFont('Vera','B',7);
        $block1 = 'Pour le Locataire'."\n".'Signature et cachet (lu et approuvé)'."\n".'Qualité'."\n".'Nom';
        $block2 = 'Pour le Loueur'."\n".'Signature et cachet';
        $block3 = 'Pour le Cessionnaire'."\n".'Signature et cachet';
        $curY = $pdf->GetY();
        $larg1 = ($this->page_largeur - ($this->marge_gauche + $this->marge_droite)) / 3;
        $pdf->SetY($curY);
        $pdf->SetX($this->marge_gauche);
        $pdf->MultiCell($larg1,'4',utf8_decode($block1),0,"L",0);
        $pdf->SetY($curY);
        $pdf->SetX($this->marge_gauche + $larg1);
        $pdf->MultiCell($larg1,'4',utf8_decode($block2),0,"C",0);
        $pdf->SetY($curY);
        $pdf->SetX($this->marge_gauche + $larg1 * 2);
        $pdf->MultiCell($larg1,'4',utf8_decode($block3),0,"R",0);

        $pdf->SetFont('Vera','',7);
        // Pied de page
        $this->_pagefoot($pdf);

    }

    function page_conditionParticuliereBNP(&$pdf,$contratGA,$tmpSignature)
    {
        global $outputlangs, $langs, $conf;
        $pdf->AddPage();

        $pdf->SetDrawColor(128,128,128);
        $pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite);   // Left, Top, Right
        $pagenb = 1;
        $tab_top = 48;
        $tab_top_newpage = 7;
        $tab_height = 50;
        $tab_height_newpage = 50;
        $tab_height_middlepage = 50;
        $iniY = $tab_top + 8;
        $curY = $tab_top + 8;
        $nexY = $tab_top + 8;

        $pdf->SetFillColor(255,255,255);
        $pdf->SetDrawColor(0,0,0);
        $pdf->SetTextColor(0,0,0);
        $pdf->SetFont('Vera','',7);
        $pdf->Ln(8);
        $this->_pagehead3($pdf, $contratGA, 1);

        $pdf->SetY(30);

        $html = $tmpSignature->fullname;
        $pdf->SetX($this->marge_gauche * 4);
        $pdf->MultiCell($this->page_largeur- 3.5 * ($this->marge_gauche + $this->marge_droite),'6', utf8_decode($html),0,'R',0 );
        $pdf->lasth=4;
        $pdf->SetFont('Vera','',10);
        $html='CONTRAT DE LOCATION N° ';
        $tmpWidth = $pdf->GetStringWidth($html . " ".$contratGA->ref );
        $tmpWidth1 = $pdf->GetStringWidth($html);
        $pdf->setX($this->page_largeur / 2  - ($tmpWidth/2));

        $pdf->MultiCell(50,'6', utf8_decode($html),0,'R',0 );
        $html = $contratGA->ref;
        $pdf->SetY($pdf->GetY()-12);
        $pdf->setX($this->page_largeur / 2 - ($tmpWidth/2) + $tmpWidth1 );
        $pdf->SetTextColor(38,0,255);
        $pdf->MultiCell($this->page_largeur / 2 - ($this->marge_gauche / 2),'6', utf8_decode($html),0,'L',0 );
        $pdf->SetTextColor(0,0,0);

        $pdf->SetFont('Vera','B',8);
        $tmpFourn = new Societe($this->db);
        $tmpFourn->fetch($contratGA->fournisseur_refid);
        $fournisseur_name = utf8_encode($tmpFourn->nom);
        $html ='<br><br>Le locataire :<br>';
        $pdf->lasth=4;
        $this->writeHTML($html, 4, 0,$pdf);
        $pdf->SetFont('Vera','',7);
        $html ='La société '.$fournisseur_name.', au capital de 10 000,00 '.chr(128).'<br>Immatriculée sous le n° : 429 710 700 00031 auprés du RCS de LA ROCHELLE<br>Dont le siège social est situé  : '.utf8_encode($tmpFourn->adresse).' '.$tmpFourn->cp.' '.strtoupper(utf8_encode($tmpFourn->ville)).'<br>Représenté par : Monsieur DUBOIS Patrice intervenant en qualité de  : Gérant<br>';
        $this->writeHTML($html, 4, 0,$pdf);
        $pdf->SetFont('Vera','B',8);
        $html ='Le loueur :<br>';
        $this->writeHTML($html, 4, 0,$pdf);
        $pdf->SetFont('Vera','',7);
        $html = 'La Société FINAPRO, SARL au capital de 50 000 '.chr(128).' dont le siège social est situé  à Jouques (13490), Parc du Deffend - 23 boulevard du Deffend, enregistrée sous le n° 443 247 978 au RCS d'.chr(146).'Aix en Provence,<br>Représentée par Mademoiselle Patricia RODDIER, intervenant en qualité de Gérante<br>Le loueur donne en location, l'.chr(146).'équipement désigné ci-dessous (ci-après « équipement »), au locataire qui l'.chr(146).'accepte, aux Conditions Générales ci-annexées composées de deux pages recto : Feuillet A et Feuillet B et aux Conditions Particulières suivantes :';
        $this->writeHTML($html, 4, 0,$pdf);
        $pdf->SetFont('Vera','B',8);
        $html = '<br>Description de l'.chr(146).'équipement<br>';
        $this->writeHTML($html, 4, 0,$pdf);
        $pdf->SetFont('Vera','B',8);

        $larg1=($this->page_largeur) / 6;
        $pdf->SetMargins($larg1, $this->marge_haute, $larg1);   // Left, Top, Right
        $pdf->setX($larg1);
        $curY = $pdf->GetY();
        $pdf->SetFillColor(255,255,255);

        $pdf->SetFillColor(91,148,226);
        $pdf->MultiCell($larg1,'6', utf8_decode('Quantité'),0,'C',1 );
        $pdf->SetY($curY);
        $pdf->SetX($pdf->GetX()+$larg1);
        $pdf->MultiCell($larg1*2,'6', utf8_decode('Designation'),0,'C',1 );
        $pdf->SetY($curY);
        $pdf->SetX($pdf->GetX()+$larg1*3);
        $pdf->MultiCell($larg1,'6', utf8_decode('Marque'),0,'C',1 );
        $pdf->SetFont('Vera','',7);

        $contratGA->fetch_lignes();
        $tmpY = false;
        foreach($contratGA->lignes as $key=>$val)
        {
            if ($val->fk_product > 0)
            {
                $curY = $pdf->GetY();
                $pdf->MultiCell($larg1,'4', utf8_decode($val->qty),0,'C',0 );
                $pdf->SetY($curY);
                $pdf->SetX($pdf->GetX()+$larg1);
                if (strlen($val->product->description) > 0)
                {
                    //$tmpdesc = preg_replace('/<br[\W\w]*>/',"\n",strip_tags($val->product->description,'<br>'));
                    //$tmpdesc = $pdf->writeHTML($tmpdesc);
                    $tmpY = $pdf->writeHTMLCell($larg1*2, 4, $pdf->GetX(), $pdf->GetY(), $val->product->description, 0, 1, 0);
                    //$tmpY = $this->GetY();
                    $tmpY -= 12;
                    //$pdf->MultiCell($larg1*2,'4', utf8_decode($tmpdesc),0,'C',0 );
                } else {
                    $pdf->MultiCell($larg1*2,'4', utf8_decode($val->description),0,'C',0 );
                }
                $pdf->SetY($curY);
                $pdf->SetX($pdf->GetX()+$larg1*3);
                $pdf->MultiCell($larg1,'4', utf8_decode(' - '),0,'C',0 );
            }
        }

        $pdf->SetFillColor(255,255,255);
        $pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite);   // Left, Top, Right
        $pdf->SetX($this->marge_gauche);
        if ($tmpY)
        {
            $pdf->SetY($tmpY);
        }
        $pdf->SetFont('Vera','B',8);
        $html = '<br>Evolution de l'.chr(146).'équipement<br>';
        $pdf->lasth=4;
        $this->writeHTML($html, 4, 0,$pdf);
        $pdf->SetFont('Vera','',7);
        $html ='Le locataire pourra demander au bailleur, au cours de la période de validité du présent contrat la modification de l'.chr(146).'équipement informatique remis en location. Les modifications éventuelles du contrat seront déterminées par l'.chr(146).'accord des parties.<br>Cette modification pourra porter sur tout ou partie des équipements, par adjonction, remplacement et/ou enlèvement des matériels repris dans  l'.chr(146).'article 1 ci-dessus.';
        $this->writeHTML($html, 4, 0,$pdf);
        $html = '<br>Loyers<br>';
        $pdf->SetFont('Vera','B',8);
        $this->writeHTML($html, 4, 0,$pdf);
        $pdf->SetFont('Vera','',7);

        $totLoyerHT = $contratGA->getLoyerTot();
        $durTot = $contratGA->duree;
        $nbIterTot = $contratGA->NbIterTot;
        $periodicite = $contratGA->periodicite;

        $echu = ($contratGA->echu=='1'?'à terme échu':'terme à échoir');
        $html ='Le loyer ferme et non révisable en cours de contrat, payable par '.$echu.', par prélèvements automatiques est fixé à :<br>';
        $pdf->lasth=4;
        $this->writeHTML($html, 4, 0,$pdf);
        $larg1 = ($this->page_largeur - $this->marge_gauche - $this->marge_droite) / 4;
        $pdf->SetX($this->marge_gauche);
        $curY = $pdf->GetY();
        $pdf->MultiCell($larg1,'6', utf8_decode('NOMBRE DE LOYERS'."\n".$nbIterTot),1,'C',0 );
        $pdf->SetY($curY);
        $pdf->SetX($larg1+$this->marge_gauche);
        $pdf->MultiCell($larg1,'6', utf8_decode('MONTANT'."\n".price(round($totLoyerHT * 100)/100))." ".chr(128),1,'C',0 );
        $pdf->SetY($curY);
        $pdf->SetX($larg1*2+$this->marge_gauche);
        $pdf->MultiCell($larg1,'6', utf8_decode('PERIODICITE'."\n".$periodicite),1,'C',0 );
        $pdf->SetY($curY);
        $pdf->SetX($larg1*3+$this->marge_gauche);
        $pdf->MultiCell($larg1,'6', utf8_decode('DUREE'."\n".$durTot.' mois'),1,'C',0 );

        $datecontratGA = date('d/m/Y',($contratGA->mise_en_service > 0?$contratGA->mise_en_service:time()));
        $html = '<br><strong>Site d'.chr(146).'installation :</strong><br><strong>Date d'.chr(146).'installation :</strong> '.$datecontratGA.'<br><strong>Clause spécifique :</strong><br>Fait en autant d'.chr(146).'exemplaires que de parties, un pour chacune des parties<br>ANNEXE : Conditions Générales composées de deux pages recto : Feuillet A et Feuillet B';
        $pdf->lasth=5;
        $this->writeHTML($html, 4, 0,$pdf);

        $pdf->lasth=4;
        $html =  '<br>Fait à '.strtoupper($tmpFourn->ville).' le : '.$datecontratGA.'<br><br>';
        $this->writeHTML($html, 4, 0,$pdf);
        $pdf->SetFont('Vera','B',7);
        $block1 = 'Pour le Locataire'."\n".'Signature et cachet (lu et approuvé)'."\n".'Qualité'."\n".'Nom';
        $block2 = 'Pour le Loueur'."\n".'Signature et cachet';
        $block3 = 'Pour le Cessionnaire'."\n".'Signature et cachet';
        $curY = $pdf->GetY();
        $larg1 = ($this->page_largeur - ($this->marge_gauche + $this->marge_droite)) / 3;
        $pdf->SetY($curY);
        $pdf->SetX($this->marge_gauche);
        $pdf->MultiCell($larg1,'4',utf8_decode($block1),0,"L",0);
        $pdf->SetY($curY);
        $pdf->SetX($this->marge_gauche + $larg1);
        $pdf->MultiCell($larg1,'4',utf8_decode($block2),0,"C",0);
        $pdf->SetY($curY);
        $pdf->SetX($this->marge_gauche + $larg1 * 2);
        $pdf->MultiCell($larg1,'4',utf8_decode($block3),0,"R",0);

        $pdf->SetFont('Vera','',7);
        // Pied de page
        $this->_pagefoot($pdf);

    }


    function page_PV_reception(&$pdf,$contratGA,$tmpSignature)
    {
        global $outputlangs, $langs, $conf,$mysoc;
        $tmpFourn = new Societe($this->db);
        $tmpFourn->fetch($contratGA->fournisseur_refid);
        $fournisseur_name = utf8_encode($tmpFourn->nom);

        $pdf->AddPage();

        $pdf->SetDrawColor(128,128,128);

        $pdf->SetTitle($contratGA->ref);
        $pdf->SetSubject($outputlangs->transnoentities("Contract"));
        $pdf->SetCreator("GLE ".GLE_VERSION);
        $pdf->SetAuthor($user->fullname);

        $pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite);   // Left, Top, Right
        $pdf->SetAutoPageBreak(0,0);

        // Tete de page
        $this->_pagehead3($pdf, $contratGA, 1);

        $pagenb = 1;
        $tab_top = 48;
        $tab_top_newpage = 7;
        $tab_height = 50;
        $tab_height_newpage = 50;
        $tab_height_middlepage = 50;
        $iniY = $tab_top + 8;
        $curY = $tab_top + 8;
        $nexY = $tab_top + 8;

        $pdf->SetFillColor(255,255,255);
        $pdf->SetDrawColor(0,0,0);
        $pdf->SetTextColor(0,0,0);
        $pdf->SetFont('Vera','B',19);
        $pdf->Ln(8);

        //en tete
        $pdf->SetY(50);
        $pdf->SetX($this->marge_gauche*3);
        $html = 'PROCES VERBAL DE RECEPTION ET MISE EN SERVICE DE MATERIEL';
        $pdf->MultiCell(($this->page_largeur - ($this->marge_gauche+$this->marge_droite) * 3),10,utf8_decode($html),1,'C',0);

        $pdf->SetFont('Vera','',10);
        $head1 = 'ADRESSE DU LOCATAIRE : ';
        $head2 = 'ADRESSE  DU VENDEUR : ';
        $addr1 = utf8_encode($tmpFourn->nom."\n".$tmpFourn->adresse."\n".$tmpFourn->cp." ".$tmpFourn->ville);
        $addr2 = utf8_encode($mysoc->nom."\n".$mysoc->adresse."\n".$mysoc->cp." ".$mysoc->ville);
        $pdf->SetY($pdf->GetY()+5);
        $curY=$pdf->GetY();
        $pdf->SetX($this->marge_gauche*2);
        $pdf->SetFont('Vera','B',8);
        $pdf->MultiCell(86,6,utf8_decode($head1),0,'C',0);
        $pdf->SetFont('Vera','',10);
        $pdf->SetX($this->marge_gauche*2);
        $pdf->MultiCell(86,6,utf8_decode($addr1),0,'C',0);
        $pdf->SetY($curY);
        $pdf->SetX($this->marge_gauche*2 + 86 +  ($this->page_largeur - 86 - $this->marge_gauche*2 - $this->marge_droite*2 - 86) );
        $pdf->SetFont('Vera','B',8);
        $pdf->MultiCell(86,6,utf8_decode($head2),0,'C',0);
        $pdf->SetFont('Vera','',10);
        $pdf->SetX($this->marge_gauche*2 + 86 +  ($this->page_largeur - 86 - $this->marge_gauche*2 - $this->marge_droite*2 - 86) );
        $pdf->MultiCell(86,6,utf8_decode($addr2),0,'C',0);


        //intitulé
        $pdf->SetY($pdf->GetY()+20);
        $pdf->lasth=4;
        $pdf->SetFont('Vera','',18);
        $html='CONTRAT DE LOCATION N° ';
        $tmpWidth = $pdf->GetStringWidth($html . " ".$contratGA->ref );
        $tmpWidth1 = $pdf->GetStringWidth($html);
        $pdf->setX($this->page_largeur / 2  - ($tmpWidth/2));

        $pdf->MultiCell(90,'6', utf8_decode($html),0,'R',0 );
        $html = $contratGA->ref;
        $pdf->SetY($pdf->GetY()-12);
        $pdf->setX($this->page_largeur / 2 - ($tmpWidth/2) + $tmpWidth1 );
        $pdf->SetTextColor(38,0,255);
        $pdf->MultiCell($this->page_largeur / 2 - ($this->marge_gauche / 2),'6', utf8_decode($html),0,'L',0 );
        $pdf->SetTextColor(0,0,0);


        $pdf->SetY($pdf->GetY()+10);
        //Tableau principal
        $pdf->SetFont('Vera','B',8);
        $larg1=($this->page_largeur) / 6;
        $pdf->SetMargins($larg1, $this->marge_haute, $larg1);   // Left, Top, Right
        $pdf->setX($larg1);
        $curY = $pdf->GetY();
        $pdf->SetFillColor(255,255,255);

        $pdf->SetFillColor(91,148,226);
        $pdf->MultiCell($larg1,'6', utf8_decode('Quantité'),0,'C',1 );
        $pdf->SetY($curY);
        $pdf->SetX($pdf->GetX()+$larg1);
        $pdf->MultiCell($larg1*2,'6', utf8_decode('Designation'),0,'C',1 );
        $pdf->SetY($curY);
        $pdf->SetX($pdf->GetX()+$larg1*3);
        $pdf->MultiCell($larg1,'6', utf8_decode('Marque'),0,'C',1 );
        $pdf->SetFont('Vera','',7);

        $contratGA->fetch_lignes();
        foreach($contratGA->lignes as $key=>$val)
        {
            if ($val->fk_product > 0)
            {
                $curY = $pdf->GetY();
                $pdf->MultiCell($larg1,'4', utf8_decode($val->qty),0,'C',0 );
                $pdf->SetY($curY);
                $pdf->SetX($pdf->GetX()+$larg1);
                $pdf->MultiCell($larg1*2,'4', utf8_decode($val->description),0,'C',0 );
                $pdf->SetY($curY);
                $pdf->SetX($pdf->GetX()+$larg1*3);
                $pdf->MultiCell($larg1,'4', utf8_decode('Marque'),0,'C',0 );
            }
        }

        $pdf->SetFont('Vera','',8);
        $pdf->SetFillColor(255,255,255);
        $pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite);   // Left, Top, Right
        //blabla
        $html = '<br><br>Le locataire a choisi librement et sous sa responsabilité les équipements, objets du présent contrat, en s'.chr(146).'assurant auprès de ses fournisseurs de leur compatibilité  y compris dans le cas où ils sont incorporés dans un système informatique préexistant.<br><br>Le vendeur déclare que le matériel, ci-dessus désigné, a bien été mis en service selon les normes du constructeur, et le locataire déclare avoir, ce jour, réceptionné ce matériel sans aucune réserve, en bon état de marche, sans vice ni défaut apparent et conforme à la commande passée au fournisseur. En conséquence, le locataire déclare accepter ledit matériel sans restriction, ni réserve, compte tenu du mandat qui lui a été fait par '.$mysoc->nom.'.<br><br>';
        $this->writeHTML($html, 4, 0,$pdf);
        $html = 'FAIT EN DOUBLE EXEMPLAIRE,  UN POUR CHACUNE DES PARTIES<br>';
        $this->writeHTML($html, 4, 0,$pdf);

        $datecontratGA = date('d/m/Y',($contratGA->mise_en_service > 0?$contratGA->mise_en_service:time()));

        $html = 'Fait à  '.utf8_decode($tmpFourn->ville). ' le '.$datecontratGA."<br>";
        $this->writeHTML($html, 4, 0,$pdf);

        $pdf->SetFont('Vera','B',7);
        $block1 = 'Pour le Locataire'."\n".'Signature et cachet (lu et approuvé)'."\n".'Qualité'."\n".'Nom';
        $block2 = 'Pour le Loueur'."\n".'Signature et cachet';
        $curY = $pdf->GetY();
        $larg1 = ($this->page_largeur - ($this->marge_gauche + $this->marge_droite)) / 2;
        $pdf->SetY($curY);
        $pdf->SetX($this->marge_gauche);
        $pdf->MultiCell($larg1,'4',utf8_decode($block1),0,"C",0);
        $pdf->SetY($curY);
        $pdf->SetX($this->marge_gauche + $larg1);
        $pdf->MultiCell($larg1,'4',utf8_decode($block2),0,"C",0);


        // Pied de page
        $this->_pagefoot($pdf);


    }

    function page_cessioncontratGA(&$pdf,$contratGA,$tmpSignature)
    {
        global $outputlangs, $langs, $conf;
        $pdf->AddPage();

        $pdf->SetDrawColor(128,128,128);

        $pdf->SetTitle($contratGA->ref);
        $pdf->SetSubject($outputlangs->transnoentities("Contract"));
        $pdf->SetCreator("GLE ".GLE_VERSION);
        $pdf->SetAuthor($user->fullname);

        $pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite);   // Left, Top, Right
        $pdf->SetAutoPageBreak(0,0);

        // Tete de page
        $this->_pagehead2($pdf, $contratGA, 1);

        $pagenb = 1;
        $tab_top = 48;
        $tab_top_newpage = 7;
        $tab_height = 50;
        $tab_height_newpage = 50;
        $tab_height_middlepage = 50;
        $iniY = $tab_top + 8;
        $curY = $tab_top + 8;
        $nexY = $tab_top + 8;

        $pdf->SetFillColor(255,255,255);
        $pdf->SetDrawColor(0,0,0);
        $pdf->SetTextColor(0,0,0);
        $pdf->SetFont('Vera','',10);
        $pdf->Ln(8);

        $pdf->SetY(110);

        $html = "Objet : Contrat de location n° ".$contratGA->ref;
        $this->writeHTML($html, 4, 0,$pdf);
        $pdf->SetMargins($this->marge_gauche * 3.5, $this->marge_haute, $this->marge_droite* 3.5);   // Left, Top, Right
        $tmpCess = new Societe($this->db);
        $tmpCess->fetch($contratGA->cessionnaire_refid);
        $cessionnaire_name = $tmpCess->nom;
        $date_contratGA=$contratGA->mise_en_service;
        $tmpFourn = new Societe($this->db);
        $tmpFourn->fetch($contratGA->fournisseur_refid);
        $fournisseur_name = utf8_encode($tmpFourn->nom);
        $numFacture="";
        $contratGA->contratCheck_link();
        require_once(DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php');
        $fact = new Facture($this->db);
        foreach($contratGA->linkedArray['fa'] as $key=>$val)
        {
            $fact->fetch($val);
            $numFacture = $fact->ref;
        }
        $numFactureFourn="";
        $html = '<br><br>Madame, Monsieur,<br><br>Nous vous prions de bien vouloir trouver sous ce pli le contrat de location N°'.chr(160).'<strong>'.$contratGA->ref.'</strong> concernant : <br><br><strong>'.$fournisseur_name.'</strong><br><br>et contenant : <br><br>- les conditions générales et particulières en trois exemplaires,<br>- le procès verbal de mise à disposition du matériel, <br>- l'.chr(146).'autorisation de prélèvement et le RIB, <br>- notre facture de vente N°'.$numFacture.' <br>- la copie de la facture fournisseur N°'.$numFactureFourn.' <br>Nous vous souhaitons bonne réception de ces éléments, et vous prions d'.chr(146).'agréer nos sincères salutations.<br>';
        $pdf->lasth=5;
        $this->writeHTML($html, 8, 0,$pdf);

        $html = $tmpSignature->fullname;
        $pdf->SetX($this->marge_gauche * 4);
        $pdf->MultiCell($this->page_largeur- 3.5 * ($this->marge_gauche + $this->marge_droite),'6', utf8_decode($html),0,'R',0 );


        // Pied de page
        $this->_pagefoot($pdf);

    }



    function page_retourcontratGA(&$pdf,$contratGA,$tmpSignature)
    {
        // Tete de page
        $pdf->AddPage();
        $this->_pagehead($pdf, $contratGA, 1);

        $pagenb = 1;
        $tab_top = 48;
        $tab_top_newpage = 7;
        $tab_height = 50;
        $tab_height_newpage = 50;
        $tab_height_middlepage = 50;
        $iniY = $tab_top + 8;
        $curY = $tab_top + 8;
        $nexY = $tab_top + 8;

        $pdf->SetFillColor(255,255,255);
        $pdf->SetDrawColor(0,0,0);
        $pdf->SetTextColor(0,0,0);
        $pdf->SetFont('Vera','',10);
        $pdf->Ln(8);

        $pdf->SetY(110);

        $html = "Objet : Contrat de location n° ".$contratGA->ref;
        $this->writeHTML($html, 4, 0,$pdf);
        $pdf->SetMargins($this->marge_gauche * 3.5, $this->marge_haute, $this->marge_droite* 3.5);   // Left, Top, Right
        $tmpCess = new Societe($this->db);
        $tmpCess->fetch($contratGA->cessionnaire_refid);
        $cessionnaire_name = $tmpCess->nom;
        $date_contratGA=$contratGA->mise_en_service;
        $html = '<br><br>Madame, Monsieur,<br><br>Nous avons le plaisir de vous faire parvenir votre exemplaire de contrat concernant la location de vos équipements professionnels.<br><br>Nous vous rappelons que conformément à l'.chr(146).'article 8 de nos conditions générales, nous avons chargé notre partenaire, <strong>'.$cessionnaire_name.'</strong> des prélèvements de notre contrat depuis le '.$date_contratGA.'<br><br>Restant à votre disposition pour le financement en location évolutive de l'.chr(146).'ensemble des équipements liés au traitement de l'.chr(146).'information ainsi qu'.chr(146).'à la communication d'.chr(146).'entreprises (Données, voix, écrits, images), et d'.chr(146).'étudier tout projet dans ces domaines. <br><br>Nous vous souhaitons bonne réception de la présente, et vous prions d'.chr(146).'agréer, Madame, Monsieur, l'.chr(146).'expression de nos salutations distinguées.<br>';
        $pdf->lasth=5;
        $this->writeHTML($html, 8, 0,$pdf);

        $html = $tmpSignature->fullname;
        $pdf->SetX($this->marge_gauche * 4);
        $pdf->MultiCell($this->page_largeur- 3.5 * ($this->marge_gauche + $this->marge_droite),'6', utf8_decode($html),0,'R',0 );


        // Pied de page
        $this->_pagefoot($pdf);
    }


    function page_presentation(&$pdf,$contratGA,$tmpSignature)
    {
        global $outputlangs , $langs;
        // Tete de page
        $pdf->AddPage();
        $this->_pagehead($pdf, $contratGA, 1);

        $pagenb = 1;
        $tab_top = 48;
        $tab_top_newpage = 7;
        $tab_height = 50;
        $tab_height_newpage = 50;
        $tab_height_middlepage = 50;
        $iniY = $tab_top + 8;
        $curY = $tab_top + 8;
        $nexY = $tab_top + 8;

        $pdf->SetFillColor(255,255,255);
        $pdf->SetDrawColor(0,0,0);
        $pdf->SetTextColor(0,0,0);
        $pdf->SetFont('Vera','',10);
        $pdf->Ln(8);

        $pdf->SetY(110);

        $html = "Objet : Contrat de location n° ".$contratGA->ref;
        $this->writeHTML($html, 4, 0,$pdf);
        $pdf->SetMargins($this->marge_gauche * 3.5, $this->marge_haute, $this->marge_droite* 3.5);   // Left, Top, Right
        $pdf->SetX($this->marge_gauche * 2);
        $html = "<br><br>Madame, Monsieur,<br><br>Nous vous prions de trouver sous ce pli le contrat concernant la location de votre matériel professionnel, et comprenant :<br><br><br>- les conditions particulières et générales,<br>- le procès verbal de mise à disposition du matériel,<br>- une autorisation de prélèvement<br><br><br><br>Nous vous remercions de bien vouloir nous  retourner l".chr(146)."ensemble de ces documents <bu>datés, signés et revêtus des cachets commerciaux</bu> avec un RIB à l".chr(146)."adresse suivante :<br><br>";
        $pdf->lasth=5;
        $this->writeHTML($html, 4, 0,$pdf);
        global $mysoc;
        $html = $mysoc->nom . "\n". $mysoc->adresse . "\n". $mysoc->cp . " ".$mysoc->ville;

        $pdf->SetFont('Vera','B',10);
        $pdf->MultiCell($this->page_largeur- 3.5 * ($this->marge_gauche + $this->marge_droite),'6', utf8_decode($html),0,'C',0 );
        $pdf->SetFont('Vera','',10);

        $html = "<br><br>Dans l".chr(146)."attente, nous vous souhaitons bonne réception de la présente et vous prions d".chr(146)."agréer, Madame, Monsieur, nos sincères salutations.<br><br><br>";
        $pdf->lasth=5;
        $this->writeHTML($html, 4, 0,$pdf);

        $html = $tmpSignature->fullname;
        $pdf->SetX($this->marge_gauche * 4);
        $pdf->MultiCell($this->page_largeur- 3.5 * ($this->marge_gauche + $this->marge_droite),'6', utf8_decode($html),0,'R',0 );


                    // Pied de page
        $this->_pagefoot($pdf);
   }


    function _pagehead(& $pdf, $object, $showadress = 1) {
        global $conf, $langs,$outputlangs;

        $outputlangs->load("main");
        $outputlangs->load("bills");
        $outputlangs->load("contract");
        $outputlangs->load('synopsisGene@Synopsis_Tools');
        $outputlangs->load("companies");

        //Affiche le filigrane brouillon - Print Draft Watermark
        if($object->statut==0 && (! empty($conf->global->CONTRATGA_DRAFT_WATERMARK)) )
        {
            $watermark_angle=atan($this->page_hauteur/$this->page_largeur);
            $watermark_x=5;
            $watermark_y=$this->page_hauteur-25;  //Set to $this->page_hauteur-50 or less if problems
            $watermark_width=$this->page_hauteur;
            $pdf->SetFont('Vera','B',50);
            $pdf->SetTextColor(255,192,203);
            //rotate
            $pdf->_out(sprintf('q %.5F %.5F %.5F %.5F %.2F %.2F cm 1 0 0 1 %.2F %.2F cm',cos($watermark_angle),sin($watermark_angle),-sin($watermark_angle),cos($watermark_angle),$watermark_x*$pdf->k,($pdf->h-$watermark_y)*$pdf->k,-$watermark_x*$pdf->k,-($pdf->h-$watermark_y)*$pdf->k));
            //print watermark
            $pdf->SetXY($watermark_x,$watermark_y);
            $pdf->Cell($watermark_width,25,clean_html($conf->global->CONTRATGA_DRAFT_WATERMARK),0,2,"C",0);
            //antirotate
            $pdf->_out('Q');
        }

        //Prepare la suite
        $pdf->SetTextColor(0,0,60);
        $pdf->SetFont('Vera','B',13);

        $posy=50;

        $pdf->SetY($posy);
        $pdf->SetX($this->marge_gauche);
        $logo = false;
        if (is_file ($conf->societe->dir_logos.'/'.$this->emetteur->logo."noalpha.png"))
        {
            $logo=$conf->societe->dir_logos.'/'.$this->emetteur->logo."noalpha.png";
        } else {
            $logo=$conf->societe->dir_logos.'/'.$this->emetteur->logo;
        }
        if ($this->emetteur->logo)
        {
            if (is_readable($logo))
            {
                $pdf->Image($logo, ($this->page_largeur / 2) - 30 , 10, 0, 24,"","http://www.finapro.fr/");
            } else {
                $pdf->SetTextColor(200,0,0);
                $pdf->SetFont('Vera','B',8);
                $pdf->MultiCell(100, 3, $outputlangs->transnoentities("ErrorLogoFileNotFound",$logo), 0, 'L');
                $pdf->MultiCell(100, 3, $outputlangs->transnoentities("ErrorGoToGlobalSetup"), 0, 'L');
            }
        } else if (defined("FAC_PDF_INTITULE")) {
            $pdf->MultiCell(100, 4, FAC_PDF_INTITULE, 0, 'L');
        }


        if ($showadress)
        {
//            // Emetteur
            $hautcadre=40;
            // Client destinataire
            $posy=48;
            $pdf->SetTextColor(0,0,0);
            $pdf->SetFont('Vera','',11);
            $object->fetch_client();

            // If BILLING contact defined on invoice, we use it
            $usecontact=false;
            if ($conf->global->CONTRATGA_USE_CUSTOMER_CONTACT_AS_RECIPIENT != 1)
            {
                $conf->global->CONTRATGA_USE_CUSTOMER_CONTACT_AS_RECIPIENT=1;
            }
            if ($conf->global->CONTRATGA_USE_CUSTOMER_CONTACT_AS_RECIPIENT)
            {
                $arrayidcontact=$object->getIdContact('external','BILLING');

                if (sizeof($arrayidcontact) > 0)
                {
                    $usecontact=true;
                    $result=$object->fetch_contact($arrayidcontact[0]);
                }
            }
            if (!$usecontact)
            {
                // Nom societe
                $pdf->SetXY(102,$posy+3);
                $pdf->SetFont('Vera','B',10);
                $pdf->MultiCell(96,4, $object->client->nom, 0, 'L');

                // Nom client
                $carac_client = "\n".$object->contact->getFullName($outputlangs,1,1);

                // Caracteristiques client
                $carac_client.="\n".$object->contact->adresse;
                $carac_client.="\n".$object->contact->cp . " " . $object->contact->ville."\n";
                //Pays si different de l'emetteur
                if ($this->emetteur->pays_code != $object->contact->pays_code)
                {
                    $carac_client.=dol_entity_decode($object->contact->pays)."\n";
                }
            } else {
                // Nom client
                $pdf->SetXY(102,$posy+3);
                $pdf->SetFont('Vera','B',11);
                $pdf->MultiCell(96,4, $object->client->nom, 0, 'L');

                // Nom du contact suivi contratGA si c'est une societe
                $arrayidcontact = $object->getIdContact('external','BILLING');
                if (sizeof($arrayidcontact) > 0)
                {
                    $object->fetch_contact($arrayidcontact[0]);
                    // On verifie si c'est une societe ou un particulier
                    if( !preg_match('#'.$object->contact->getFullName($outputlangs,1).'#isU',$object->client->nom) )
                    {
                        $carac_client .= "\n ".$object->contact->getFullName($outputlangs,1,1);
                    }
                }

                // Caracteristiques client
                $carac_client.="\n".$object->client->adresse;
                $carac_client.="\n".$object->client->cp . " " . $object->client->ville."\n";

                //Pays si different de l'emetteur
                if ($this->emetteur->pays_code != $object->client->pays_code)
                {
                    $carac_client.=dol_entity_decode($object->client->pays)."\n";
                }
            }
//            // Numero TVA intracom
//            if ($object->client->tva_intra) $carac_client.="\n".$outputlangs->transnoentities("VATIntraShort").': '.$object->client->tva_intra;

            $pdf->SetFont('Vera','',11);
            $posy=$pdf->GetY()-9; //Auto Y coord readjust for multiline name
            $pdf->SetXY(102,$posy+6);
            $pdf->MultiCell(86,4, $carac_client);

            //Date et Lieu
            $pdf->SetFont('Vera','',10);
            $posy=$pdf->GetY() + 10; //Auto Y coord readjust for multiline name
            $pdf->SetXY(102,$posy);
            setlocale (LC_TIME, 'fr_FR');
            $pdf->MultiCell(86,4, "Jouques, le ".strftime("%A %d %B %Y"));
            //exit();
        }


    }

    function _pagehead2(& $pdf, $object, $showadress = 1) {
        global $conf, $langs, $outputlangs;

        $outputlangs->load("main");
        $outputlangs->load("bills");
        $outputlangs->load("contract");
        $outputlangs->load('synopsisGene@Synopsis_Tools');
        $outputlangs->load("companies");

        //Affiche le filigrane brouillon - Print Draft Watermark
        if($object->statut==0 && (! empty($conf->global->CONTRATGA_DRAFT_WATERMARK)) )
        {
            $watermark_angle=atan($this->page_hauteur/$this->page_largeur);
            $watermark_x=5;
            $watermark_y=$this->page_hauteur-25;  //Set to $this->page_hauteur-50 or less if problems
            $watermark_width=$this->page_hauteur;
            $pdf->SetFont('Vera','B',50);
            $pdf->SetTextColor(255,192,203);
            //rotate
            $pdf->_out(sprintf('q %.5F %.5F %.5F %.5F %.2F %.2F cm 1 0 0 1 %.2F %.2F cm',cos($watermark_angle),sin($watermark_angle),-sin($watermark_angle),cos($watermark_angle),$watermark_x*$pdf->k,($pdf->h-$watermark_y)*$pdf->k,-$watermark_x*$pdf->k,-($pdf->h-$watermark_y)*$pdf->k));
            //print watermark
            $pdf->SetXY($watermark_x,$watermark_y);
            $pdf->Cell($watermark_width,25,clean_html($conf->global->CONTRATGA_DRAFT_WATERMARK),0,2,"C",0);
            //antirotate
            $pdf->_out('Q');
        }

        //Prepare la suite
        $pdf->SetTextColor(0,0,60);
        $pdf->SetFont('Vera','B',13);

        $posy=50;

        $pdf->SetY($posy);
        $pdf->SetX($this->marge_gauche);
        $logo = false;
        if (is_file ($conf->societe->dir_logos.'/'.$this->emetteur->logo."noalpha.png"))
        {
            $logo=$conf->societe->dir_logos.'/'.$this->emetteur->logo."noalpha.png";
        } else {
            $logo=$conf->societe->dir_logos.'/'.$this->emetteur->logo;
        }
        if ($this->emetteur->logo)
        {
            if (is_readable($logo))
            {
                $pdf->Image($logo, ($this->page_largeur / 2) - 30 , 10, 0, 24,"","http://www.finapro.fr/");
            } else {
                $pdf->SetTextColor(200,0,0);
                $pdf->SetFont('Vera','B',8);
                $pdf->MultiCell(100, 3, $outputlangs->transnoentities("ErrorLogoFileNotFound",$logo), 0, 'L');
                $pdf->MultiCell(100, 3, $outputlangs->transnoentities("ErrorGoToGlobalSetup"), 0, 'L');
            }
        } else if (defined("FAC_PDF_INTITULE")) {
            $pdf->MultiCell(100, 4, FAC_PDF_INTITULE, 0, 'L');
        }


        if ($showadress)
        {
//            // Emetteur
            $hautcadre=40;
            // Client destinataire
            $posy=48;
            $pdf->SetTextColor(0,0,0);
            $pdf->SetFont('Vera','',11);

            // Nom cessionnaire
            $pdf->SetXY(102,$posy+3);
            $pdf->SetFont('Vera','B',11);
            $tmpSoc=new Societe($this->db);
            $tmpSoc->fetch($object->cessionnaire_refid);

            $pdf->MultiCell(96,4, $tmpSoc->nom, 0, 'L');
            $sql = "SELECT p.rowid, p.name, p.firstname, p.poste, p.phone, p.fax, p.email, p.note ";
            $sql .= " FROM ".MAIN_DB_PREFIX."socpeople as p";
            $sql .= " WHERE p.fk_soc = ".$objsoc->id;
            $sql .= " ORDER by p.datec";

            $result = $this->db->query($sql);
            $row = $this->db->fetch_object($result);

            $carac_client = "\n " .$row->name . " ".$row->firstname;

            // Caracteristiques client
            $carac_client.="\n".$tmpSoc->adresse;
            $carac_client.="\n".$tmpSoc->cp . " " . $tmpSoc->ville."\n";


            $pdf->SetFont('Vera','',11);
            $posy=$pdf->GetY()-9; //Auto Y coord readjust for multiline name
            $pdf->SetXY(102,$posy+6);
            $pdf->MultiCell(86,4, $carac_client);

            //Date et Lieu
            $pdf->SetFont('Vera','',10);
            $posy=$pdf->GetY() + 10; //Auto Y coord readjust for multiline name
            $pdf->SetXY(102,$posy);
            setlocale (LC_TIME, 'fr_FR');
            $pdf->MultiCell(86,4, "Jouques, le ".strftime("%A %d %B %Y"));
            //exit();
        }


    }

    function _pagehead3(&$pdf, $object,$displayLogo=1) {
        global $conf, $langs, $outputlangs;

        $outputlangs->load("main");
        $outputlangs->load("bills");
        $outputlangs->load("contract");
        $outputlangs->load('synopsisGene@Synopsis_Tools');
        $outputlangs->load("companies");

        //Affiche le filigrane brouillon - Print Draft Watermark
        if($object->statut==0 && (! empty($conf->global->CONTRATGA_DRAFT_WATERMARK)) )
        {
            $watermark_angle=atan($this->page_hauteur/$this->page_largeur);
            $watermark_x=5;
            $watermark_y=$this->page_hauteur-25;  //Set to $this->page_hauteur-50 or less if problems
            $watermark_width=$this->page_hauteur;
            $pdf->SetFont('Vera','B',50);
            $pdf->SetTextColor(255,192,203);
            //rotate
            $pdf->_out(sprintf('q %.5F %.5F %.5F %.5F %.2F %.2F cm 1 0 0 1 %.2F %.2F cm',cos($watermark_angle),sin($watermark_angle),-sin($watermark_angle),cos($watermark_angle),$watermark_x*$pdf->k,($pdf->h-$watermark_y)*$pdf->k,-$watermark_x*$pdf->k,-($pdf->h-$watermark_y)*$pdf->k));
            //print watermark
            $pdf->SetXY($watermark_x,$watermark_y);
            $pdf->Cell($watermark_width,25,clean_html($conf->global->CONTRATGA_DRAFT_WATERMARK),0,2,"C",0);
            //antirotate
            $pdf->_out('Q');
        }

        //Prepare la suite
        $pdf->SetTextColor(0,0,60);
        $pdf->SetFont('Vera','B',13);

        $posy=10;

        $pdf->SetY($posy);
        $pdf->SetX($this->marge_gauche);
        $logo = false;
        if (is_file ($conf->societe->dir_logos.'/'.$this->emetteur->logo."noalpha.png"))
        {
            $logo=$conf->societe->dir_logos.'/'.$this->emetteur->logo."noalpha.png";
        } else {
            $logo=$conf->societe->dir_logos.'/'.$this->emetteur->logo;
        }
        if ($this->emetteur->logo && $displayLogo)
        {
            if (is_readable($logo))
            {
                $pdf->Image($logo, ($this->page_largeur / 2) - 30 , 10, 0, 24,"","http://www.finapro.fr/");
            } else {
                $pdf->SetTextColor(200,0,0);
                $pdf->SetFont('Vera','B',8);
                $pdf->MultiCell(100, 3, $outputlangs->transnoentities("ErrorLogoFileNotFound",$logo), 0, 'L');
                $pdf->MultiCell(100, 3, $outputlangs->transnoentities("ErrorGoToGlobalSetup"), 0, 'L');
            }
        } else if (defined("FAC_PDF_INTITULE") && $displayLogo) {
            $pdf->MultiCell(100, 4, FAC_PDF_INTITULE, 0, 'L');
        }
    }


    /*
    *   \brief      Affiche le pied de page
    *   \param      pdf     objet PDF
    */
    function _pagefoot(&$pdf)
    {
        global $outputlangs;
        return pdf_pagefoot($pdf,$outputlangs,'CONTRATGA_FREE_TEXT',$this->emetteur,$this->marge_basse,$this->marge_gauche ,$this->page_hauteur);
    }
    function writeHTML($html, $ln=2, $fill=0,&$pdf)
    {
        // store some variables
        $html=strip_tags($html,"<h1><h2><h3><h4><h5><h6><bi><bu><ub><strong><u><ui><iu><uib><ubi><bui><biu><uib><ubi><i><center><a><img><p><br><br/><strong><em><font><span><blockquote><li><ul><ol><hr><td><th><tr><table><sup><sub><small>"); //remove all unsupported tags
        //replace carriage returns, newlines and tabs
        $repTable = array("\t" => " ", "\n" => "<br>", "\r" => " ", "\0" => " ", "\x0B" => " ");
        $html = strtr($html, $repTable);
        $pattern = '/(<[^>]+>)/U';
        $a = preg_split($pattern, $html, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY); //explodes the string

        if (empty($pdf->lasth)) {
            $pdf->lasth = $pdf->FontSize * K_CELL_HEIGHT_RATIO / 2;
        }
        foreach($a as $key=>$element) {
            if (!preg_match($pattern, $element)) {
                //Text
                if($this->HREF) {
                    $pdf->addHtmlLink($this->HREF, $element, $fill);
                } else {
                    $pdf->Write($pdf->lasth , stripslashes(utf8_decode($pdf->unhtmlentities($element))), '', $fill);
                }
            } else {
                $element = substr($element, 1, -1);
                //Tag
                if($element{0}=='/') {
                    $this->closedHTMLTagHandler(strtolower(substr($element, 1)),$pdf);
                } else {
                    //Extract attributes
                    // get tag name
                    preg_match('/([a-zA-Z0-9]*)/', $element, $tag);
                    $tag = strtolower($tag[0]);
                    // get attributes
                    preg_match_all('/([^=\s]*)=["\']?([^"\']*)["\']?/', $element, $attr_array, PREG_PATTERN_ORDER);
                    $attr = array(); // reset attribute array

                    while(list($id,$name)=each($attr_array[1])) {
                        $attr[strtolower($name)] = $attr_array[2][$id];
                    }

                    $this->openHTMLTagHandler($tag, $attr, $fill,$pdf);
                }
            }
        }
        if ($ln) {
            $pdf->Ln($pdf->lasth);
        }
    }

    function closedHTMLTagHandler($tag,$pdf) {
        //Closing tag
        switch($tag) {
            case 'strong': {
                $pdf->setStyle('b', false);
                $pdf->setFont('Vera',"");
                break;
            }
            case 'em': {
                $pdf->setStyle('i', false);
                $pdf->setFont('Vera',"");
                break;
            }
            case 'center': {
                $pdf->SetX($this->marge_gauche);
                break;
            }

            case 'biu':
            case 'bui':
            case 'ubi':
            case 'uib':
            case 'ibu':
            case 'iub': {
                $pdf->setStyle('u', false);
                $pdf->setStyle('b', false);
                $pdf->setStyle('i', false);
                $pdf->setFont('',"");
                break;
            }
            case 'ib':
            case 'bi': {
                $pdf->setStyle('b', false);
                $pdf->setStyle('i', false);
                $pdf->setFont('',"");
                break;
            }
            case 'bu':
            case 'ub': {
                $pdf->setStyle('b', false);
                $pdf->setStyle('u', false);
                $pdf->setFont('',"");
                break;
            }
            case 'iu':
            case 'ui': {
                $pdf->setStyle('i', false);
                $pdf->setStyle('u', false);
                $pdf->setFont('',"");
                break;
            }
            case 'b':
            case 'i':
            case 'u': {
                $pdf->setStyle($tag, false);
                $pdf->setFont('Vera',"");
                break;
            }
            case 'a': {
                $pdf->HREF = '';
                break;
            }
            case 'small': {
                $currentFontSize = $pdf->FontSize;
                $pdf->SetFontSize($pdf->tempfontsize);
                $pdf->tempfontsize = $pdf->FontSizePt;
                $pdf->SetXY($pdf->GetX(), $pdf->GetY() - (($pdf->FontSize - $currentFontSize)/3));
                break;
            }
            case 'span':
            case 'font': {
                if ($pdf->issetcolor == true) {
                    $pdf->SetTextColor($pdf->prevTextColor[0], $pdf->prevTextColor[1], $pdf->prevTextColor[2]);
                }
                if ($pdf->issetfont) {
                    $pdf->FontFamily = $pdf->prevFontFamily;
                    $pdf->FontStyle = $pdf->prevFontStyle;
                    $pdf->SetFont($pdf->FontFamily);
                    $pdf->issetfont = false;
                }
                $currentFontSize = $pdf->FontSize;
                $pdf->SetFontSize($pdf->tempfontsize);
                $pdf->tempfontsize = $pdf->FontSizePt;
                //$pdf->TextColor = $pdf->prevTextColor;
                $pdf->lasth = $pdf->FontSize * K_CELL_HEIGHT_RATIO;
                break;
            }
        }
    }



    function AcceptPageBreak()
    {
        $this->_pagefoot();
        $this->AddPage();
        $this->_pageheader3();
        return(true);
    }

    function openHTMLTagHandler($tag, $attr, $fill=0,&$pdf) {
        //Opening tag
        switch($tag) {
            case 'hr': {
                $pdf->Ln();
                if ((isset($attr['width'])) AND ($attr['width'] != '')) {
                    $hrWidth = $attr['width'];
                }
                else {
                    $hrWidth = $pdf->w - $pdf->lMargin - $pdf->rMargin;
                }
                $x = $pdf->GetX();
                $y = $pdf->GetY();
                $pdf->SetLineWidth(0.2);
                $pdf->Line($x, $y, $x + $hrWidth, $y);
                $pdf->SetLineWidth(0.2);
                $pdf->Ln();
                break;
            }
            case 'strong': {
                $pdf->setStyle('B', true);
                $pdf->setFont('Vera',"B");
                //$this->write('aa');

                break;
            }
            case 'em': {
                $pdf->setStyle('i', true);
                $pdf->setFont('',"i");
                break;
            }
            case 'ib':
            case 'bi': {
                $pdf->setStyle('bi', true);
                $pdf->setFont('',"bi");
                break;
            }
            case 'ub':
            case 'bu': {
                $pdf->setStyle('b', true);
                $pdf->setStyle('u', true);
                $pdf->setFont('',"ub");
                break;
            }
            case 'iu':
            case 'ui': {
                $pdf->setStyle('i', true);
                $pdf->setStyle('u', true);
                $pdf->setFont('',"ui");
                break;
            }
            case 'biu':
            case 'bui':
            case 'ubi':
            case 'uib':
            case 'ibu':
            case 'iub': {
                $pdf->setStyle('b', true);
                $pdf->setStyle('i', true);
                $pdf->setStyle('u', true);
                $pdf->setFont('',"ubi");
                break;
            }
            case 'b':
            case 'i':
            case 'u': {
                $pdf->setStyle($tag, true);
                $pdf->setFont('',strtoupper($tag));
                break;
            }
            case 'a': {
                $pdf->HREF = $attr['href'];
                break;
            }
            case 'br': {
                $pdf->Ln();
                if(strlen($pdf->lispacer) > 0) {
                    $pdf->x += $pdf->GetStringWidth($pdf->lispacer);
                }
                break;
            }
            case 'center': {
                $pdf->setX($attr['centerx']);
                $pdf->x = $attr['centerx'];

                break;
            }
        }
    }
}

?>
