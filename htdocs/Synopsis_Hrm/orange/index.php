<?php
error_reporting(0);

ini_set('display_errors', 0);

/**
 * OrangeHRM is a comprehensive Human Resource Management (HRM) System that captures
 * all the essential functionalities required for any enterprise.
 * Copyright (C) 2006 OrangeHRM Inc., http://www.orangehrm.com
 *
 * OrangeHRM is free software; you can redistribute it and/or modify it under the terms of
 * the GNU General Public License as published by the Free Software Foundation; either
 * version 2 of the License, or (at your option) any later version.
 *
 * OrangeHRM is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
 * without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with this program;
 * if not, write to the Free Software Foundation, Inc., 51 Franklin Street, Fifth Floor,
 * Boston, MA  02110-1301, USA
 */
//charge la session dolibarr
// si l'utilisateur à les droits
// grant permission with l/p set in dolibarr pour le module HRM
//require_once("../../master.inc.php");
//print $user->id;


$_SESSION['isProjectAdmin'] = (isset($_SESSION['isProjectAdmin']) ? $_SESSION['isProjectAdmin'] : false);
$_SESSION['isSupervisor'] = (isset($_SESSION['isSupervisor']) ? $_SESSION['isSupervisor'] : false);
$_SESSION['isManager'] = (isset($_SESSION['isManager']) ? $_SESSION['isManager'] : false);
ob_start();
// Init session
if ($_COOKIE['adminRH'] == "admin" && $_COOKIE['Loggedin'] != 'True') {
    $_SESSION['isAdmin'] = "No";
    $_SESSION['isSupervisor'] = "No";
    $_SESSION['isManager'] = "No";
    $_SESSION['empID'] = "No";
    if (!isset($_SESSION['fname']) || !isset($_SESSION['empID'])) {
        require_once("../pre2.inc.php");
        $sessionname = _DOLNAME_;
        session_name($sessionname);
        session_start();
        //    var_dump($_SESSION);exit(0);

        if (!isset($_SESSION['fname']) || !isset($_SESSION['empID'])) {
            $_REQUEST['txtUserName'] = $conf->global->ORANGE_USER;
            $_REQUEST['txtPassword'] = $conf->global->ORANGE_PASS;

            $_POST['actionID'] = 'chkAuthentication';
            $_REQUEST['actionID'] = 'chkAuthentication';
            $_GET = "?module=Home&menu_no=3&menu_no_top=leave";

            header("Location: ./login.php?doliauth=1&module=Home&menu_no=3&menu_no_top=leave");
        }
    }
    $_SESSION['isAdmin'] = 'Yes';
} else if ($_COOKIE['Loggedin'] != 'True') {
    $_SESSION['isAdmin'] = "No";
    $_SESSION['isSupervisor'] = "No";
    $_SESSION['isManager'] = "No";
    $_SESSION['empID'] = "No";
    if (!isset($_SESSION['fname']) || !isset($_SESSION['empID'])) {
        require_once("../pre2.inc.php");
        $sessionname = _DOLNAME_;
        session_name($sessionname);
        session_start();
        $tabVerif = array("isSupervisor", "empID", "user", "recruitHomePage");
        foreach ($tabVerif as $cle) {
            $_SESSION[$cle] = (isset($_SESSION[$cle]) ? $_SESSION[$cle] : false);
        }

        if (!isset($_SESSION['fname']) || !isset($_SESSION['empID'])) {
//            $_REQUEST['txtUserName']=$conf->global->ORANGE_USER;
//            $_REQUEST['txtPassword']=$conf->global->ORANGE_PASS;

            $_REQUEST['txtUserName'] = $_SESSION['dol_login'];
            $requete = "SELECT pass FROM ".MAIN_DB_PREFIX."user WHERE login='" . $_SESSION['dol_login'] . "'";
            $sql = $db->query($requete);
            $res = $db->fetch_object($sql);

            $_REQUEST['txtPassword'] = $res->pass;

//        var_dump($_REQUEST);exit(0);

            $_POST['actionID'] = 'chkAuthentication';
            $_REQUEST['actionID'] = 'chkAuthentication';
            $_GET = "?module=Home&menu_no=3&menu_no_top=leave";

            header("Location: ./login.php?doliauth=1&module=Home&menu_no=3&menu_no_top=leave");
        }
    }
    $_SESSION['isAdmin'] = 'No';
} else {
    require_once("../pre2.inc.php");
    $sessionname = _DOLNAME_;
    session_name($sessionname);
    session_start();
}
define('ROOT_PATH', dirname(__FILE__));

if (!is_file(ROOT_PATH . '/lib/confs/Conf.php')) {
    header('Location: ./install.php');
    exit();
}
$tmpUser = new User($db);
$tmpUser->fetch($_COOKIE['userid']);
$fullname = $tmpUser->getFullName($langs);

//session_start();
//if(!isset($_SESSION['fname'])) {
//
//    header("Location: ./login.php");
//    exit();
//}

define('Admin', 'MOD001');
define('PIM', 'MOD002');
define('MT', 'MOD003');
define('Report', 'MOD004');
define('Leave', 'MOD005');
define('TimeM', 'MOD006');
define('Benefits', 'MOD007');
define('Recruit', 'MOD008');

$arrRights = array('add' => false, 'edit' => false, 'delete' => false, 'view' => false);


$arrAllRights = array(Admin => $arrRights,
    PIM => $arrRights,
    MT => $arrRights,
    Report => $arrRights,
    Leave => $arrRights,
    TimeM => $arrRights,
    Benefits => $arrRights,
    Recruit => $arrRights);

require_once ROOT_PATH . '/lib/models/maintenance/Rights.php';
require_once ROOT_PATH . '/lib/models/maintenance/UserGroups.php';
require_once ROOT_PATH . '/lib/common/CommonFunctions.php';
require_once ROOT_PATH . '/lib/common/Config.php';

$_SESSION['path'] = ROOT_PATH;

/* For checking TimesheetPeriodStartDaySet status : Begins */
if (Config::getTimePeriodSet()) {
    $_SESSION['timePeriodSet'] = 'Yes';
} else {
    $_SESSION['timePeriodSet'] = 'No';
}
/* For checking TimesheetPeriodStartDaySet status : Ends */

if ($_SESSION['isAdmin'] == 'Yes') {
    $rights = new Rights();

    $arrRights = array('add' => true, 'edit' => true, 'delete' => true, 'view' => true);

    foreach ($arrAllRights as $moduleCode => $currRights) {
        $arrAllRights[$moduleCode] = $rights->getRights($_SESSION['userGroup'], $moduleCode);
    }

    $ugroup = new UserGroups();
    $ugDet = $ugroup->filterUserGroups($_SESSION['userGroup']);

    $arrRights['repDef'] = $ugDet[0][2] == '1' ? true : false;
} else {

    /* Assign supervisors edit and view rights to the PIM
     * They have PIM rights over their subordinates, but they cannot add/delete
     * employees. But they have add/delete rights in the employee details page.
     */
    if (@$_SESSION['isSupervisor']) {
        $arrAllRights[PIM] = array('add' => false, 'edit' => true, 'delete' => false, 'view' => true);
    }

    /*
     * Assign Manager's access to recruitment module
     */
    if (@$_SESSION['isManager'] || @$_SESSION['isDirector'] || (isset($_SESSION['isAcceptor']) && $_SESSION['isAcceptor']) || (isset($_SESSION['isOfferer']) && $_SESSION['isOfferer'])) {
        $arrAllRights[Recruit] = array('add' => false, 'edit' => true, 'delete' => false, 'view' => true);
    }
}

if ((isset($_GET['menu_no_top'])) && ($_GET['menu_no_top'] == "eim"))
    $arrRights = $arrAllRights[Admin];

if ((isset($_GET['menu_no_top'])) && ($_GET['menu_no_top'] == "hr"))
    $arrRights = $arrAllRights[PIM];

if ((isset($_GET['menu_no_top'])) && ($_GET['menu_no_top'] == "mt"))
    $arrRights = $arrAllRights[MT];

if ((isset($_GET['menu_no_top'])) && ($_GET['menu_no_top'] == "rep"))
    $arrRights = $arrAllRights[Report];

if ((isset($_GET['menu_no_top'])) && ($_GET['menu_no_top'] == "leave"))
    $arrRights = $arrAllRights[Leave];

if ((isset($_GET['menu_no_top'])) && ($_GET['menu_no_top'] == "time"))
    $arrRights = $arrAllRights[TimeM];

if (isset($_GET['menu_no_top']) && ($_GET['menu_no_top'] == "recruit")) {
    $arrRights = $arrAllRights[Recruit];
}


$_SESSION['localRights'] = $arrRights;

//var_dump($_SESSION);
$styleSheet = CommonFunctions::getTheme();

if (isset($_GET['ACT']) && $_GET['ACT'] == 'logout') {
    session_destroy();
    setcookie('Loggedin', '', time() - 3600, '/');
    header("Location: ./login.php");
}

require_once ROOT_PATH . '/lib/common/authorize.php';

$authorizeObj = new authorize(@$_SESSION['empID'], $_SESSION['isAdmin']);

// Default leave home page
if ($authorizeObj->isSupervisor()) {
    if ($authorizeObj->isAdmin()) {
        $leaveHomePage = 'lib/controllers/CentralController.php?leavecode=Leave&action=Leave_HomeSupervisor';
    } else {
        $leaveHomePage = 'lib/controllers/CentralController.php?leavecode=Leave&action=Leave_FetchLeaveSupervisor';
    }
} else if ($authorizeObj->isAdmin()) {
    $leaveHomePage = 'lib/controllers/CentralController.php?leavecode=Leave&action=Leave_Type_Summary';
} else if ($authorizeObj->isESS()) {
    $leaveHomePage = 'lib/controllers/CentralController.php?leavecode=Leave&action=Leave_Summary&id=' . $_SESSION['empID'];
}

// Time module default pages
if (!$authorizeObj->isAdmin() && $authorizeObj->isESS()) {
    if ($_SESSION['timePeriodSet'] == 'Yes') {
        $timeHomePage = 'lib/controllers/CentralController.php?timecode=Time&action=View_Current_Timesheet';
    } else {
        $timeHomePage = 'lib/controllers/CentralController.php?timecode=Time&action=Work_Week_Edit_View';
    }

    $timesheetPage = 'javascript: location.href = \'' . $_SESSION['WPATH'] . '/lib/controllers/CentralController.php?timecode=Time&action=View_Current_Timesheet&clientTimezoneOffset=\' + escape((new Date()).getTimezoneOffset() * -1);';
} else {
    if ($_SESSION['timePeriodSet'] == 'Yes') {
        $timeHomePage = 'lib/controllers/CentralController.php?timecode=Time&action=View_Select_Employee';
    } else {
        $timeHomePage = 'lib/controllers/CentralController.php?timecode=Time&action=Work_Week_Edit_View';
    }

    $timesheetPage = 'lib/controllers/CentralController.php?timecode=Time&action=View_Select_Employee';
}

if (!$authorizeObj->isAdmin() && $authorizeObj->isESS()) {
    $beneftisHomePage = 'lib/controllers/CentralController.php?benefitcode=Benefits&action=Benefits_Schedule_Select_Year';
    $empId = $_SESSION['empID'];
    $year = date('Y');
    $personalHspSummary = "lib/controllers/CentralController.php?benefitcode=Benefits&action=Search_Hsp_Summary&empId=$empId&year=$year";
} else {
    $beneftisHomePage = 'lib/controllers/CentralController.php?benefitcode=Benefits&action=Benefits_Schedule_Select_Year';
    $personalHspSummary = 'lib/controllers/CentralController.php?benefitcode=Benefits&action=Hsp_Summary_Select_Year_Employee_Admin';
}

if ($authorizeObj->isESS()) {
    if ($_SESSION['timePeriodSet'] == 'Yes') {
        $timeHomePage = 'lib/controllers/CentralController.php?timecode=Time&action=Show_Punch_Time';
    } else {
        $timeHomePage = 'lib/controllers/CentralController.php?timecode=Time&action=Work_Week_Edit_View';
    }
}

$recruitHomePage = '';
if ($authorizeObj->isAdmin()) {
    $recruitHomePage = 'lib/controllers/CentralController.php?recruitcode=Vacancy&action=List';
} else if ($authorizeObj->isManager() || $authorizeObj->isDirector() || $authorizeObj->isAcceptor() || $authorizeObj->isOfferer()) {
    $recruitHomePage = 'lib/controllers/CentralController.php?recruitcode=Application&action=List';
}

// Default page in admin module is the Company general info page.
$defaultAdminView = "GEN";
$allowAdminView = false;

if ($_SESSION['isAdmin'] == 'No') {
    if (@$_SESSION['isProjectAdmin']) {

        // Default page for project admins is the Project Activity page
        $defaultAdminView = "PAC";

        // Allow project admins to view PAC (Project Activity) page only (in the admin module)
        // If uniqcode is not set, the default view is Project activity
        if ((!isset($_GET['uniqcode'])) || ($_GET['uniqcode'] == 'PAC')) {
            $allowAdminView = true;
        }
    }

    if (@$_SESSION['isSupervisor']) {

        // Default page for supervisors is the Company property page
        $defaultAdminView = "TCP";

        // Allow supervisors to view TCP (Company property) page only (in the admin module)
        // If uniqcode is not set, the default view is Company Property
        if ((!isset($_GET['uniqcode'])) || ($_GET['uniqcode'] == 'TCP')) {
            $allowAdminView = true;
        }
    }
}

require_once ROOT_PATH . '/lib/common/Language.php';

$lan = new Language();

require_once ROOT_PATH . '/language/default/lang_default_full.php';
require_once ROOT_PATH . '/language/fr/lang_fr_full.php';
//require_once($lan->getLangPath("full.php"));
?>
<html>
    <head>
        <title>OrangeHRM</title>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
        <link href="themes/<?php echo $styleSheet; ?>/css/style.css" rel="stylesheet" type="text/css">
        <link href="favicon.ico" rel="icon" type="image/gif"/>
        <style type="text/css">@import url("themes/<?php echo $styleSheet; ?>/css/menu.css"); </style>
        <script language=javascript src="scripts/ypSlideOutMenus.js"></script>
        <!DOCTYPE html PUBLIC "-//W3C//DTD html 4.01 Transitional//EN">
    <script language="JavaScript">
        //window.onresize = setSize();

        var yPosition = 40;

        var agt=navigator.userAgent.toLowerCase();

        var xPosition = 150;

        if (agt.indexOf("konqueror") != -1) var xPosition = 144;

        if (agt.indexOf("windows") != -1) var xPosition = 144;

        if (agt.indexOf("msie") != -1) var xPosition = 150;
        xPosition += 4;

        new ypSlideOutMenu("menu1", "right", xPosition, yPosition, 150, 230)
        new ypSlideOutMenu("menu2", "right", xPosition, yPosition + 22, 146, 360)
        new ypSlideOutMenu("menu3", "right", xPosition, yPosition + 44, 146, 220)
        new ypSlideOutMenu("menu4", "right", xPosition, yPosition + 66, 146, 80)
        new ypSlideOutMenu("menu5", "right", xPosition, yPosition + 88, 146, 130)
        new ypSlideOutMenu("menu9", "right", xPosition, yPosition + 110, 146, 80)
        new ypSlideOutMenu("menu12", "right", xPosition, yPosition + 132, 146, 120)
        new ypSlideOutMenu("menu15", "right", xPosition, yPosition + 154, 146, 120)
        new ypSlideOutMenu("menu17", "right", xPosition, yPosition + 176, 146, 120)
        new ypSlideOutMenu("menu18", "right", xPosition, yPosition + 198, 146, 120)//CVS
        new ypSlideOutMenu("menu13", "right", xPosition, yPosition, 146, 120)
        new ypSlideOutMenu("menu14", "right", xPosition, yPosition + 22, 146, 120)
        new ypSlideOutMenu("menu16", "right", xPosition, yPosition, 146, 120)
        new ypSlideOutMenu("menu19", "right", xPosition, yPosition, 146, 140)//HSP
        new ypSlideOutMenu("menu20", "right", xPosition, yPosition + 16, 146, 120)

        function swapImgRestore() {
            var i,x,a=document.sr; for(i=0;a&&i<a.length&&(x=a[i])&&x.oSrc;i++) x.src=x.oSrc;
        }
        function preloadImages() {
            var d=document; if(d.images){ if(!d.p) d.p=new Array();
                var i,j=d.p.length,a=preloadImages.arguments; for(i=0; i<a.length; i++)
                    if (a[i].indexOf("#")!=0){ d.p[j]=new Image; d.p[j++].src=a[i];}}
            }
            function findObj(n, d) {
                var p,i,x;  if(!d) d=document; if((p=n.indexOf("?"))>0&&parent.frames.length) {
                    d=parent.frames[n.substring(p+1)].document; n=n.substring(0,p);}
                if(!(x=d[n])&&d.all) x=d.all[n]; for (i=0;!x&&i<d.forms.length;i++) x=d.forms[i][n];
                for(i=0;!x&&d.layers&&i<d.layers.length;i++) x=findObj(n,d.layers[i].document);
                if(!x && document.getElementById) x=document.getElementById(n); return x;
            }
            function swapImage() {
                var i,j=0,x,a=swapImage.arguments; document.sr=new Array; for(i=0;i<(a.length-2);i+=3)
                if ((x=findObj(a[i]))!=null){document.sr[j++]=x; if(!x.oSrc) x.oSrc=x.src; x.src=a[i+2];}
            }
            function showHideLayers() {
                var i,p,v,obj,args=showHideLayers.arguments;
                for (i=0; i<(args.length-2); i+=3) if ((obj=findObj(args[i]))!=null) { v=args[i+2];
                    if (obj.style) { obj=obj.style    ; v=(v=='show')?'visible':(v='hide')?'hidden':v; }
                    obj.visibility=v; }
            }

            function setSize() {
                var iframeElement = document.getElementById('rightMenu');
                iframeElement.style.height = (window.innerHeight - 20) + 'px'; //100px or 100%
                iframeElement.style.width = '100%'; //100px or 100%
            }
            function preloadAllImages() {
                var base = 'themes/<?php echo $styleSheet; ?>/pictures';
                preloadImages(base + '/buttonplain.gif', base + '/buttonplain_o.gif');
            }
    </SCRIPT>
    <style type="text/css">
        #rightMenu {
            z-index: 0;
        }
    </style>

</head>
<body onLoad="preloadAllImages()">
    <table width="100%" cellspacing="0" cellpadding="0" border="0">
        <form name="indexForm" action="./menu.php?TEST=1111" method="post">
            <input type="hidden" name="tabnumber" value="1">
            <a name="a"></a>
            <tr>
                <td colspan="2"><table cellspacing="0" cellpadding="0" border="0" width="100%">
              <!--      <tr height="50">
                      <td title="Company Logo" class="companyLogoHeader" />
                      <td title="Header Image" class="headerRight"/>
                    </tr>-->
                        <tr>
<?php
if (!isset($_GET['menu_no_top'])) {
    $_GET['menu_no_top'] = "home";
}

if ((isset($_GET['menu_no_top'])) && ($_GET['menu_no_top'] == "home")) {
    ?>
                                <td colspan="2"></td>
<?php } else { ?>
                                <td colspan="2"><table cellspacing="0" cellpadding="0" border="0" width="100%">
                                        <tr height="20">
                                            <td class="tabLeftSpace"><img src="" width="8" height="1" border="0" alt="Home"></td>
                                            <td></td>
                            <?php } ?>
                            <?php
                            if ((@$_SESSION['isAdmin'] == 'Yes') || @$_SESSION['isProjectAdmin'] || @$_SESSION['isSupervisor']) {
                                if (isset($_GET['menu_no_top']) && ($_GET['menu_no_top'] == "eim") && ($arrAllRights[Admin]['view'] || $_SESSION['isProjectAdmin'] || $_SESSION['isSupervisor'])) {
                                    ?>
                                                <td />
                                                <td class="tabSeparator"></td>
                                                <td><table cellspacing="0" cellpadding="0" border="0" class="tabContainer">
                                                        <tr height="20">
                                                            <td class="currentTabLeft" ></td>
                                                            <td  class="currentTab" nowrap><a   class="currentTab"  href="./index.php?module=Home&menu_no=1&submenutop=EIMModule&menu_no_top=eim" ><?php echo $lang_Menu_Admin; ?></a></td>
                                                            <td class="currentTabRight"><img src="" width="8" height="1" border="0" alt="Home"></td>
                                                            <td class="tabSpace"><img src="" width="1" height="1" border="0" alt=""></td>
                                                        </tr>
                                                    </table></td>
                                            <?php } else if ($arrAllRights[Admin]['view'] || $_SESSION['isProjectAdmin'] || $_SESSION['isSupervisor']) { ?>
                                                <td><table cellspacing="0" cellpadding="0" border="0" class="tabContainer">
                                                        <tr height="20">
                                                            <td class="otherTabLeft" ><img src="" width="8" height="1" border="0" alt="My Portal"></td>
                                                            <td  class="otherTab" nowrap><a class="otherTab" href="index.php?module=Home&menu_no=1&submenutop=EIMModule&menu_no_top=eim&pageNo=1"><?php echo $lang_Menu_Admin; ?></a></td>
                                                            <td class="otherTabRight"><img src="" width="8" height="1" border="0" alt="My Portal"></td>
                                                            <td class="tabSpace"><img src="" width="1" height="1" border="0" alt=""></td>
                                                        </tr>
                                                    </table></td>
    <?php
    }
}
?>
                                        <?php
                                        if ($_SESSION['isAdmin'] == 'Yes' || $_SESSION['isSupervisor']) {
                                            ?>
    <?php
    if ((isset($_GET['menu_no_top'])) && ($_GET['menu_no_top'] == "hr") && $arrAllRights[PIM]['view']) {
        ?>
                                                <td />
                                                <td class="tabSeparator"></td>
                                                <td><table cellspacing="0" cellpadding="0" border="0" class="tabContainer"">
                                                           <tr height="20">
                                                            <td class="currentTabLeft" ></td>
                                                            <td  class="currentTab" nowrap><a   class="currentTab"  href="./index.php?module=Home&menu_no=12&submenutop=home1&menu_no_top=hr" ><?php echo $lang_Menu_Pim; ?></a></td>
                                                            <td class="currentTabRight"><img src="" width="8" height="1" border="0" alt="Home"></td>
                                                            <td class="tabSpace"><img src="" width="1" height="1" border="0" alt=""></td>
                                                        </tr>
                                                    </table></td>
                                            <?php } else if ($arrAllRights[PIM]['view']) { ?>
                                                <td><table cellspacing="0" cellpadding="0" border="0" class="tabContainer"">
                                                           <tr height="20">
                                                            <td class="otherTabLeft" ><img src="" width="8" height="1" border="0" alt="My Portal"></td>
                                                            <td class="otherTab" nowrap><a   class="otherTab"  href="./index.php?module=Home&menu_no=12&submenutop=home1&menu_no_top=hr"><?php echo $lang_Menu_Pim; ?></a></td>
                                                            <td class="otherTabRight"><img src="" width="8" height="1" border="0" alt="My Portal"></td>
                                                            <td class="tabSpace"><img src="" width="1" height="1" border="0" alt=""></td>
                                                        </tr>
                                                    </table></td>
    <?php
    }
}
if ((isset($_GET['menu_no_top'])) && ($_GET['menu_no_top'] == "leave") && (($_SESSION['empID'] != null) || $arrAllRights[Leave]['view'])) {
    ?>
                                            <td />
                                            <td class="tabSeparator"></td>
                                            <td><table cellspacing="0" cellpadding="0" border="0" class="tabContainer"">
                                                       <tr height="20">
                                                        <td class="currentTabLeft" ></td>
                                                        <td  class="currentTab" nowrap><a class="currentTab"  href="./index.php?module=Home&menu_no=1&submenutop=LeaveModule&menu_no_top=leave" ><?php echo $lang_Menu_Leave; ?></a></td>
                                                        <td class="currentTabRight"><img src="" width="8" height="1" border="0" alt="Home"></td>
                                                        <td class="tabSpace"><img src="" width="1" height="1" border="0" alt=""></td>
                                                    </tr>
                                                </table></td>
                                        <?php } else if (($_SESSION['empID'] != null) || $arrAllRights[Leave]['view']) { ?>
                                            <td><table cellspacing="0" cellpadding="0" border="0" class="tabContainer"">
                                                       <tr height="20">
                                                        <td class="otherTabLeft" ><img src="" width="8" height="1" border="0" alt="My Portal"></td>
                                                        <td class="otherTab" nowrap><a   class="otherTab"  href="index.php?module=Home&menu_no=3&menu_no_top=leave"><?php echo $lang_Menu_Leave; ?></a></td>
                                                        <td class="otherTabRight"><img src="" width="8" height="1" border="0" alt="My Portal"></td>
                                                        <td class="tabSpace"><img src="" width="1" height="1" border="0" alt=""></td>
                                                    </tr>
                                                </table></td>
<?php
}
if ((isset($_GET['menu_no_top'])) && ($_GET['menu_no_top'] == "time") && (($_SESSION['empID'] != null) || $arrAllRights[TimeM]['view'])) {
    ?>
                                            <td />
                                            <td class="tabSeparator"></td>
                                            <td><table cellspacing="0" cellpadding="0" border="0" class="tabContainer"">
                                                       <tr height="20">
                                                        <td class="currentTabLeft" ></td>
                                                        <td  class="currentTab" nowrap><a class="currentTab"  href="./index.php?module=Home&menu_no=1&submenutop=LeaveModule&menu_no_top=time" ><?php echo $lang_Menu_Time; ?></a></td>
                                                        <td class="currentTabRight"><img src="" width="8" height="1" border="0" alt="Home"></td>
                                                        <td class="tabSpace"><img src="" width="1" height="1" border="0" alt=""></td>
                                                    </tr>
                                                </table></td>
<?php } else if (($_SESSION['empID'] != null) || $arrAllRights[TimeM]['view']) { ?>
                                            <td><table cellspacing="0" cellpadding="0" border="0" class="tabContainer"">
                                                       <tr height="20">
                                                        <td class="otherTabLeft" ><img src="" width="8" height="1" border="0" alt="My Portal"></td>
                                                        <td class="otherTab" nowrap><a   class="otherTab"  href="index.php?module=Home&menu_no=3&menu_no_top=time"><?php echo $lang_Menu_Time; ?></a></td>
                                                        <td class="otherTabRight"><img src="" width="8" height="1" border="0" alt="My Portal"></td>
                                                        <td class="tabSpace"><img src="" width="1" height="1" border="0" alt=""></td>
                                                    </tr>
                                                </table></td>
                                        <?php
                                        }

                                        if (isset($_GET['menu_no_top']) && ($_GET['menu_no_top'] == "recruit") && $arrAllRights[Recruit]['view']) {
                                            ?>
                                            <td />
                                            <td class="tabSeparator"></td>
                                            <td><table cellspacing="0" cellpadding="0" border="0" class="tabContainer"">
                                                       <tr height="20">
                                                        <td class="currentTabLeft" ></td>
                                                        <td  class="currentTab" nowrap><a class="currentTab"  href="./index.php?module=Home&menu_no=1&submenutop=RecruitModule&menu_no_top=recruit" ><?php echo $lang_Menu_Recruit; ?></a></td>
                                                        <td class="currentTabRight"><img src="" width="8" height="1" border="0" alt=""></td>
                                                        <td class="tabSpace"><img src="" width="1" height="1" border="0" alt=""></td>
                                                    </tr>
                                                </table></td>
<?php } else if ($arrAllRights[Recruit]['view']) { ?>
                                            <td><table cellspacing="0" cellpadding="0" border="0" class="tabContainer"">
                                                       <tr height="20">
                                                        <td class="otherTabLeft" ><img src="" width="8" height="1" border="0" alt=""></td>
                                                        <td class="otherTab" nowrap><a   class="otherTab"  href="index.php?module=Home&menu_no=3&menu_no_top=recruit"><?php echo $lang_Menu_Recruit; ?></a></td>
                                                        <td class="otherTabRight"><img src="" width="8" height="1" border="0" alt=""></td>
                                                        <td class="tabSpace"><img src="" width="1" height="1" border="0" alt=""></td>
                                                    </tr>
                                                </table></td>
                                        <?php
                                        }

                                        if ($_SESSION['isAdmin'] == 'Yes') {
                                            if ((isset($_GET['menu_no_top'])) && ($_GET['menu_no_top'] == "rep") && $arrAllRights[Report]['view']) {
                                                ?>
                                                <td />
                                                <td class="tabSeparator"></td>
                                                <td><table cellspacing="0" cellpadding="0" border="0" class="tabContainer"">
                                                           <tr height="20">
                                                            <td class="currentTabLeft" ></td>
                                                            <td  class="currentTab" nowrap><a   class="currentTab"  href="./index.php?module=Home&menu_no=12&submenutop=home1&menu_no_top=rep"><?php echo $lang_Menu_Reports; ?></a></td>
                                                            <td class="currentTabRight"><img src="" width="8" height="1" border="0" alt="Home"></td>
                                                            <td class="tabSpace"><img src="" width="1" height="1" border="0" alt=""></td>
                                                        </tr>
                                                    </table></td>
    <?php } else if ($arrAllRights[Report]['view']) { ?>
                                                <td><table cellspacing="0" cellpadding="0" border="0" class="tabContainer"">
                                                           <tr height="20">
                                                            <td class="otherTabLeft" ><img src="" width="8" height="1" border="0" alt="My Portal"></td>
                                                            <td class="otherTab" nowrap><a   class="otherTab"  href="./index.php?module=Home&menu_no=12&submenutop=home1&menu_no_top=rep"><?php echo $lang_Menu_Reports; ?></a></td>
                                                            <td class="otherTabRight"><img src="" width="8" height="1" border="0" alt="My Portal"></td>
                                                            <td class="tabSpace"><img src="" width="1" height="1" border="0" alt=""></td>
                                                        </tr>
                                                    </table></td>
    <?php } ?>
    <?php
} else {
    if ((isset($_GET['menu_no_top'])) && ($_GET['menu_no_top'] == "ess")) {
        ?>
                                                <td />
                                                <td class="tabSeparator"></td>
                                                <td><table cellspacing="0" cellpadding="0" border="0" class="tabContainer"">
                                                           <tr height="20">
                                                            <td class="currentTabLeft" ></td>
                                                            <td  class="currentTab" nowrap><a class="currentTab"  href="./index.php?module=Home&menu_no=1&submenutop=EIMModule&menu_no_top=ess" ><?php echo $lang_Menu_Ess; ?></a></td>
                                                            <td class="currentTabRight"><img src="" width="8" height="1" border="0" alt="Home"></td>
                                                            <td class="tabSpace"><img src="" width="1" height="1" border="0" alt=""></td>
                                                        </tr>
                                                    </table></td>
    <?php } else { ?>
                                                <td><table cellspacing="0" cellpadding="0" border="0" class="tabContainer"">
                                                           <tr height="20">
                                                            <td class="otherTabLeft" ><img src="" width="8" height="1" border="0" alt="My Portal"></td>
                                                            <td class="otherTab" nowrap><a   class="otherTab"  href="index.php?module=Home&menu_no=3&menu_no_top=ess"><?php echo $lang_Menu_Ess; ?></a></td>
                                                            <td class="otherTabRight"><img src="" width="8" height="1" border="0" alt="My Portal"></td>
                                                            <td class="tabSpace"><img src="" width="1" height="1" border="0" alt=""></td>
                                                        </tr>
                                                    </table></td>
    <?php
    }
}
?>                  <td width="100%" class="tabSpace"><img src="" width="1" height="1" border="0" alt=""></td>
                                    </tr>
                                </table></td>
                        </tr>
                        <tr height="20">
                        <input type="hidden" name="action" value="UnifiedSearch">
                        <input type="hidden" name="module" value="Home">
                        <input type="hidden" name="search_form" value="false">
                        <td class="subTabBar" colspan="2"><table width="100%" cellspacing="0" cellpadding="0" border="0" height="20">
                                <tr>
<?php $lang_index_WelcomeMes = "Bienvenue #username" ?>
                                    <td class="welcome" width="100%"><?php echo preg_replace('/#username/', ((isset($user)) ? $fullname : ''), $lang_index_WelcomeMes); ?></td>
                                    <td class="search" align="right" nowrap="nowrap">
<?php
if (isset($_SESSION['ladpUser']) && $_SESSION['ladpUser'] && $_SESSION['isAdmin'] != "Yes") {
    echo "&nbsp;";
} else {
    ?>
                      <!--                  <a href="./lib/controllers/CentralController.php?mtcode=CPW&capturemode=updatemode&id=<?php echo $_SESSION['user'] ?>" target="rightMenu"><strong><?php echo $lang_index_ChangePassword; ?></strong></a>-->
                                        <?php } ?>
                                    </td>
                                    <td class="searchSeparator">&nbsp;</td>
                                    <td class="search" nowrap>&nbsp;&nbsp; </td>
                                </tr>
                            </table></td>
            </tr>
    </table>
</table>

<?php if ((isset($_GET['menu_no_top'])) && ($_GET['menu_no_top'] != "hr" && $_GET['menu_no_top'] != "ess" )) { ?>

    <table border="0" align="top" cellpadding="0" cellspacing="0">
        <tr>
            <td width="200" valign="top"><!-- Rollover buttons -->
                <TABLE cellSpacing=0 cellPadding=0 border=0>
                    <TBODY>
                        <TR vAlign=top>
    <?php
    if ((isset($_GET['menu_no_top'])) && ($_GET['menu_no_top'] == "eim")) {
        if ($arrRights['view']) {
            ?>
                                    <TD width=158>
                                        <ul id="menu">
                                            <li id="compinfo"><a href="#" onMouseOver="ypSlideOutMenu.showMenu('menu1');" onMouseOut="ypSlideOutMenu.hideMenu('menu1');"><?php echo $lang_Menu_Admin_CompanyInfo; ?></a></li>
                                            <li id="job"><a href="#" onMouseOver="ypSlideOutMenu.showMenu('menu2');" onMouseOut="ypSlideOutMenu.hideMenu('menu2');"><?php echo $lang_Menu_Admin_Job; ?></a></li>
                                            <li id="qualification"><a href="#" onMouseOver="ypSlideOutMenu.showMenu('menu3');" onMouseOut="ypSlideOutMenu.hideMenu('menu3');"><?php echo $lang_Menu_Admin_Quali; ?></a></li>
                                            <li id="skills"><a href="#" onMouseOver="ypSlideOutMenu.showMenu('menu4');" onMouseOut="ypSlideOutMenu.hideMenu('menu4');"><?php echo $lang_Menu_Admin_Skills; ?></a></li>
                                            <li id="memberships"><a href="#" onMouseOver="ypSlideOutMenu.showMenu('menu5');" onMouseOut="ypSlideOutMenu.hideMenu('menu5');"><?php echo $lang_Menu_Admin_Memberships; ?></a></li>
                                            <li id="natandrace"><a href="#" onMouseOver="ypSlideOutMenu.showMenu('menu9');" onMouseOut="ypSlideOutMenu.hideMenu('menu9');"><?php echo $lang_Menu_Admin_NationalityNRace; ?></a></li>
                                            <li id="users"><a href="#" onMouseOver="ypSlideOutMenu.showMenu('menu12');" onMouseOut="ypSlideOutMenu.hideMenu('menu12');"><?php echo $lang_Menu_Admin_Users; ?></a></li>
                                            <li id="notifications"><a href="#" onMouseOver="ypSlideOutMenu.showMenu('menu15');" onMouseOut="ypSlideOutMenu.hideMenu('menu15');"><?php echo $lang_Menu_Admin_EmailNotifications; ?></a></li>
                                            <li id="projectInfo"><a href="#"  onMouseOver="ypSlideOutMenu.showMenu('menu17');" onMouseOut="ypSlideOutMenu.hideMenu('menu17');"><?php echo $lang_Menu_Admin_ProjectInfo; ?></a></li>
                                            <li id="dataexport"><a href="#"  onMouseOver="ypSlideOutMenu.showMenu('menu18');" onMouseOut="ypSlideOutMenu.hideMenu('menu18');"><?php echo $lang_Menu_Admin_DataImportExport; ?></a></li>
                                            <li id="dataexport"><a href="index.php?uniqcode=CTM&submenutop=EIMModule&menu_no_top=eim"><?php echo $lang_Menu_Admin_CustomFields; ?> </a></li>
            <?php
            if ($_SESSION['ldap'] == "enabled") {
                ?>
                                                <li id="projectInfo"><a href="index.php?uniqcode=LDAP&submenutop=EIMModule&menu_no_top=eim"><?php echo $lang_Menu_LDAP_Configuration; ?></a></li>
                                                <?php
                                            }
                                            ?>
                                        </ul></TD>
                                        <?php } else if (($_SESSION['isProjectAdmin']) || ($_SESSION['isSupervisor'])) { ?>
                                    <TD width=158>
                                        <ul id="menu">

                                    <?php if ($_SESSION['isProjectAdmin']) { ?>
                                                <li id="projectInfo">
                                                    <a href="index.php?uniqcode=PAC&menu_no=2&submenutop=EIMModule&menu_no_top=eim">
                <?php echo $lang_Admin_ProjectActivities; ?></a></li>
                                            <?php }
                                            if ($_SESSION['isSupervisor']) {
                                                ?>
                                                <li id="compinfo">
                                                    <a href="index.php?uniqcode=TCP&menu_no=1&submenutop=EIMModule&menu_no_top=eim&pageNo=1">
                                                <?php echo $lang_Menu_Admin_Company_Property; ?></a></li>
                                            <?php } ?>
                                        </ul></TD>
                                                <?php }
                                            } else if ((isset($_GET['menu_no_top'])) && ($_GET['menu_no_top'] == "rep")) {
                                                ?>
                                <TD width=158>
                                    <ul id="menu">

        <?php if ($arrAllRights[Report]['add'] || $arrAllRights[Report]['edit'] || $arrAllRights[Report]['delete']) { ?>
                                            <li id="defemprep"><A href="index.php?repcode=EMPDEF&menu_no=1&submenutop=HR&menu_no_top=rep"><?php echo $lang_Menu_Reports_DefineReports; ?></A></li>
                                        <?php } ?>
                                        <li id="viewemprep"><A href="index.php?repcode=EMPVIEW&menu_no=1&submenutop=HR&menu_no_top=rep"><?php echo $lang_Menu_Reports_ViewReports; ?></A></li>
                                        <?php } else
                                        
                                        ?>
                                </ul>
                            </TD>
    <?php if ((isset($_GET['menu_no_top'])) && ($_GET['menu_no_top'] == "leave" )) { ?>
                                <TD width=158>
                                    <ul id="menu">
        <?php
        $allowedRoles = array($authorizeObj->roleAdmin, $authorizeObj->roleSupervisor);

        //if ($authorizeObj->firstRole($allowedRoles)) {

        if ($authorizeObj->isESS()) {
            $linkSummary = 'href="lib/controllers/CentralController.php?leavecode=Leave&action=Leave_Summary&id=' . $_SESSION['empID'] . '"';
        } else {
            $linkSummary = "";
        }
        ?>
                                        <?php //var_dump($authorizeObj); ?>
                                        <li id="leaveSummary"><a <?php echo $linkSummary; ?> target="rightMenu" onMouseOver="ypSlideOutMenu.showMenu('menu13');" onMouseOut="ypSlideOutMenu.hideMenu('menu13');"><?php echo $lang_Menu_Leave_LeaveSummary; ?></a></li>
                                        <?php
                                        if ($authorizeObj->isAdmin()) {
                                            ?>
                                            <li id="defineLeaveType"><a target="rightMenu" onMouseOver="ypSlideOutMenu.showMenu('menu14');" onMouseOut="ypSlideOutMenu.hideMenu('menu14');"><?php echo $lang_Menu_Leave_DefineDaysOff; ?></a></li>
                                            <li id="defineLeaveType"><a href="lib/controllers/CentralController.php?leavecode=Leave&action=Leave_Type_Summary" target="rightMenu"><?php echo $lang_Menu_Leave_LeaveTypes; ?></a></li>
            <?php
        }
        if ($authorizeObj->isESS()) {
            ?>
                                            <li id="leaveList"><a href="lib/controllers/CentralController.php?leavecode=Leave&action=Leave_FetchLeaveEmployee" target="rightMenu"><?php echo $lang_Menu_Leave_MyLeave; ?></a></li>
                                            <li id="applyLeave"><a href="lib/controllers/CentralController.php?leavecode=Leave&action=Leave_Apply_view" target="rightMenu"><?php echo $lang_Menu_Leave_Apply; ?></a></li>
            <?php
        }
        if ($authorizeObj->isAdmin() || $authorizeObj->isSupervisor()) {
            ?>
                                            <li id="applyLeave"><a href="lib/controllers/CentralController.php?leavecode=Leave&action=Leave_Apply_Admin_view" target="rightMenu"><?php echo $lang_Menu_Leave_Assign; ?></a></li>
                                            <?php
                                        }
                                        if ($authorizeObj->isSupervisor()) {
                                            ?>
                                            <li id="approveLeave"><a href="lib/controllers/CentralController.php?leavecode=Leave&action=Leave_FetchLeaveSupervisor" target="rightMenu"><?php echo $lang_Menu_Leave_ApproveLeave; ?></a></li>
                                        <?php
                                        }
                                        if ($authorizeObj->isAdmin()) {
                                            ?>
                                            <li id="approveLeave"><a href="lib/controllers/CentralController.php?leavecode=Leave&action=Leave_FetchLeaveAdmin&NewQuery=1" target="rightMenu"><?php echo $lang_Leave_all_emplyee_leaves; ?> </a></li>
                                            <!--<li id="approveLeave"><a href="lib/controllers/CentralController.php?leavecode=Leave&action=Leave_FetchLeaveTaken" target="rightMenu"><?php echo $lang_Leave_all_emplyee_taken_leaves; ?> </a></li>-->
                                        <?php } ?>
                                    </ul>
                                </TD>
                            <?php }

                            if ((isset($_GET['menu_no_top'])) && ($_GET['menu_no_top'] == "time" )) {
                                ?>
                                <?php if ($_SESSION['timePeriodSet'] == "Yes") { // For checking Time period setting: Begins ?>
                                    <TD width=158>
                                        <ul id="menu">
                                            <li id="timesheets"><a href="<?php echo $timesheetPage; ?>" target="rightMenu" onMouseOver="ypSlideOutMenu.showMenu('menu16');" onMouseOut="ypSlideOutMenu.hideMenu('menu16');"><?php echo $lang_Menu_Time_Timesheets; ?></a></li>
                                            <?php if ($authorizeObj->isESS()) { ?>
                                                <li id="punchTime"><a href="lib/controllers/CentralController.php?timecode=Time&action=Show_Punch_Time" target="rightMenu"><?php echo $lang_Menu_Time_PunchInOut; ?></a></li>
                                                <li id="punchTime"><a href="lib/controllers/CentralController.php?timecode=Time&action=Time_Event_Home" target="rightMenu"><?php echo $lang_Menu_Time_ProjectTime; ?></a></li>
                                                <?php
                                            }
                                            $allowedRoles = array($authorizeObj->roleAdmin, $authorizeObj->roleSupervisor);
                                            if ($authorizeObj->firstRole($allowedRoles)) {
                                                ?>
                                                <li id="timesheets"><a href="lib/controllers/CentralController.php?timecode=Time&action=Employee_Report_Define" target="rightMenu"><?php echo $lang_Menu_Time_EmployeeReports; ?></a></li>
                                            <?php } ?>
                                            <?php if (($_SESSION['isAdmin'] == 'Yes') || $_SESSION['isProjectAdmin']) { ?>
                                                <li id="projectInfo">
                                                    <a href="lib/controllers/CentralController.php?timecode=Time&action=Project_Report_Define" target="rightMenu">
                                                        <?php echo $lang_Menu_Time_ProjectReports; ?>
                                                    </a>
                                                </li>
                                            <?php } ?>
                                            <?php if ($_SESSION['isAdmin'] == 'Yes') { ?>
                                                <li id="projectInfo">
                                                    <a href="lib/controllers/CentralController.php?timecode=Time&action=View_Work_Shifts" target="rightMenu">
                                                        <?php echo $lang_Menu_Time_WorkShifts; ?>
                                                    </a>
                                                </li>
                                            <?php } ?>
                                        </ul>
                                    </TD>
                                <?php } // For checking Time period setting: Ends ?>
                            <?php }

                            if ((isset($_GET['menu_no_top'])) && ($_GET['menu_no_top'] == "recruit" )) { // For checnig the recruitment settings : Begins  
                                ?>
                                <TD width=158>
                                    <ul id="menu">
        <?php if ($_SESSION['isAdmin'] == 'Yes') { ?>
                                            <li id="jobVacancies">
                                                <a href="lib/controllers/CentralController.php?recruitcode=Vacancy&action=List" target="rightMenu">
            <?php echo $lang_Menu_Recruit_JobVacancies; ?>
                                                </a>
                                            </li>
                                        <?php
                                        }
                                        if ($_SESSION['isAdmin'] == 'Yes' || $_SESSION['isManager'] || $_SESSION['isDirector'] || $_SESSION['isAcceptor'] || $_SESSION['isOfferer']) {
                                            ?>
                                            <li id="jobApplicants">
                                                <a href="lib/controllers/CentralController.php?recruitcode=Application&action=List" target="rightMenu">
                                            <?php echo $lang_Menu_Recruit_JobApplicants; ?>
                                                </a>
                                            </li>
                                <?php } ?>
                                    </ul>
                                </TD>
                            <?php } // For checnig the recruitment settings : Ends  ?>

    <?php if ((isset($_GET['menu_no_top'])) && ($_GET['menu_no_top'] == "benefits" )) { ?>
                                <TD width=158>
                                    <ul id="menu">
                                        <li id="projectInfo">
                                            <?php if ($_SESSION['isAdmin'] == "Yes") { ?>
                                                <a href="lib/controllers/CentralController.php?benefitcode=Benefits&action=Hsp_Summary&year=<?php echo date('Y'); ?>" onMouseOver="ypSlideOutMenu.showMenu('menu19');" onMouseOut="ypSlideOutMenu.hideMenu('menu19');" target="rightMenu"><?php echo $lang_Menu_Benefits_HealthSavingsPlan; ?></a>
                                            <?php
                                            } else {
                                                if (Config::getHspCurrentPlan() > 0) {
                                                    ?>
                                                    <a href="<?php echo $personalHspSummary; ?>" onMouseOver="ypSlideOutMenu.showMenu('menu19');" onMouseOut="ypSlideOutMenu.hideMenu('menu19');" target="rightMenu"><?php echo $lang_Menu_Benefits_HealthSavingsPlan; ?></a>
            <?php } else { ?>
                                                    <a href="lib/controllers/CentralController.php?benefitcode=Benefits&action=Hsp_Not_Defined" onMouseOver="ypSlideOutMenu.showMenu('menu19');" onMouseOut="ypSlideOutMenu.hideMenu('menu19');" target="rightMenu"><?php echo $lang_Menu_Benefits_HealthSavingsPlan; ?></a>
                                                <?php }
                                            } ?>
                                        </li>
                                        <li id="projectInfo">
                                            <?php if ($_SESSION['isAdmin'] == "Yes") { ?>
                                                <a href="lib/controllers/CentralController.php?benefitcode=Benefits&action=Benefits_Schedule_Select_Year" target="rightMenu" onMouseOver="ypSlideOutMenu.showMenu('menu20');" onMouseOut="ypSlideOutMenu.hideMenu('menu20');">
                                                    <?php echo $lang_Menu_Benefits_PayrollSchedule; ?>
                                                </a>
                                            <?php } else { ?>
                                                <a href="lib/controllers/CentralController.php?benefitcode=Benefits&action=Benefits_Schedule_Select_Year" target="rightMenu">
            <?php echo $lang_Menu_Benefits_PayrollSchedule; ?>
                                                </a>
                                <?php } ?>
                                        </li>
                                    </ul>
                                </TD>

    <?php }

    if ((isset($_GET['menu_no_top'])) && ($_GET['menu_no_top'] == "home")) {
        ?>
                                <TD valign="top" width=158>
                                    <ul id="menu">
                                        <li id="viewemprep"><a href="http://www.orangehrm.com/subscribe-support.shtml" target="_blank"><?php echo $lang_Menu_Home_Support; ?></a></li>
                                        <li id="viewemprep"><a href="http://www.orangehrm.com/forum/" target="_blank"><?php echo $lang_Menu_Home_Forum; ?></a></li>
                                        <li id="viewemprep"><a href="http://www.orangehrm.com/blog/" target="_blank"><?php echo $lang_Menu_Home_Blog; ?></a></li>
                                    </ul>
                                </td>

    <?php }
    if ((isset($_GET['menu_no_top'])) && ($_GET['menu_no_top'] == "bug" )) {
        ?>
                                <TD class="bugtrackerLeftCol"><p><br>
                                    </p></TD>
    <?php } ?>
                        </TR>
                    </TBODY>
                </TABLE>
                <!-- End Rollover buttons -->
                <!--------------------- Menu start --------------------->
                <!-- Begin SubMenu1 -->
                <DIV id=menu1Container>
                    <DIV id=menu1Content>
                        <TABLE cellSpacing=0 cellPadding=0 width=142 border=0>
                            <TBODY>
                                <TR>
                                    <TD onMouseOver="ypSlideOutMenu.showMenu('menu1')" onMouseOut="ypSlideOutMenu.hideMenu('menu1')" vAlign=center align=left width=142 height=17><A class="rollmenu" href="index.php?uniqcode=GEN&menu_no=1&submenutop=EIMModule&menu_no_top=eim"><?php echo $lang_Menu_Admin_CompanyInfo_Gen; ?></A></TD>
                                </TR>
                                <TR>
                                    <TD onMouseOver="ypSlideOutMenu.showMenu('menu1')" onMouseOut="ypSlideOutMenu.hideMenu('menu1')" vAlign=center align=left width=142 height=17><A class="rollmenu" href="index.php?uniqcode=CST&menu_no=1&submenutop=EIMModule&menu_no_top=eim"><?php echo $lang_Menu_Admin_CompanyInfo_CompStruct; ?></A></TD>
                                </TR>
                                <TR>
                                    <TD onMouseOver="ypSlideOutMenu.showMenu('menu1')" onMouseOut="ypSlideOutMenu.hideMenu('menu1')" vAlign=center align=left width=142 height=17><A class="rollmenu" href="index.php?uniqcode=LOC&menu_no=1&submenutop=EIMModule&menu_no_top=eim"><?php echo $lang_Menu_Admin_CompanyInfo_Locations; ?></A></TD>
                                </TR>
                                <TR>
                                    <TD onMouseOver="ypSlideOutMenu.showMenu('menu1')" onMouseOut="ypSlideOutMenu.hideMenu('menu1')" vAlign=center align=left width=142 height=17><A class="rollmenu" href="index.php?uniqcode=TCP&menu_no=1&submenutop=EIMModule&menu_no_top=eim&pageNo=1"><?php echo $lang_Menu_Admin_Company_Property; ?></A></TD>
                                </TR>
                            </TBODY>
                        </TABLE>
                    </DIV>
                </DIV>
                <!-- End SubMenu1 -->
                <!-- Begin SubMenu2 -->
                <DIV id=menu2Container>
                    <DIV id=menu2Content>
                        <TABLE cellSpacing=0 cellPadding=0 width=142 border=0>
                            <TBODY>
                                <TR>
                                    <TD onMouseOver="ypSlideOutMenu.showMenu('menu2')" onMouseOut="ypSlideOutMenu.hideMenu('menu2')" vAlign=center align=left width=142 height=17><A class="rollmenu" href="index.php?uniqcode=JOB&menu_no=2&submenutop=EIMModule&menu_no_top=eim"><?php echo $lang_Menu_Admin_Job_JobTitles; ?></A></TD>
                                </TR>
                                <TR>
                                    <TD onMouseOver="ypSlideOutMenu.showMenu('menu2')" onMouseOut="ypSlideOutMenu.hideMenu('menu2')" vAlign=center align=left width=142 height=17><A class="rollmenu" href="index.php?uniqcode=SPC&menu_no=2&submenutop=EIMModule&menu_no_top=eim"><?php echo $lang_Menu_Admin_Job_JobSpecs; ?></A></TD>
                                </TR>
                                <TR>
                                    <TD onMouseOver="ypSlideOutMenu.showMenu('menu2');ypSlideOutMenu.showMenu('menu2')" onMouseOut="ypSlideOutMenu.hideMenu('menu2');ypSlideOutMenu.hideMenu('menu2')" vAlign=center align=left width=142 height=17><A class="rollmenu" href="index.php?uniqcode=SGR&menu_no=2&submenutop=EIMModule&menu_no_top=eim"><?php echo $lang_Menu_Admin_Job_PayGrades; ?></A></TD>
                                </TR>
                                <TR>
                                    <TD onMouseOver="ypSlideOutMenu.showMenu('menu2')" onMouseOut="ypSlideOutMenu.hideMenu('menu2')" vAlign=center align=left width=142 height=17><A class="rollmenu" href="index.php?uniqcode=EST&menu_no=2&submenutop=EIMModule&menu_no_top=eim"><?php echo $lang_Menu_Admin_Job_EmpStatus; ?></A></TD>
                                </TR>
                                <TR>
                                    <TD onMouseOver="ypSlideOutMenu.showMenu('menu2')" onMouseOut="ypSlideOutMenu.hideMenu('menu2')" vAlign=center align=left width=142 height=17><A class=rollmenu href="index.php?uniqcode=EEC&menu_no=2&submenutop=EIMModule&menu_no_top=eim"><?php echo $lang_Menu_Admin_Job_EEO; ?></A></TD>
                                </TR>
                            </TBODY>
                        </TABLE>
                    </DIV>
                </DIV>
                <!-- End SubMenu2  -->
                <!-- Begin SubMenu3 -->
                <DIV id=menu3Container>
                    <DIV id=menu3Content>
                        <TABLE cellSpacing=0 cellPadding=0 width=142 border=0>
                            <TBODY>
                                <TR>
                                    <TD onMouseOver="ypSlideOutMenu.showMenu('menu3')" onMouseOut="ypSlideOutMenu.hideMenu('menu3')" vAlign=center align=left width=142 height=17><A class=rollmenu href="index.php?uniqcode=EDU&menu_no=3&submenutop=EIMModule&menu_no_top=eim"><?php echo $lang_Menu_Admin_Quali_Education; ?></A> </TD>
                                </TR>
                                <TR>
                                    <TD onMouseOver="ypSlideOutMenu.showMenu('menu3')" onMouseOut="ypSlideOutMenu.hideMenu('menu3')" vAlign=center align=left width=142 height=17><A class=rollmenu href="index.php?uniqcode=LIC&menu_no=3&submenutop=EIMModule&menu_no_top=eim"><?php echo $lang_Menu_Admin_Quali_Licenses; ?></A></TD>
                                </TR>
                            </TBODY>

                        </TABLE>
                    </DIV>
                </DIV>
                <!-- End SubMenu3 -->
                <!-- Begin SubMenu4 -->
                <DIV id=menu4Container>
                    <DIV id=menu4Content>
                        <TABLE cellSpacing=0 cellPadding=0 width=142 border=0>
                            <TBODY>
                                <TR>
                                    <TD onMouseOver="ypSlideOutMenu.showMenu('menu4')" onMouseOut="ypSlideOutMenu.hideMenu('menu4')" vAlign=center align=left width=142 height=17><A class=rollmenu href="index.php?uniqcode=SKI&menu_no=3&submenutop=EIMModule&menu_no_top=eim"><?php echo $lang_Menu_Admin_Skills_Skills; ?></A></TD>
                                </TR>
                                <TR>
                                    <TD onMouseOver="ypSlideOutMenu.showMenu('menu4')" onMouseOut="ypSlideOutMenu.hideMenu('menu4')" vAlign=center align=left width=142 height=17><A class=rollmenu href="index.php?uniqcode=LAN&menu_no=3&submenutop=EIMModule&menu_no_top=eim"><?php echo $lang_Menu_Admin_Skills_Languages; ?></A></TD>
                                </TR>
                            </TBODY>
                        </TABLE>
                    </DIV>
                </DIV>
                <!-- End SubMenu4 -->
                <!-- Begin SubMenu5 -->
                <DIV id=menu5Container>
                    <DIV id=menu5Content>
                        <TABLE cellSpacing=0 cellPadding=0 width=142 border=0>
                            <TBODY>
                                <TR>
                                    <TD onMouseOver="ypSlideOutMenu.showMenu('menu5')" onMouseOut="ypSlideOutMenu.hideMenu('menu5')" vAlign=center align=left width=142 height=17><A class=rollmenu href="index.php?uniqcode=MEM&menu_no=4&submenutop=EIMModule&menu_no_top=eim"><?php echo $lang_Menu_Admin_Memberships_MembershipTypes; ?></A></TD>
                                </TR>
                                <TR>
                                    <TD onMouseOver="ypSlideOutMenu.showMenu('menu5')" onMouseOut="ypSlideOutMenu.hideMenu('menu5')" vAlign=center align=left width=142 height=17><A class=rollmenu href="index.php?uniqcode=MME&menu_no=4&submenutop=EIMModule&menu_no_top=eim"><?php echo $lang_Menu_Admin_Memberships_Memberships; ?></A></TD>
                                </TR>
                            </TBODY>
                        </TABLE>
                    </DIV>
                </DIV>
                <!-- End SubMenu5 -->
                <!-- Begin SubMenu9 -->
                <DIV id=menu9Container>
                    <DIV id=menu9Content>
                        <TABLE cellSpacing=0 cellPadding=0 width=142 border=0>
                            <TBODY>
                                <TR>
                                    <TD onMouseOver="ypSlideOutMenu.showMenu('menu9')" onMouseOut="ypSlideOutMenu.hideMenu('menu9')" vAlign=center align=left width=142 height=17><A class=rollmenu href="index.php?uniqcode=NAT&submenutop=EIMModule&menu_no_top=eim"><?php echo $lang_Menu_Admin_NationalityNRace_Nationality; ?></A></TD>
                                </TR>
                            </TBODY>
                        </TABLE>
                    </DIV>
                </DIV>
                <!-- End SubMenu9 -->
                <!-- Begin SubMenu12 -->
                <DIV id=menu12Container>
                    <DIV id=menu12Content>
                        <TABLE cellSpacing=0 cellPadding=0 width=142 border=0>
                            <TBODY>
                                <TR>
                                    <TD onMouseOver="ypSlideOutMenu.showMenu('menu12')" onMouseOut="ypSlideOutMenu.hideMenu('menu12')" vAlign=center align=left width=142 height=17><A class=rollmenu  href="index.php?uniqcode=USR&menu_no=1&submenutop=BR&menu_no_top=eim&isAdmin=Yes"><?php echo $lang_Menu_Admin_Users_HRAdmin; ?></A></TD>
                                </TR>
                                <TR>
                                    <TD onMouseOver="ypSlideOutMenu.showMenu('menu12')" onMouseOut="ypSlideOutMenu.hideMenu('menu12')" vAlign=center align=left width=142 height=17><A class=rollmenu  href="index.php?uniqcode=USR&menu_no=1&submenutop=BR&menu_no_top=eim&isAdmin=No"><?php echo $lang_Menu_Admin_Users_ESS; ?></A></TD>
                                </TR>
            <!--                    <tr>
                                    <TD onMouseOver="ypSlideOutMenu.showMenu('menu12')" onMouseOut="ypSlideOutMenu.hideMenu('menu12')" vAlign=center align=left width=142 height=17><A class=rollmenu  href="index.php?uniqcode=USG&menu_no=1&submenutop=BR&menu_no_top=eim"><?php echo $lang_Menu_Admin_Users_UserGroups; ?></A></TD>
                                    </TR>
                                -->
                            </TBODY>
                        </TABLE>
                    </DIV>
                </DIV>
                <!-- End SubMenu12 -->
                <!-- Begin SubMenu13 -->
                <DIV id=menu13Container>
                    <DIV id=menu13Content>
                                <?php
                                $allowedRoles = array($authorizeObj->roleAdmin, $authorizeObj->roleSupervisor);

                                if ($authorizeObj->firstRole($allowedRoles)) {
                                    ?>
                            <TABLE cellSpacing=0 cellPadding=0 width=142 border=0>
                                <TBODY>
                                    <?php
                                    if ($authorizeObj->isESS()) {
                                        ?>
                                        <TR>
                                            <TD onMouseOver="ypSlideOutMenu.showMenu('menu13')" onMouseOut="ypSlideOutMenu.hideMenu('menu13')" onClick="ypSlideOutMenu.hideMenu('menu13')" vAlign=center align=left width=142 height=17><A class=rollmenu href="lib/controllers/CentralController.php?leavecode=Leave&action=Leave_Summary&id=<?php echo $_SESSION['empID']; ?>" target="rightMenu"><?php echo $lang_Menu_Leave_PersonalLeaveSummary; ?></A></TD>
                                        </TR>
            <?php
        }
        ?>
                                    <TR>
                                        <TD onMouseOver="ypSlideOutMenu.showMenu('menu13')" onMouseOut="ypSlideOutMenu.hideMenu('menu13')" onClick="ypSlideOutMenu.hideMenu('menu13')" vAlign=center align=left width=142 height=17><A class=rollmenu href="lib/controllers/CentralController.php?leavecode=Leave&action=Leave_Select_Employee_Leave_Summary" target="rightMenu"><?php echo $lang_Menu_Leave_EmployeeLeaveSummary; ?></A></TD>
                                    </TR>
                                </TBODY>
                            </TABLE>
        <?php
    }
    ?>
                    </DIV>
                </DIV>
                <!-- End SubMenu13 -->
                <!-- Begin SubMenu14 -->
                <DIV id=menu14Container>
                    <DIV id=menu14Content>
    <?php
    if ($authorizeObj->isAdmin()) {
        ?>
                            <TABLE cellSpacing=0 cellPadding=0 width=142 border=0>
                                <TBODY>
                                    <TR>
                                        <TD onMouseOver="ypSlideOutMenu.showMenu('menu14')" onMouseOut="ypSlideOutMenu.hideMenu('menu14')" onClick="ypSlideOutMenu.hideMenu('menu14')" vAlign=center align=left width=142 height=17><A class=rollmenu  href="lib/controllers/CentralController.php?leavecode=Leave&action=Holiday_Weekend_List" target="rightMenu"><?php echo $lang_Menu_Leave_DefineDaysOff_Weekends; ?></A></TD>
                                    </TR>
                                    <TR>
                                        <TD onMouseOver="ypSlideOutMenu.showMenu('menu14')" onMouseOut="ypSlideOutMenu.hideMenu('menu14')" onClick="ypSlideOutMenu.hideMenu('menu14')" vAlign=center align=left width=142 height=17><A class=rollmenu  href="lib/controllers/CentralController.php?leavecode=Leave&action=Holiday_Specific_List" target="rightMenu"><?php echo $lang_Menu_Leave_DefineDaysOff_SpecificHolidays; ?></A></TD>
                                    </TR>
                                </TBODY>
                            </TABLE>
        <?php
    }
    ?>
                    </DIV>
                </DIV>
                <!-- End SubMenu14 -->
                <!-- Begin SubMenu15 -->
                <DIV id=menu15Container>
                    <DIV id=menu15Content>
    <?php
    if ($authorizeObj->isAdmin()) {
        ?>
                            <TABLE cellSpacing=0 cellPadding=0 width=142 border=0>
                                <TBODY>
                                    <tr>
                                        <td onMouseOver="ypSlideOutMenu.showMenu('menu15')" onMouseOut="ypSlideOutMenu.hideMenu('menu15')" vAlign=center align=left width=142 height=17><a class=rollmenu  href="index.php?uniqcode=EMX&submenutop=EIMModule&menu_no_top=eim" ><?php echo $lang_Menu_Admin_EmailConfiguration; ?></a></td>
                                    </tr>
                                    <tr>
                                        <td onMouseOver="ypSlideOutMenu.showMenu('menu15')" onMouseOut="ypSlideOutMenu.hideMenu('menu15')" vAlign=center align=left width=142 height=17><a class=rollmenu href="index.php?uniqcode=ENS&submenutop=EIMModule&menu_no_top=eim" ><?php echo $lang_Menu_Admin_EmailSubscribe; ?></a></td>
                                    </tr>
                                </TBODY>
                            </TABLE>
        <?php
    }
    ?>
                    </DIV>
                </DIV>
                <!-- End SubMenu15 -->
                <!-- Begin SubMenu16 -->
                <DIV id=menu16Container>
                    <DIV id=menu16Content>
                                <?php
                                $allowedRoles = array($authorizeObj->roleAdmin, $authorizeObj->roleSupervisor);

                                if ($authorizeObj->firstRole($allowedRoles)) {
                                    ?>
                            <TABLE cellSpacing=0 cellPadding=0 width=142 border=0>
                                <TBODY>
        <?php
        if ($authorizeObj->isESS()) {
            ?>
                                        <TR>
                                            <TD onMouseOver="ypSlideOutMenu.showMenu('menu16')" onMouseOut="ypSlideOutMenu.hideMenu('menu16')" onClick="ypSlideOutMenu.hideMenu('menu16')" vAlign=center align=left width=142 height=17>
                                                <A class=rollmenu href="lib/controllers/CentralController.php?timecode=Time&action=View_Current_Timesheet&clientTimezoneOffset=" target="rightMenu"><?php echo $lang_Menu_Time_PersonalTimesheet; ?></A>
                                            </TD>
                                        </TR>
            <?php
        }
        if ($authorizeObj->isAdmin() || $authorizeObj->isSupervisor()) {
            ?>
                                        <TR>
                                            <TD onMouseOver="ypSlideOutMenu.showMenu('menu16')" onMouseOut="ypSlideOutMenu.hideMenu('menu16')" onClick="ypSlideOutMenu.hideMenu('menu16')" vAlign=center align=left width=142 height=17>
                                                <A class=rollmenu href="lib/controllers/CentralController.php?timecode=Time&action=Select_Timesheets_View" target="rightMenu"><?php echo $lang_Menu_Time_PrintTimesheets; ?></A>
                                            </TD>
                                        </TR>
            <?php
        }
        ?>
                                    <TR>
                                        <TD onMouseOver="ypSlideOutMenu.showMenu('menu16')" onMouseOut="ypSlideOutMenu.hideMenu('menu16')" onClick="ypSlideOutMenu.hideMenu('menu16')" vAlign=center align=left width=142 height=17>
                                            <A class=rollmenu href="lib/controllers/CentralController.php?timecode=Time&action=View_Select_Employee" target="rightMenu"><?php echo $lang_Menu_Time_EmployeeTimesheets; ?></A>
                                        </TD>
                                    </TR>
                                </TBODY>
                            </TABLE>
        <?php
    }
    ?>
                    </DIV>
                </DIV>
                <!-- End SubMenu16 -->
                <!-- Begin SubMenu17 -->
                <DIV id=menu17Container>
                    <DIV id=menu17Content>
    <?php
    if ($authorizeObj->isAdmin()) {
        ?>
                            <TABLE cellSpacing="0" cellPadding="0" width="142" border="0"">
                                   <TBODY>
                                    <tr>
                                        <td onMouseOver="ypSlideOutMenu.showMenu('menu17')" onMouseOut="ypSlideOutMenu.hideMenu('menu17')"
                                            vAlign="center" align="left" width="142" height="17">
                                            <a class="rollmenu" href="index.php?uniqcode=CUS&menu_no=2&submenutop=EIMModule&menu_no_top=eim" >
                                                <?php echo $lang_Menu_Admin_Customers; ?></a></td>
                                    </tr>
                                    <tr>
                                        <td onMouseOver="ypSlideOutMenu.showMenu('menu17')" onMouseOut="ypSlideOutMenu.hideMenu('menu17')"
                                            vAlign="center" align="left" width="142" height="17"">
                                            <a class="rollmenu" href="index.php?uniqcode=PRJ&menu_no=2&submenutop=EIMModule&menu_no_top=eim" >
                                                <?php echo $lang_Menu_Admin_Projects; ?></a></td>
                                    </tr>
                                    <tr>
                                        <td onMouseOver="ypSlideOutMenu.showMenu('menu17')" onMouseOut="ypSlideOutMenu.hideMenu('menu17')"
                                            vAlign="center" align="left" width="142" height="17"">
                                            <a class="rollmenu" href="index.php?uniqcode=PAC&menu_no=2&submenutop=EIMModule&menu_no_top=eim" >
                            <?php echo $lang_Admin_ProjectActivities; ?></a></td>
                                    </tr>
                                </TBODY>
                            </TABLE>
        <?php
    }
    ?>
                    </DIV>
                </DIV>
                <!-- End SubMenu17 -->
                <!-- Begin SubMenu18 -->
                <DIV id=menu18Container>
                    <DIV id=menu18Content>
    <?php
    if ($authorizeObj->isAdmin()) {
        ?>
                            <TABLE cellSpacing="0" cellPadding="0" width="142" border="0"">
                                   <TBODY>
                                    <tr>
                                        <td onMouseOver="ypSlideOutMenu.showMenu('menu18')" onMouseOut="ypSlideOutMenu.hideMenu('menu18')"
                                            vAlign="center" align="left" width="142" height="17">
                                            <a class="rollmenu" href="index.php?uniqcode=CSE&submenutop=EIMModule&menu_no_top=eim" >
                                                <?php echo $lang_Menu_Admin_DataExport; ?></a></td>
                                    </tr>
                                    <tr>
                                        <td onMouseOver="ypSlideOutMenu.showMenu('menu18')" onMouseOut="ypSlideOutMenu.hideMenu('menu18')"
                                            vAlign="center" align="left" width="142" height="17"">
                                            <a class="rollmenu" href="index.php?uniqcode=CEX&submenutop=EIMModule&menu_no_top=eim" >
                                                <?php echo $lang_Menu_Admin_DataExportDefine; ?></a></td>
                                    </tr>
                                    <tr>
                                        <td onMouseOver="ypSlideOutMenu.showMenu('menu18')" onMouseOut="ypSlideOutMenu.hideMenu('menu18')"
                                            vAlign="center" align="left" width="142" height="17">
                                            <a class="rollmenu" href="index.php?uniqcode=IMP&submenutop=EIMModule&menu_no_top=eim" >
                                                <?php echo $lang_Menu_Admin_DataImport; ?></a></td>
                                    </tr>
                                    <tr>
                                        <td onMouseOver="ypSlideOutMenu.showMenu('menu18')" onMouseOut="ypSlideOutMenu.hideMenu('menu18')"
                                            vAlign="center" align="left" width="142" height="17"">
                                            <a class="rollmenu" href="index.php?uniqcode=CIM&submenutop=EIMModule&menu_no_top=eim" >
                            <?php echo $lang_Menu_Admin_DataImportDefine; ?></a></td>
                                    </tr>
                                </TBODY>
                            </TABLE>
        <?php
    }
    ?>
                    </DIV>
                </DIV>
                <!-- End SubMenu18 -->
                <!-- Begin SubMenu19 -->
                <DIV id=menu19Container>
                    <DIV id=menu19Content>
                        <TABLE cellSpacing=0 cellPadding=0 width=142 border=0>
                            <TBODY>
    <?php
    if ($authorizeObj->isAdmin()) {
        ?>
                                    <TR>
                                        <TD onMouseOver="ypSlideOutMenu.showMenu('menu19')" onMouseOut="ypSlideOutMenu.hideMenu('menu19')" onClick="ypSlideOutMenu.hideMenu('menu19')" vAlign=center align=left width=142 height=17>
                                            <A class=rollmenu href="lib/controllers/CentralController.php?benefitcode=Benefits&action=Define_Health_Savings_Plans" target="rightMenu"><?php echo $lang_Menu_Benefits_Define_Health_savings_plans; ?></A>
                                        </TD>
                                    </TR>

                                    <TR>
                                        <TD onMouseOver="ypSlideOutMenu.showMenu('menu19')" onMouseOut="ypSlideOutMenu.hideMenu('menu19')" onClick="ypSlideOutMenu.hideMenu('menu19')" vAlign=center align=left width=142 height=17>
                                            <A class=rollmenu href="lib/controllers/CentralController.php?benefitcode=Benefits&action=Hsp_Summary&year=<?php echo date('Y'); ?>" target="rightMenu"><?php echo $lang_Menu_Benefits_EmployeeHspSummary; ?></A>
                                        </TD>
                                    </TR>
                                    <TR>
                                        <TD onMouseOver="ypSlideOutMenu.showMenu('menu19')" onMouseOut="ypSlideOutMenu.hideMenu('menu19')" onClick="ypSlideOutMenu.hideMenu('menu19')" vAlign=center align=left width=142 height=17>
                                            <A class=rollmenu href="lib/controllers/CentralController.php?benefitcode=Benefits&action=List_Hsp_Due" target="rightMenu"><?php echo $lang_Benefits_HspPaymentsDue; ?></A>
                                        </TD>
                                    </TR>
                                    <TR>
                                        <TD onMouseOver="ypSlideOutMenu.showMenu('menu19')" onMouseOut="ypSlideOutMenu.hideMenu('menu19')" onClick="ypSlideOutMenu.hideMenu('menu19')" vAlign=center align=left width=142 height=17>
                                            <A class=rollmenu href="lib/controllers/CentralController.php?benefitcode=Benefits&action=Hsp_Expenditures_Select_Year_And_Employee" target="rightMenu"><?php echo $lang_Benefits_HspExpenditures; ?></A>
                                        </TD>
                                    </TR>
                                    <TR>
                                        <TD onMouseOver="ypSlideOutMenu.showMenu('menu19')" onMouseOut="ypSlideOutMenu.hideMenu('menu19')" onClick="ypSlideOutMenu.hideMenu('menu19')" vAlign=center align=left width=142 height=17>
                                            <A class=rollmenu href="lib/controllers/CentralController.php?benefitcode=Benefits&action=Hsp_Used_Select_Year&year=<?php echo date('Y'); ?>" target="rightMenu"><?php echo $lang_Benefits_HspUsed; ?></A>
                                        </TD>
                                    </TR>
        <?php
    } else if ($authorizeObj->isESS()) {
        ?>
                                    <TR>
                                        <TD onMouseOver="ypSlideOutMenu.showMenu('menu19')" onMouseOut="ypSlideOutMenu.hideMenu('menu19')" onClick="ypSlideOutMenu.hideMenu('menu19')" vAlign=center align=left width=142 height=17>
                                            <A class=rollmenu href="lib/controllers/CentralController.php?benefitcode=Benefits&action=Hsp_Expenditures&year=<?php echo date('Y'); ?>&employeeId=<?php echo $_SESSION['empID']; ?>" target="rightMenu"><?php echo $lang_Benefits_HspExpenditures; ?></A>
                                        </TD>
                                    </TR>
        <?php
    }
    if (Config::getHspCurrentPlan() > 0) { // Show only when Admin has defined a HSP paln (HSP not defined)
        if ($authorizeObj->isESS()) {
            ?>
                                        <TR>
                                            <TD onMouseOver="ypSlideOutMenu.showMenu('menu19')" onMouseOut="ypSlideOutMenu.hideMenu('menu19')" onClick="ypSlideOutMenu.hideMenu('menu19')" vAlign=center align=left width=142 height=17>
                                                <A class=rollmenu href="lib/controllers/CentralController.php?benefitcode=Benefits&action=Hsp_Request_Add_View" target="rightMenu"><?php echo $lang_Benefits_HspRequest; ?></A>
                                            </TD>
                                        </TR>
            <?php
        }
        if ($authorizeObj->isESS()) {
            ?>
                                        <TR>
                                            <TD onMouseOver="ypSlideOutMenu.showMenu('menu19')" onMouseOut="ypSlideOutMenu.hideMenu('menu19')" onClick="ypSlideOutMenu.hideMenu('menu19')" vAlign=center align=left width=142 height=17>
                                                <A class=rollmenu href="<?php echo $personalHspSummary; ?>" target="rightMenu"><?php echo $lang_Menu_Benefits_PersonalHspSummary; ?></A>
                                            </TD>
                                        </TR>
            <?php
        }
    } // HSP not defined ends
    ?>
                            </TBODY>
                        </TABLE>
                    </DIV>
                </DIV>
                <!-- End SubMenu19 -->

                <!-- Begin SubMenu20 -->
                <DIV id=menu20Container>
                    <DIV id=menu20Content>
                        <TABLE cellSpacing=0 cellPadding=0 width=142 border=0>
                            <TBODY>
    <?php if ($authorizeObj->isAdmin()) { ?>
                                    <TR>
                                        <TD onMouseOver="ypSlideOutMenu.showMenu('menu20')" onMouseOut="ypSlideOutMenu.hideMenu('menu20')" onClick="ypSlideOutMenu.hideMenu('menu20')" vAlign=center align=left width=142 height=17>
                                            <A class=rollmenu href="lib/controllers/CentralController.php?benefitcode=Benefits&action=Benefits_Schedule_Select_Year" target="rightMenu"><?php echo $lang_Benefits_ViewPayrollSchedule; ?></A>
                                        </TD>
                                    </TR>
                                    <TR>
                                        <TD onMouseOver="ypSlideOutMenu.showMenu('menu20')" onMouseOut="ypSlideOutMenu.hideMenu('menu20')" onClick="ypSlideOutMenu.hideMenu('menu20')" vAlign=center align=left width=142 height=17>
                                            <A class=rollmenu href="lib/controllers/CentralController.php?benefitcode=Benefits&action=View_Add_Pay_Period" target="rightMenu"><?php echo $lang_Benefits_AddPayPeriod; ?></A>
                                        </TD>
                                    </TR>
    <?php } ?>
                            </TBODY>
                        </TABLE>
                    </DIV>
                </DIV>
                <!-- End SubMenu20 -->

                <!-- ------------------ End Menu ------------------ -->
            </td>
            <td width="779" valign="top" id="rightMenuHolder">
                <table width='100%' cellpadding='0' cellspacing='0' border='0' class='moduleTitle'>
                    <tr>
                        <td>
                        <td valign="top">
    <?php if ((isset($_GET['menu_no_top'])) && ($_GET['menu_no_top'] == "home")) { ?>
                                <iframe src="home.php" id="rightMenu" name="rightMenu" width="100%" height="400" frameborder="0"></iframe>
                            <?php } elseif ((isset($_GET['menu_no_top'])) && ($_GET['menu_no_top'] == "eim") && ($arrRights['view'] || $allowAdminView)) { ?>
                                <iframe src="./lib/controllers/CentralController.php?uniqcode=<?php echo (isset($_GET['uniqcode'])) ? $_GET['uniqcode'] : $defaultAdminView; ?>&VIEW=MAIN<?php echo isset($_GET['isAdmin']) ? ('&isAdmin=' . $_GET['isAdmin']) : '';
                        echo isset($_GET['pageNo']) ? '&pageNo=1' : '' ?>" id="rightMenu" name="rightMenu" width="100%" height="400" frameborder="0"> </iframe>
                            <?php } elseif ((isset($_GET['menu_no_top'])) && ($_GET['menu_no_top'] == "hr") && $arrRights['view']) { ?>
                                <iframe src="./lib/controllers/CentralController.php?reqcode=<?php echo (isset($_GET['reqcode'])) ? $_GET['reqcode'] : 'EMP' ?>&VIEW=MAIN" id="rightMenu" name="rightMenu" width="100%" height="400" frameborder="0"> </iframe>

                            <?php } elseif ((isset($_GET['menu_no_top'])) && ($_GET['menu_no_top'] == "ldap") && $arrRights['view']) { ?>
                                <iframe src="./lib/controllers/CentralController.php?uniqcode=<?php echo (isset($_GET['uniqcode'])) ? $_GET['uniqcode'] : '' ?>&VIEW=MAIN" id="rightMenu" name="rightMenu" width="100%" height="400" frameborder="0"> </iframe>


                            <?php } else if ((isset($_GET['menu_no_top'])) && ($_GET['menu_no_top'] == "bug")) { ?>
                                <iframe src="./lib/controllers/CentralController.php?mtcode=BUG&capturemode=addmode" id="rightMenu" name="rightMenu" width="100%" height="750" frameborder="0"> </iframe>
                            <?php } elseif ((isset($_GET['menu_no_top'])) && ($_GET['menu_no_top'] == "rep")) { ?>
                                <iframe src="./lib/controllers/CentralController.php?repcode=<?php echo isset($_GET['repcode']) ? $_GET['repcode'] : 'EMPVIEW' ?>&VIEW=MAIN" id="rightMenu" name="rightMenu" width="100%" height="400" frameborder="0"> </iframe>
                            <?php } elseif ((isset($_GET['menu_no_top'])) && ($_GET['menu_no_top'] == "ess")) { ?>
                                <iframe src="./lib/controllers/CentralController.php?reqcode=ESS&id=<?php echo $_SESSION['empID'] ?>&capturemode=updatemode" id="rightMenu" name="rightMenu" width="100%" height="400" frameborder="0"> </iframe>
                            <?php } elseif ((isset($_GET['menu_no_top'])) && ($_GET['menu_no_top'] == "ess")) { ?>
                                <iframe src="./lib/controllers/CentralController.php?reqcode=<?php echo (isset($_GET['reqcode'])) ? $_GET['reqcode'] : 'ESS' ?>&id=<?php echo $_SESSION['empID'] ?>" id="rightMenu" name="rightMenu" width="100%" height="400" frameborder="0"> </iframe>
                            <?php } elseif ((isset($_GET['menu_no_top'])) && ($_GET['menu_no_top'] == "leave")) { ?>
                                <iframe src="<?php echo $leaveHomePage; ?>" id="rightMenu" name="rightMenu" width="100%" height="400" frameborder="0"> </iframe>
                            <?php } elseif ((isset($_GET['menu_no_top'])) && ($_GET['menu_no_top'] == "time")) { ?>
                                <iframe src="<?php echo $timeHomePage; ?>" id="rightMenu" name="rightMenu" width="100%" height="400" frameborder="0"> </iframe>
    <?php } elseif ((isset($_GET['menu_no_top'])) && ($_GET['menu_no_top'] == "benefits")) { ?>
                                <iframe src="<?php echo $beneftisHomePage; ?>" id="rightMenu" name="rightMenu" width="100%" height="400" frameborder="0"> </iframe>
                <?php } elseif ((isset($_GET['menu_no_top'])) && ($_GET['menu_no_top'] == "recruit")) { ?>
                                <iframe src="<?php echo $recruitHomePage; ?>" id="rightMenu" name="rightMenu" width="100%" height="400" frameborder="0"> </iframe>
                <?php } ?>

                        </td>
                    </tr>
                </table>
<?php } elseif ((isset($_GET['menu_no_top'])) && ($_GET['menu_no_top'] == "hr") && $arrRights['view']) { ?>
                <iframe src="./lib/controllers/CentralController.php?reqcode=<?php echo (isset($_GET['reqcode'])) ? $_GET['reqcode'] : 'EMP' ?>&VIEW=MAIN" id="rightMenu" name="rightMenu" width="100%" height="400" frameborder="0"> </iframe>
<?php } elseif ((isset($_GET['menu_no_top'])) && ($_GET['menu_no_top'] == "ess")) { ?>
                <iframe src="./lib/controllers/CentralController.php?reqcode=ESS&id=<?php echo $_SESSION['empID'] ?>&capturemode=updatemode" id="rightMenu" name="rightMenu" width="100%" height="400" frameborder="0"> </iframe>
<?php } ?>
            <table width="100%">
                <tr>
                    <td align="center"><a href="http://www.orangehrm.com" target="_blank">OrangeHRM</a> ver 2.4.1 &copy; OrangeHRM Inc. 2005 - 2008 All rights reserved.</td>
                </tr>
            </table>
            <script language="javascript">
                    function windowDimensions() {
                        if (document.compatMode && document.compatMode != "BackCompat") {
                            x = document.documentElement.clientWidth;
                        } else {
                            x = document.body.clientWidth;
                        }
                        y = document.body.clientHeight;

                        return [x,y];
                    }
                    function exploitSpace() {
                        dimensions = windowDimensions();

                        if (document.getElementById("rightMenu")) {
                            document.getElementById("rightMenu").height = dimensions[1]-130;
                        }

                        if (document.getElementById("rightMenuHolder")) {
                            document.getElementById("rightMenuHolder").width = dimensions[0]-200;
                        }
                    }

                    exploitSpace();
                    window.onresize = exploitSpace;
            </script>
            </body>
            </html>
<?php ob_end_flush(); ?>
