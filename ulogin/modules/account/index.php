<?php
/**
* Модуль пользовательского профиля
* 
* @author Igor Ognichenko
* @copyright Copyright (c)2007-2010 by Kasseler CMS
* @link http://www.kasseler-cms.net/
* @filesource modules/account/index.php
* @version 2.0
*/
if (!defined('KASSELERCMS')) die("Hacking attempt!");
define('PRIVMSG', true);
define('ACCOUNT', true); 

global $navi, $tpl_create;
$navi = navi(array(), false, false);

$tpl_create->add2script("includes/javascript/kr_tab.js");

function main($plugins=true){
global $main, $navi, $link;
    if(isset($_FILES['Filedata'])) return false;
    if(!is_user()) redirect($main->url(array('module' => $main->module, 'do' => 'login')));
    echo $navi;
    open();
    echo "<center><table align='center' class='account_main'><tr>\n".
    "<td align='center' width='120'><a class='account_ico' href='".$main->url(array('module' => $main->module))."' title='{$main->lang['account']}'>{$main->img['account']}<br />{$main->lang['account']}</a></td>\n";
    $links = scan_dir("modules/{$main->module}/links/", "/(.*)\.php/");
    foreach($links as $value){
        $title = "";
        require_once "modules/{$main->module}/links/{$value}";
        $title = isset($main->lang[$link['name']]) ? $main->lang[$link['name']] : $link['name'];
        $url = str_replace('.php', '', $value);
        echo "<td width='120' align='center'><a class='account_ico' href='".$main->url(array('module' => $main->module, 'do' => $url))."' title='{$title}'><img src='includes/images/48x48/{$link['ico']}' alt='{$title}' /><br />{$title}</a></td>\n";
    }
    echo "<td width='120' align='center'><a class='account_ico' href='".$main->url(array('module' => $main->module, 'do' => 'controls'))."' title='{$main->lang['user_controls']}'>{$main->img['controls']}<br />{$main->lang['user_controls']}</a></td>\n".
    "<td width='120' align='center'><a class='account_ico' href='".$main->url(array('module' => $main->module, 'do' => 'logout'))."' title='{$main->lang['user_controls']}'>{$main->img['logout']}<br />{$main->lang['logout']}</a></td>\n".
    "</tr></table></center>\n";
    close();
    if($plugins==true){
        $files = scan_dir('modules/account/info/', '/(.+?)\.php$/i');
        foreach($files as $file) require_once 'modules/account/info/'.$file; 
    }
}

function login($msg=""){
global $userconf, $main, $navi;
    if(is_user()) redirect(MODULE);
    echo $navi;
    if(!empty($msg)) warning($msg);
    open();
    echo "<h3 class='option'>{$main->lang['account_login']}</h3>\n".
    "<form action='".$main->url(array('module' => $main->module, 'do' => 'sign'))."' method='post'>\n".
    "<table align='center' class='form'>\n".
    "<tr class='row_tr'><td class='form_text'>{$main->lang['login']}:</td><td class='form_input'>".in_text("user_name", "input_text")."</td></tr>\n".
    "<tr class='row_tr'><td class='form_text'>{$main->lang['password']}:</td><td class='form_input'>".in_pass("user_password", "input_text")."</td></tr>\n".
    "<tr class='row_tr'><td class='form_text'>{$main->lang['save_authorisation']}:</td><td class='form_input'>".in_chck("save_authorise", "")."</td></tr>\n".
    "<tr><td colspan='2' align='center' class='form_submit'>".send_button()."</td></tr>\n".
    "</table>\n</form>\n";
    echo ($userconf['registration']!='') ? "<center>[<a class='sys_link' href='".$main->url(array('module' => $main->module, 'do' => 'new_password'))."' title='{$main->lang['forgot_your_password']}'>{$main->lang['forgot_your_password']}</a> | <a class='sys_link' href='".$main->url(array('module' => $main->module, 'do' => 'new_user'))."' title='{$main->lang['new_user']}'>{$main->lang['new_user']}</a>]</center><br /><br />\n" : "<center>[<a href='".$main->url(array('module' => $main->module, 'do' => 'new_password'))."' title='{$main->lang['forgot_your_password']}'>{$main->lang['forgot_your_password']}</a>]</center><br /><br />\n";
    close();
}

function sign($redirect=true){
    if(hook_check(__FUNCTION__)) hook();    
    global $session, $main, $ip;
    if(!isset($_POST['user_name']) OR !isset($_POST['user_password'])) redirect(MODULE);
    $login = kr_filter($_POST['user_name'], TAGS);
    $pass = kr_filter($_POST['user_password'], TAGS);
    $_SESSION['save_authorise']= array_key_exists('save_authorise',$_POST)?kr_filter($_POST['save_authorise'], TAGS):'off';
    $msg = (empty($login) OR empty($pass)) ? $main->lang['error_login_pass'] : "";
    if(empty($msg)){
        require_once "modules/{$main->module}/classes/system.php";
        if(kr_file_exists("modules/{$main->module}/classes/{$main->config['compatibility']}.php")) {
            require_once "modules/{$main->module}/classes/{$main->config['compatibility']}.php";
            $account = new account;
        } else $account = new account_system;
        $account->login($login, $pass, $redirect);
    } else {
        $log = "".kr_date("Y-m-d H:i:s")." | Empty username or password | username::{$_POST['user_name']} | password::{$_POST['user_password']} | {$ip}||\n";
        login($msg);
    }
    if(isset($log) AND !empty($log) AND $main->config['log_error_user_logined']) file_write("uploads/logs/logined_logs.log", $log, "a");
}

function logout(){
global $main, $session;
    if(is_user()){
        require_once "modules/{$main->module}/classes/system.php";
        if(kr_file_exists("modules/{$main->module}/classes/{$main->config['compatibility']}.php")) {
            require_once "modules/{$main->module}/classes/{$main->config['compatibility']}.php";
            $account = new account;
        } else $account = new account_system;
        $account->logout();
        redirect(MODULE);
    } else redirect(BACK);
}

function new_user($msg=""){
global $main;
    if(is_user()) redirect(MODULE);
    echo navi(array(), false, false, $main->lang['registration_on_site']);
    $email = (!empty($_POST['user_email'])) ? kr_filter($_POST['user_email'], TAGS) : "";
    if(!empty($msg)) warning($msg);
    info($main->lang['register_info']);
    open();
    echo "<form action='".$main->url(array('module' => $main->module, 'do' => 'registration'))."' method='post'>\n".
    in_hide("timezone","0").
    "<table class='form' align='center'>\n".
    "<tr class='row_tr'><td class='form_text'>{$main->lang['login']}:<span class='star'>*</span></td><td class='form_input'>".in_text("user_name", "input_text", '', false, " onblur=\"checkuser(this);\"")."<div id='login_check'><img src='includes/images/pixel.gif' alt='' /></div></td></tr>\n".
    "<tr class='row_tr'><td class='form_text'>{$main->lang['email']}:<span class='star'>*</span></td><td class='form_input'>".in_text("user_email", "input_text")."</td></tr>\n".
    "<tr class='row_tr'><td class='form_text'>{$main->lang['password']}:</td><td class='form_input'>".in_pass("user_password", "input_text")."</td></tr>\n".
    "<tr class='row_tr'><td class='form_text'>{$main->lang['repassword']}:</td><td class='form_input'>".in_pass("user_repassword", "input_text", '', " onblur=\"checkpassword(this.value, document.getElementById('user_password').value, '{$main->lang['checked_pass']}', '{$main->lang['nochecked_pass']}');\"")."<div id='repass_check'><img src='includes/images/pixel.gif' alt='' /></div></td></tr>\n".
    captcha().
    "<tr><td colspan='2' align='center' class='form_submit'>".send_button()."</td></tr>\n".
    "</table>\n</form>\n";
    echo "<script type='text/javascript'>
    <!--
    \$(document).ready(function(){
       x = new Date()
       \$('#timezone').val(-x.getTimezoneOffset()/60);
    });
    // -->    
    </script>";
    close();
}

function registration(){
global $userconf, $patterns, $main;
    if(!isset($_POST['user_name']) OR !isset($_POST['user_email']) OR !isset($_POST['user_password']) OR !isset($_POST['user_repassword'])) redirect(MODULE);
    $_POST['user_email'] = kr_chenge_reg(preg_replace('/([\'"\+\s])/', '', $_POST['user_email']));
    $_POST['user_name'] = preg_replace('/([\'"\+])/', '', $_POST['user_name']);
    filter_arr(array('user_name', 'user_email', 'user_password', 'user_repassword'), POST, TAGS);
    $msg = error_empty(array('user_name'), array('error_user_name')).check_mail($_POST['user_email']).(!isset($_POST['create_user'])?check_captcha():"");
    if(cyr2lat($_POST['user_name'])=="") $msg .= $main->lang['error_uname_cyr2lat'];
    if(array_key_exists('user_name_length',$userconf)&&($userconf['user_name_length']!="")&&(kr_strlen($_POST['user_name'])<intval($userconf['user_name_length']))) $msg .= str_replace('{COUNT}', $userconf['user_name_length'], $main->lang['error_length_uname']);
    if(empty($_POST['user_password'])) $_POST['user_password'] = get_random_string($userconf['password_length']);
    elseif($_POST['user_password']!=$_POST['user_repassword']) $msg .= $main->lang['error_repass'];
    $msg .= ($userconf['password_length']>kr_strlen($_POST['user_password'])) ? str_replace('{COUNT}', $userconf['password_length'], $main->lang['error_lenghtpass']) : "";
    if(empty($msg)){
        $namesearch = kr_chenge_reg($_POST['user_email']);
        $result = $main->db->sql_query("SELECT uid FROM ".USERS." WHERE user_name='{$_POST['user_name']}' OR user_id='".cyr2lat($_POST['user_name'])."' OR user_email='{$namesearch}'");
        if($main->db->sql_numrows($result)==0){
            if(isset($_SESSION['validate'])){
                unset($_SESSION['validate']);
                require_once "modules/{$main->module}/classes/system.php";
                if(kr_file_exists("modules/{$main->module}/classes/{$main->config['compatibility']}.php")) {
                    require_once "modules/{$main->module}/classes/{$main->config['compatibility']}.php";
                    $account = new account;
                } else $account = new account_system;
                $account->newuser($_POST['user_name'], $_POST['user_email'], $_POST['user_password']);
            } else {
                echo navi(array(), false, false, $main->lang['registration_on_site']);
                open();
                $_SESSION['validate'] = true;
                echo "\n<form action='".$main->url(array('module' => $main->module, 'do' => 'registration'))."' method='post'>\n".
                in_hide("create_user", "true").
                in_hide("user_name", $_POST['user_name']).
                in_hide("user_email", $_POST['user_email']).
                in_hide("user_password", $_POST['user_password']).
                in_hide("user_repassword", $_POST['user_password']).
                in_hide("timezone",isset($_POST['timezone'])?$_POST['timezone']:"").
                "<h3 class='option'>{$main->lang['reg_user_data']}</h3>\n".
                "<table width='200' align='center' class='form'>\n".
                "<tr><td class='form_text'>{$main->lang['login']}:</td><td class='form_input'>{$_POST['user_name']}</td></tr>\n".
                "<tr><td class='form_text'>{$main->lang['email']}:</td><td class='form_input'>{$_POST['user_email']}</td></tr>\n".
                "<tr><td class='form_submit' colspan='2' align='center'>".send_button()."</td></tr>\n".
                "</table>".
                "</form>\n";
                close();
            }
        } else new_user("<li>{$main->lang['error_user_mail']}</li>");
    } else new_user($msg);
}

function user_birthday($birthday){
global $main;
    $date = explode("-", $birthday);    
    $days = $month = $years = array();
    for($i=0;$i<=31;$i++) $days[($i<10)?"0".$i:$i] = ($i<10)?"0".$i:$i;
    $return = "{$main->lang['day']}: ".in_sels('days', $days, '', $date[2]);
    for($i=0;$i<=12;$i++) $month[$i] = ($i<10)?"0".$i:$i;
    $return .= " {$main->lang['month']}: ".in_sels('months', $month, '', $date[1]);
    for($i=date("Y")-100;$i<=date("Y");$i++) $years[$i] = $i;
    $return .= " {$main->lang['year']}: ".in_sels('years', array('00' => '0000')+$years, '', $date[0]);
    return $return;
}

function user_gmt($gmt){
    $arr = array(
        '(GMT -12:00) Enevetok, Kvadzhaleyn',
        '(GMT -11:00) Midway Islands, Samoa',
        '(GMT -10:00) Hawaii',
        '(GMT -9:00) Alaska',
        '(GMT -8:00) Pacific Time (U.S. &amp; Canada), Tijuana',
        '(GMT -7:00) Mountain Time (U.S. &amp; Canada), Arizona',
        '(GMT -6:00) Central Time (U.S. &amp; Canada), Mexico City',
        '(GMT -5:00) Eastern Time (U.S. &amp; Canada), Bogota, Lima, Quito',
        '(GMT -4:00) Atlantic Time (Canada), Caracas, La Paz, Santiago',
        '(GMT -3:00) Brazil, Buenos Aires, Georgetown, Greenland',
        '(GMT -2:00) Mid-Atlantic',
        '(GMT -1:00) Azores, on Green Island Cape',
        '(GMT) Casablanca, Dublin, Edinburgh, Lisbon, London, Monrovia',
        '(GMT +1:00) Amsterdam, Berlin, Brussels, Madrid, Paris, Rome',
        '(GMT +2:00) Athens, Bucharest, Kiev, Chisinau, Minsk, Riga, Helsinki',
        '(GMT +3:00) Baghdad, Riyadh, Nairobi',
        '(GMT +4:00) Abu Dhabi, Baku, Moscow, Muscat, Tbilisi',
        '(GMT +5:00) Islamabad, Karachi, Tashkent',
        '(GMT +6:00) Almaty, Colombo, Dhaka, Ekaterinburg',
        '(GMT +7:00) Bangkok, Hanoi, Jakarta, Novosibirsk, Omsk',
        '(GMT +8:00) Beijing, Hong Kong, Krasnoyarsk, Perth, Singapore, Taipei',
        '(GMT +9:00) Irkutsk, Osaka, Sapporo, Seoul, Tokyo',
        '(GMT +10:00) Canberra, Melbourne, Guam, Sydney, Yakutsk',
        '(GMT +11:00) New Caledonia, Solomon Islands, Vladivostok',
        '(GMT +12:00) Auckland, Fiji, Kamchatka, Magadan, Wellington',
        '(GMT +13:00) Anadyr',
        '(GMT +14:00) Kiritimati (Christmas Island)'
    );
    $y = 0;
    $return = "<select name='user_gmt' class='select'>";
    for($i=-12;$i<=14;$i++){
        $return .= "<option value='{$i}'".(($gmt==$i) ? " selected='selected'" : "").">{$arr[$y]}</option>";
        $y++;
    }
    return  $return."</select>";
}

function controls($msg=""){
global $userconf, $main;
if(hook_check(__FUNCTION__)) return hook();    
    main(false);
    //unset($_SESSION['cache_session_user']);
    if(!empty($msg)) warning($msg);
    $_POST['day'] = (!isset($_POST['day'])) ? '00' : $_POST['day'];
    $_POST['month'] = (!isset($_POST['month'])) ? '00' : $_POST['month'];
    $_POST['year'] = (!isset($_POST['year'])) ? '0000' : $_POST['year'];
    $_POST['user_gmt'] = (!isset($_POST['user_gmt'])) ? '0' : $_POST['user_gmt'];
    $gender = in_radio('user_gender', 0, $main->lang['noinfo'], 'id0', ($main->user['user_gender']==0)?true:false)." ".in_radio('user_gender', 1, $main->lang['male'], 'id1', ($main->user['user_gender']==1)?true:false)." ".in_radio('user_gender', 2, $main->lang['woman'], 'id2', ($main->user['user_gender']==2)?true:false);    
    echo "<form id='form_control' enctype='multipart/form-data' method='post' action='".$main->url(array('module' => $main->module, 'do' => 'save_controls'))."'><div class='TabMenu'>
        <div class='tabContent'>
            ".open(true)."
            <div class='tabTitle'>{$main->lang['account_general']}</div>".
            "<table width='100%' class='form' cellspacing='1'>".
            "<tr class='row_tr'><td class='form_text'>{$main->lang['you_name']}:</td><td class='form_input_account'><a href='".$main->url(array('module' => $main->module, 'do' => 'user', 'id' => case_id($main->user['user_id'], $main->user['uid'])))."' title='{$main->lang['user_profile']}'>{$main->user['user_name']}</a></td></tr>\n".
            "<tr class='row_tr'><td class='form_text'>{$main->lang['you_ip']}:</td><td class='form_input_account'><a href='{$main->config['whois']}{$main->ip}'>{$main->ip}</a></td></tr>\n".
            (($main->config['geoip']==ENABLED) ? "<tr><td class='form_text'>{$main->lang['country']}:</td><td class='form_input_account'>".get_flag($main->user['user_country'])."</td></tr>\n" : "").
            "<tr class='row_tr'><td class='form_text'>{$main->lang['reg_date']}:</td><td class='form_input_account'>".format_date($main->user['user_regdate'])."</td></tr>\n".
            "<tr class='row_tr'><td class='form_text'>{$main->lang['birthday']}:</td><td class='form_input_account' id='datacase'>".user_birthday($main->user['user_birthday'])."&nbsp;</td></tr>\n".
            "<tr class='row_tr'><td class='form_text'>{$main->lang['gender']}:</td><td class='form_input_account'>{$gender}</td></tr>\n".
             "<tr class='row_tr'><td class='form_text'>{$main->lang['occupation']}:</td><td class='form_input_account'>".in_text("user_occupation", "input_text_accaunt", $main->user['user_occupation'])."</td></tr>\n".
            "<tr class='row_tr'><td class='form_text'>{$main->lang['interests']}:</td><td class='form_input_account'>".in_text("user_interests", "input_text_accaunt", $main->user['user_interests'])."</td></tr>\n".
            "<tr class='row_tr'><td class='form_text'>{$main->lang['locality']}:</td><td class='form_input_account'>".in_text("user_locality", "input_text_accaunt", $main->user['user_locality']).in_hide('user_locality_hk', $main->user['user_locality'])."</td></tr>\n".
            "<tr class='row_tr'><td class='form_text'>{$main->lang['signature']}:</td><td class='form_input_account'>".in_area("user_signature", 'textarea', 3, bb($main->user['user_signature'], DECODE))."</td></tr>\n".
            "<tr class='row_tr'><td class='form_text'>{$main->lang['language_site']}:</td><td class='form_input_account'>".get_lang_file(!empty($main->user['user_language'])?$main->user['user_language']:$main->language, false)."</td></tr>\n".
            "<tr class='row_tr'><td class='form_text'>{$main->lang['template_site']}:</td><td class='form_input_account'>".select_template($main->user['user_template'])."</td></tr>\n".
            "<tr class='row_tr'><td class='form_text'>GMT:</td><td class='form_input_account' id='time_zone'>".user_gmt($main->user['user_gmt'])."</td></tr>\n".
            "<tr class='row_tr'><td class='form_text'>{$main->lang['viewemail']}:</td><td class='form_input_account'>".in_chck('user_viewemail', 'checkbox', ($main->user['user_viewemail']==1)?ENABLED:'')."</td></tr>\n".
            "<tr class='row_tr'><td class='form_text'>{$main->lang['user_pm_send']}:</td><td class='form_input_account'>".in_chck('user_pm_send', 'checkbox', ($main->user['user_pm_send']==1)?true:false)."</td></tr>\n".
            "<tr class='row_tr'><td class='form_text'>{$main->lang['notify_receiving_pm']}:</td><td class='form_input_account'>".in_chck('user_new_pm_window', 'checkbox', ($main->user['user_new_pm_window']==1)?true:false)."</td></tr>\n".
            "<tr style='display: none' class='tabSend'><td class='form_submit' colspan='2' align='center'>".send_button()."</td></tr>\n".
            "</table>
            ".close(true)."
        </div>
        <div class='tabContent'>
            ".open(true)."
            <div class='tabTitle'>{$main->lang['account_contact']}</div>".
            "<table width='100%' class='form' cellspacing='1'>".
            "<tr class='row_tr'><td class='form_text'>{$main->lang['you_mail']}:<span class='star'>*</span></td><td class='form_input_account'>".in_text("user_email", "input_text_accaunt", $main->user['user_email'])."</td></tr>\n".    
            "<tr class='row_tr'><td class='form_text'>{$main->lang['icq']}:</td><td class='form_input_account'>".in_text("user_icq", "input_text_accaunt", $main->user['user_icq'])."</td></tr>\n".
            "<tr class='row_tr'><td class='form_text'>{$main->lang['aim']}:</td><td class='form_input_account'>".in_text("user_aim", "input_text_accaunt", $main->user['user_aim'])."</td></tr>\n".
            "<tr class='row_tr'><td class='form_text'>{$main->lang['yim']}:</td><td class='form_input_account'>".in_text("user_yim", "input_text_accaunt", $main->user['user_yim'])."</td></tr>\n".
            "<tr class='row_tr'><td class='form_text'>{$main->lang['msn']}:</td><td class='form_input_account'>".in_text("user_msnm", "input_text_accaunt", $main->user['user_msnm'])."</td></tr>\n".
            "<tr class='row_tr'><td class='form_text'>{$main->lang['skype']}:</td><td class='form_input_account'>".in_text("user_skype", "input_text_accaunt", $main->user['user_skype'])."</td></tr>\n".
            "<tr class='row_tr'><td class='form_text'>{$main->lang['googletalk']}:</td><td class='form_input_account'>".in_text("user_gtalk", "input_text_accaunt", $main->user['user_gtalk'])."</td></tr>\n".
            "<tr class='row_tr'><td class='form_text'>{$main->lang['home_page']}:</td><td class='form_input_account'>".in_text("user_website", "input_text_accaunt", $main->user['user_website'])."</td></tr>\n".
            "<tr style='display: none' class='tabSend'><td class='form_submit' colspan='2' align='center'>".send_button()."</td></tr>\n".
            "</table>
            ".close(true)."
        </div>
        <div class='tabContent'>
            ".open(true)."
            <div class='tabTitle'>{$main->lang['account_avatar']}</div>".
            "<table width='100%' class='form' cellspacing='1'>".
            "<tr class='row_tr'><td class='form_text'>{$main->lang['you_avatar']}:</td><td class='form_input_account' align='center'><input type='hidden' id='id_set_avatar' name='set_avatar' value='' /><img id='avatar' class='img_avatar' src='{$userconf['directory_avatar']}{$main->user['user_avatar']}' alt='{$main->lang['you_avatar']}' title='{$main->lang['you_avatar']}' /></td></tr>\n".
            "<tr class='row_tr'><td class='form_text'>{$main->lang['case_avatar']}:</td><td class='form_input_account' nowrap='nowrap'>".select_avatars()." <input class='case_submit' type='submit' onclick=\"newWindow = window_open('http://".get_host_name()."/index.php?module={$main->module}&amp;do=case_avatar&amp;id='+document.getElementById('cat').value+'', '', 'toolbar=0,width=720,height=600,resizable=0,menubar=0,scrollbars=1,status=0'); return false;\" value='{$main->lang['case']}' /></td></tr>\n".
            "<tr class='row_tr'><td class='form_text'>{$main->lang['load_avatar']}:</td><td class='form_input_account'><input type='file' name='userfile' value='' size='36' /></td></tr>\n".
            "<tr style='display: none' class='tabSend'><td class='form_submit' colspan='2' align='center'>".send_button()."</td></tr>\n".
            "</table>
            ".close(true)."
        </div>
        <div class='tabContent'>
            ".open(true)."
            <div class='tabTitle'>{$main->lang['new_user_password']}</div>".
            "<table width='100%' class='form' cellspacing='1'>".
            "<tr class='row_tr'><td class='form_text'>{$main->lang['you_password']}:</td><td class='form_input_account'>".in_pass("user_password", "input_password_accaunt").in_hide('user_password_hk', '')."</td></tr>\n".
            "<tr class='row_tr'><td class='form_text'>{$main->lang['you_newpassword']}:</td><td class='form_input_account'>".in_pass("user_newpassword", "input_password_accaunt").in_hide('user_newpassword_hk', '')."</td></tr>\n".
            "<tr class='row_tr'><td class='form_text'>{$main->lang['you_renewpassword']}:</td><td class='form_input_account'>".in_pass("user_renewpassword", "input_password_accaunt").in_hide('user_renewpassword_hk', '')."</td></tr>\n".
            "<tr><td class='form_submit' colspan='2' align='center'>".send_button()."</td></tr>\n".
            "</table>
            ".close(true)."
        </div>
    </div></form>
    <script type='text/javascript'>
        KR_AJAX.tab.init({className:'TabMenu', active:1});
        tabSend = KR_AJAX.document.getElementsByClassName('tabSend');
        for(i=0;i<tabSend.length;i++) tabSend[i].style.display='';
        document.getElementById('form_control').setAttribute('autocomplete','off');
    </script><br />";
}

function save_controls(){
global $userconf, $main, $code2languages;
if(hook_check(__FUNCTION__)) hook();
    unset($_SESSION['cache_session_user']);
    require_once "includes/classes/uploader.class.php";
    if(!is_user()) redirect($main->url(array('module' => $main->module, 'do' => 'login')));
    filter_arr(array('user_gmt', 'year', 'month', 'day', 'set_avatar', 'user_email', 'user_viewemail', 'user_pm_send', 'user_gender', 'user_skype', 'user_icq', 'user_aim', 'user_yim', 'user_msnm', 'user_website', 'user_occupation', 'user_interests', 'user_locality', 'user_signature', 'template', 'language', 'user_password', 'user_newpassword', 'user_renewpassword'), POST, TAGS);
    $_POST['user_gmt'] = (!isset($_POST['user_gmt'])||($_POST['user_gmt']=="")) ? '0' : $_POST['user_gmt'];
    $user_avatar = $main->user['user_avatar'];
    $viewemail = (isset($_POST['user_viewemail']) AND $_POST['user_viewemail']=='on') ? 1 : 0;
    $user_birthday = isset($_POST['years']) ? "{$_POST['years']}-{$_POST['months']}-{$_POST['days']}" : "";
    $_POST['user_website'] = (empty($_POST['user_website'])) ? "http://" : $_POST['user_website'];
    if(!empty($_POST['user_password']) AND $main->user['user_password']==pass_crypt($_POST['user_password'])){
        if($_POST['user_newpassword']==$_POST['user_renewpassword'] AND !empty($_POST['user_renewpassword'])) $user_password = pass_crypt($_POST['user_newpassword']);
        else {
            controls($main->lang['error_new_password']);
            return false;
        }
    } elseif($main->user['user_password']!=pass_crypt($_POST['user_password']) AND !empty($_POST['user_password'])){
        controls($main->lang['error_this_password']);
        return false;
    } else $user_password = $main->user['user_password'];
    $msg = check_mail($_POST['user_email']);
    if(empty($msg)){
        if(isset($_FILES['userfile']) AND !empty($_FILES['userfile']['name'])){
            $atrib = array(
                'dir'       => $userconf['directory_avatar'],
                'file'      => $_FILES['userfile'],
                'size'      => $userconf['size_avatar'],
                'type'      => explode(',', $userconf['type_avatar']),
                'width'     => $userconf['width_avatar'],
                'height'    => $userconf['height_avatar'],
                'name'      => $main->user['user_id'],
                'overwrite' => true
            );
            $avatar = new upload($atrib);
            if($avatar->error){
                controls($avatar->get_error_msg());
                return false;
            } elseif($avatar->is_upload) $user_avatar = $avatar->file;
        } else $user_avatar = (!empty($_POST['set_avatar'])) ? $_POST['set_avatar'] : $user_avatar;
        $_POST['user_pm_send'] = (isset($_POST['user_pm_send']) AND $_POST['user_pm_send']=='on') ? 1 : 0; 
        $_POST['user_new_pm_window'] = (isset($_POST['user_new_pm_window']) AND $_POST['user_new_pm_window']=='on') ? 1 : 0; 
        $_POST['user_avatar'] = $user_avatar;
        $_POST['user_password'] = $user_password;
        $_POST['user_birthday'] = $user_birthday;
        $_POST['viewemail'] = $viewemail;
        if(isset($_POST['language']) AND isset($code2languages[$_POST['language']])) setcookies($code2languages[$_POST['language']], 'lang');
        require_once "modules/{$main->module}/classes/system.php";
        if(kr_file_exists("modules/{$main->module}/classes/{$main->config['compatibility']}.php")) {
            require_once "modules/{$main->module}/classes/{$main->config['compatibility']}.php";
            $account = new account;
        } else $account = new account_system;
        $account->user_update();
        redirect($main->url(array('module' => $main->module, 'do' => 'controls')));
    } else controls($msg);
    return true;
}

function case_avatar(){
global $parametr_design, $lang, $userconf, $main;
    add_meta_value($lang['case_avatar2']);
    $parametr_design = false;
    if(!preg_match('/([a-zA-Z0-9_\-])/s', $_GET['id'])) redirect($main->url(array()));
    open();
    echo "<table class='table' cellspacing='1' align='center'>\n";
    $directory = $userconf['directory_avatar'].$_GET['id']."/";
    $i = 0;
    $dir = kr_opendir($directory);
    while(($file = readdir($dir))){
        if ($file!=".." AND $file!="." AND $file!="index.html" AND kr_is_file("{$directory}{$file}")){
            if($i==0) {echo  "<tr class='bgcolor5'>\n<td align='center' valign='top'><img onclick=\"set_avatar('{$file}', '{$userconf['directory_avatar']}');\" src='{$directory}{$file}' class='case_avatar' alt='{$lang['case_avatar']}' /></td>"; $i++;
            } elseif($i<5){echo  "<td align='center' valign='top'><img onclick=\"set_avatar('{$file}', '{$userconf['directory_avatar']}');\" width='110' height='110' src='{$directory}{$file}' class='case_avatar' alt='{$lang['case_avatar']}' /></td>\n"; $i++;
            } elseif($i==5){echo  "<td align='center' valign='top'><img onclick=\"set_avatar('{$file}', '{$userconf['directory_avatar']}');\" width='110' height='110' src='{$directory}{$file}' class='case_avatar' alt='{$lang['case_avatar']}' /></td>\n</tr>\n"; $i=0;}
        }
    }
    closedir($dir);
    echo "<tr>\n<td align='center' colspan='6' class='form_submit'><input type='submit' onclick='window.close();' value='{$lang['close']}' /></td>\n</tr>\n</table>";
    close();
}

function activation(){
global $main;
    echo navi(array(), false, false, $main->lang['activation_user']);
    $result = $main->db->sql_query("SELECT * FROM ".USERS." WHERE user_activation_code='{$_GET['code']}'");
    if($main->db->sql_numrows($result)>0){
        meta_refresh(5, $main->url(array('module' => $main->module)), $main->lang['your_user_activeation']);
        sql_update(array('user_activation' => '0'), USERS, "user_activation_code='{$_GET['code']}'");
    } else warning($main->lang['error_activation_user']);
}

function information($msg=''){
global $main, $img, $userconf, $tpl_create, $config,$template;
if(hook_check(__FUNCTION__)) return hook();    
    //Подключаем модуль комментариев
    require_once "includes/function/comments.php";
    if(isset($_POST['id'])) add_comment('', $userconf['comments_sort'], $userconf['guests_comments'], 'user');
    else {
        $sql_extra = "(u.uid='".intval($_GET['id'])."' OR u.user_name='{$_GET['id']}') AND u.user_id<>'guest'";
        $result = $main->db->sql_query("SELECT u.uid, u.user_id, u.user_name, u.user_email, u.user_skype, u.user_gtalk, u.user_icq, u.user_aim, u.user_yim, u.user_msnm, u.user_viewemail, u.user_avatar, u.user_website, u.user_locality, u.user_signature, u.user_interests, u.user_occupation, u.user_birthday, u.user_country, u.user_gender, u.user_regdate, u.user_last_ip, u.user_last_os, u.user_last_browser, u.user_group, u.user_points, u.rating, u.voted, g.id, g.title, g.color 
            FROM ".USERS." AS u LEFT JOIN ".GROUPS." AS g ON (g.id=u.user_group) 
            WHERE {$sql_extra} LIMIT 1");
        if($main->db->sql_numrows($result)>0){
            $row = $main->db->sql_fetchrow($result);
            $zodiak = get_zodiak($row['user_birthday']);
            echo navi(array(), false, false, $main->lang['userinfo']." {$row['user_name']}");
            $amenu=array(
              array($main->lang['user_list_news'],$main->url(array('module' => 'news', 'do' => 'userinfo','user' => urlencode($row['user_name'])))),
              array($main->lang['user_list_comments'],$main->url(array('module' => $main->module, 'do' => 'userinfo','user' => urlencode($row['user_name'])))),
              array($main->lang['user_list_post'],$main->url(array('module' => 'forum', 'do' => 'userinfo','op'=>'post','user' => urlencode($row['user_name'])))),
              array($main->lang['user_list_gratitude'],$main->url(array('module' => 'forum', 'do' => 'userinfo','op'=>'gratitude','user' => urlencode($row['user_name']))))
            );
            $ul="<ul>";
            foreach ($amenu as $value) {
               $ul.="<li><a href='{$value[1]}'>{$value[0]}</a></li>";
            }
            $ul.="</ul>";
            open();
            echo "<table width='100%' class='table' id='table_{$main->module}'>".
            "<tr><td width='160' valign='top' class='user_info'>".
            "<div align='center'><h3>".get_flag($row['user_country'])."{$row['user_name']}</h3><img src='uploads/avatars/{$row['user_avatar']}' alt='{$row['user_name']}' /></div><hr />".
            "<div align='left'>{$img['email']} ".(($row['user_viewemail']==1 OR is_support()) ? "<a target='_BLANK' href='mailto:{$row['user_email']}'>{$row['user_email']}</a>" : $main->lang['closed'])."</div>".        
            "<div align='left'>{$img['icq']} ".(empty($row['user_icq']) ? $main->lang['noinfo'] : "<a target='_BLANK' href='http://www.icq.com/people/about_me.php?uin={$row['user_icq']}'>{$row['user_icq']}</a>")."</div>".
            "<div align='left'>{$img['skype']} ".(empty($row['user_skype']) ? $main->lang['noinfo'] : "<a target='_BLANK' href='skype:{$row['user_skype']}?call'>{$row['user_skype']}</a>")."</div>".
            "<div align='left'>{$img['talk']} ".(empty($row['user_gtalk']) ? $main->lang['noinfo'] : $row['user_gtalk'])."</div>".
            "<div align='left'>{$img['aim']} ".(empty($row['user_aim']) ? $main->lang['noinfo'] : $row['user_aim'])."</div>".
            "<div align='left'>{$img['yim']} ".(empty($row['user_yim']) ? $main->lang['noinfo'] : $row['user_yim'])."</div>".
            "<div align='left'>{$img['msnm']} ".(empty($row['user_msnm']) ? $main->lang['noinfo'] : $row['user_msnm'])."</div>".
            ((is_support())?"<div align='left'>{$img['ip']} ".($row['user_last_ip']=='0.0.0.0' ? $main->lang['noinfo'] : "<a href='{$main->config['whois']}{$row['user_last_ip']}'>{$row['user_last_ip']}</a>")."</div>":"").
            "</td><td valign='top'><table width='100%' class='notable'>".
            "<tr><td width='150'>{$main->lang['birthday']}:</td><td>".(($row['user_birthday']=="0000-00-00")?$main->lang['no']:format_date($row['user_birthday']))."</td></tr>".
            (($row['user_group']!=0)?"<tr><td>{$main->lang['group']}:</td><td><font color='#{$row['color']}'>{$row['title']}</font></td></tr>":"").
            "<tr><td>{$main->lang['age']}:</td><td>".(($row['user_birthday']!='0000-00-00')?get_age($row['user_birthday']):$main->lang['noinfo'])."</td></tr>".
            "<tr><td>{$main->lang['zodiak']}:</td><td>".(($row['user_birthday']!='0000-00-00')?$zodiak[1]:$main->lang['noinfo'])."</td></tr>".
            "<tr><td>{$main->lang['gender']}:</td><td>".(($row['user_gender']==1)?$main->lang['male']:(($row['user_gender']==2)?$main->lang['woman']:$main->lang['noinfo']))."</td></tr>".
            "<tr><td>{$main->lang['reg_date']}:</td><td>".format_date($row['user_regdate'])."</td></tr>".
            "<tr><td>{$main->lang['home_page']}:</td><td>".(($row['user_website']!='http://' AND !empty($row['user_website']))?"<a href='{$row['user_website']}'>".preg_replace('/http:\/\/(.*?)(?!\/)(.+?)$/i', '\\2', $row['user_website'])."</a>":"")."</td></tr>\n".
            "<tr><td>{$main->lang['occupation']}:</td><td>".(!empty($row['user_occupation'])?$row['user_occupation']:$main->lang['noinfo'])."</td></tr>\n".
            "<tr><td>{$main->lang['interests']}:</td><td>".(!empty($row['user_interests'])?$row['user_interests']:$main->lang['noinfo'])."</td></tr>\n".
            "<tr><td>{$main->lang['locality']}:</td><td>".(!empty($row['user_locality'])?$row['user_locality']:$main->lang['noinfo'])."</td></tr>\n".
            "<tr><td>{$main->lang['rating']}:</td><td>".(($userconf['ratings']==ENABLED)?"<div class='rating_div' id='r_{$row['uid']}'>".(is_ajax() ? rating_calc($row['uid'], "r_{$row['uid']}", $row['rating'], 'users', $row['voted'], true) : "")."</div>":"&nbsp;")."</td></tr>\n".
            "<tr><td colspan='2'><hr />".parse_bb($row['user_signature'])."</td></tr>".
            "</table><div class='op_account' style='float:left;padding-top: 10px;'><ul style='margin:0'><li class='limenu'><a>[{$main->lang['user_static_info']}]</a>{$ul}</li></ul></div><div align='right' class='op_account' style='padding-top: 10px;'>[ <a href='".$main->url(array('module' => $main->module, 'do' => 'mail', 'id' => urlencode($row['user_name'])))."' title='{$main->lang['send_email']}'>{$main->lang['send_email']}</a> | <a href='".$main->url(array('module' => 'account', 'do' => 'message', 'id' => 'create', 'user' => urlencode($row['user_name'])))."' title='{$main->lang['send_pm']}'>{$main->lang['send_pm']}</a> ]</div></td></tr>".
            "</table>";
            close();
            if($userconf['ratings']==ENABLED AND !is_ajax()) $tpl_create->add2script("rating({$row['uid']}, 'users', {$row['rating']}, {$row['voted']}, ".((is_guest() AND $userconf['guests_evaluate']!=ENABLED) ? "0" : "1").");", false);
            if($userconf['comments']==ENABLED) comments('', $row['uid'], $row['user_id'], $userconf['guests_comments'], $userconf['comments_sort'], true, $msg, 'user');
        } else info($main->lang['noinfo']);
    }
}

function information_accounf($msg=''){
    information($msg);
}

function new_password($msg=""){
global $main, $captcha_set;
    if(is_user()) redirect(MODULE);
    $captcha_set = true;
    echo navi(array(), false, false, $main->lang['sendnewpassword']);
    if(!empty($msg)) warning($msg); 
    if(!isset($_GET['id'])) info($main->lang['sendnewpassword_info']);
    if(!isset($_GET['id'])){
        open();
        echo "<form action='".$main->url(array('module' => 'account', 'do' => 'send_new_password'))."' method='post'>".
        "<table class='form'>".
        "<tr class='row_tr'><td class='form_text'>{$main->lang['mail']}</td><td class='form_input'>".in_text('email_check', 'input_text')."</td></tr>".
        "<tr class='row_tr'><td class='form_text'>{$main->lang['login']}</td><td class='form_input'>".in_text('login_check', 'input_text')."</td></tr>".
        captcha().
        "<tr><td colspan='2' align='center'><br />".send_button()."</td></tr>".
        "</table>".
        "</form>";
        close();
    } else {
        $result = $main->db->sql_query("SELECT user_name, user_email, user_activation_code FROM ".USERS." WHERE user_activation_code='{$_GET['id']}'");        
        if($main->db->sql_numrows($result)>0){
            open();
            echo "<form action='".$main->url(array('module' => 'account', 'do' => 'save_new_password', 'id' => $_GET['id']))."' method='post'>".
            "<table class='form'>".
            "<tr class='row_tr'><td class='form_text'>{$main->lang['you_newpassword']}:</td><td class='form_input'>".in_pass("user_newpassword", "input_password_accaunt")."</td></tr>\n".
            "<tr class='row_tr'><td class='form_text'>{$main->lang['you_renewpassword']}:</td><td class='form_input'>".in_pass("user_renewpassword", "input_password_accaunt")."</td></tr>\n".
            captcha().
            "<tr><td class='form_submit' colspan='2' align='center'>".send_button()."</td></tr>\n".
            "</table>".in_hide("key", $_GET['id']).
            "</form>";
            close();
        } else meta_refresh("5", $main->url(array('module' => $main->module)), $main->lang['nosearchkey']);
    }    
}

function save_new_password(){
global $main, $captcha_set, $patterns;
    if(is_user() OR $_POST['key']!=$_GET['id']) redirect(MODULE);
    $captcha_set = true;
    $msg = ($_POST['user_newpassword']==$_POST['user_renewpassword'] AND !empty($_POST['user_renewpassword'])) ? "" : $main->lang['error_new_password'];
    $msg .= check_captcha();    
    if(empty($msg)){
        require_once "modules/{$main->module}/classes/system.php";
        if(kr_file_exists("modules/{$main->module}/classes/{$main->config['compatibility']}.php")) {
            require_once "modules/{$main->module}/classes/{$main->config['compatibility']}.php";
            $account = new account;
        } else $account = new account_system;
        $account->new_pawssword();
    } else new_password($msg);
}

function send_new_password(){
global $main, $captcha_set, $patterns;
    if(is_user()) redirect(MODULE);
    $captcha_set = true;
    $msg = (empty($_POST['email_check']) AND empty($_POST['login_check'])) ? $main->lang['no_parametrs'] : "";
    $msg .= check_captcha();
    if(!empty($_POST['login_check'])){
        $chk = $main->db->sql_query("SELECT user_name, user_email FROM ".USERS." WHERE user_name='".addslashes($_POST['login_check'])."'");
        $msg .= ($main->db->sql_numrows($chk)==0) ? $main->lang['no_login_search'] : "";
    }
    if(!empty($_POST['email_check'])){
        $chk = $main->db->sql_query("SELECT user_name, user_email FROM ".USERS." WHERE user_email='".addslashes($_POST['email_check'])."'");
        $msg .= ($main->db->sql_numrows($chk)==0) ? $main->lang['no_email_search'] : "";
    }
    if(empty($msg)){
        $info = $main->db->sql_fetchrow($chk);
        $random_string = get_random_string(25);
        sql_update(array('user_activation_code' => $random_string), USERS, "user_name='{$info['user_name']}'");
        $ms = preg_replace(
            array(
                '/\{USER\}/is',
                '/\{SITE\}/is',
                '/\{CODE\}/is',
            ),
            array(
                $info['user_name'],
                "<a href='{$main->config['http_home_url']}'>{$main->config['home_title']}</a>",
                "<a href='{$main->config['http_home_url']}index.php?module=account&amp;do=new_password&amp;id={$random_string}'>{$main->config['http_home_url']}index.php?module=account&amp;do=new_password&amp;id={$random_string}</a>"                
            ),
            $patterns['new_password']
        );
        send_mail($info['user_email'], $info['user_name'], $main->config['sends_mail'], 'noreply', $main->lang['change_password'].' @ '.$main->config['site_name_for_mail'], $ms);
        meta_refresh(3, $main->url(array('module' => 'account')), $main->lang['send_mail_instruct']);
    } else new_password($msg);
}


function show_smiles(){
global $smiles, $parametr_design;
    $parametr_design = false;
    $list = "<table width='100%'>";
    $i=0;
    foreach($smiles as $arr){
        $img = "<td align='center' width='39' height='39'><img onclick=\"$$('setsmile').value=' ".magic_quotes($arr[0])." '\" style='cursor: pointer;' src='{$arr[1]}' alt='".htmlspecialchars($arr[0], ENT_QUOTES)."' title='".htmlspecialchars($arr[0], ENT_QUOTES)."' /></td>";
        if($i==0) $list .= "<tr>{$img}";
        elseif($i==6) {$list .= "{$img}</tr>"; $i=-1;}
        else $list .= $img;
        $i++;
    }
   $list .= ($i<=6) ? "</tr></table>" : "</table>";
   echo $list."<input type='hidden' id='setsmile' name='setsmile' vlaue=''>";
}

function user_email($msg=""){
global $main;
    if(!is_user()) redirect(MODULE);
    if(!empty($msg)) warning($msg);
    $result = $main->db->sql_query("SELECT * FROM ".USERS." WHERE user_name='".addslashes($_GET['id'])."'");
    if($main->db->sql_numrows($result)>0){
        $info = $main->db->sql_fetchrow($result);
        open();
        echo "<form id='autocomplete' style='margin: 3px;' method='post' action='".$main->url(array('module' => $main->module, 'do' => 'send_mail', 'id' => $_GET['id']))."'>\n".
        "<table class='form' align='center' width='100%' id='form_{$main->module}'>\n".
        "<tr class='row_tr'><td class='form_text'>{$main->lang['recipient']}:</td><td class='form_input'><b>{$info['user_name']}</b></td></tr>\n".
        "<tr class='row_tr'><td class='form_text'>{$main->lang['subj']}:<span class='star'>*</span></td><td class='form_input'>".in_text("subj", "input_text2")."</td></tr>\n".
        "<tr class='row_tr'><td class='form_text'>{$main->lang['message']}:<span class='star'>*</span></td><td class='form_input'>".editor("message", 9, "97%")."</td></tr>\n".
        captcha()."<tr><td class='form_submit' colspan='2' align='center'>".send_button()."</td></tr>\n".
        "</table>";
        close();
    } else redirect(MODULE);
}

function send_user_email(){
    global $main;
    if(!is_user()) redirect(MODULE);
    $result = $main->db->sql_query("SELECT * FROM ".USERS." WHERE user_name='".addslashes($_GET['id'])."'");
    if($main->db->sql_numrows($result)>0){
        $info = $main->db->sql_fetchrow($result);
        filter_arr(array('subj', 'message'), POST, TAGS);
        $msg = error_empty(array('subj', 'message'), array('subj_err', 'message_err')).check_captcha();
        if(empty($msg)){
            send_mail($info['user_email'], $info['user_name'], $main->user['user_email'], $main->user['user_name'], $_POST['subj'], parse_bb(bb($_POST['message']))."\n\r");
            meta_refresh(3, $main->url(array('module' => $main->module, 'do' => 'user', 'id' => $info['user_id'])), $main->lang['user_email_sends']);
        } else user_email($msg);
    } else redirect(MODULE); 
}

function usercheck(){
global $main,$userconf;
    $_POST['user'] = kr_filter($_POST['user'], TAGS);
    if(!empty($_POST['user'])){
       if(cyr2lat($_POST['user'])!=""){
          if(!array_key_exists('user_name_length',$userconf)||(array_key_exists('user_name_length',$userconf)&&($userconf['user_name_length']!="")&&(kr_strlen($_POST['user'])>intval($userconf['user_name_length'])))){
             $result = $main->db->sql_query("SELECT * FROM ".USERS." WHERE user_name='{$_POST['user']}' OR user_id='".cyr2lat($_POST['user'])."' LIMIT 1");
             if($main->db->sql_numrows($result)>0) echo $main->lang['checked_user'];
             else echo $main->lang['nochecked_user'];
          } else echo preg_replace('%(?i)<li[^>]*>([^<{]*)(\{COUNT\})*([^<{]*)</li>%s', '<span style="color:red">$1 '.$userconf['user_name_length'].'$3</span>', $main->lang['error_length_uname']);
       } else echo preg_replace('%(?i)<li[^>]*>([^<{]*)(\{COUNT\})*([^<{]*)</li>%s', '<span style="color:red">$1</span>', $main->lang['error_uname_cyr2lat']);
    } else echo $main->lang['checked_empty'];
    exit;
}

function seatch_plugin(){
global $main;
    if(isset($_GET['do']) AND !empty($_GET['do'])){
        $links = scan_dir("modules/{$main->module}/links/", "/(.*)\.php/");
        $file_search = $_GET['do'].'.php';
        if(kr_file_exists("modules/{$main->module}/links/{$file_search}") AND kr_file_exists("modules/{$main->module}/plugin/{$file_search}")){
            if(!isset($_POST['showmenu']) OR (isset($_POST['showmenu']) AND $_POST['showmenu']=='1')) main(false);
            require_once "modules/{$main->module}/plugin/{$file_search}"; 
        } else kr_http_ereor_logs("404");
    } else kr_http_ereor_logs("404");
}

if(isset($_GET['do'])){
    switch ($_GET['do']){
        case "login": login(); break;
        case "sign": sign(); break;
        case "logout": logout(); break;
        case "new_user": new_user(); break;
        case "registration": registration(); break;
        case "controls": controls(); break;
        case "case_avatar": case_avatar(); break;
        case "save_controls": save_controls(); break;
        case "activation": activation(); break;
        case "user": information(); break;
        case "new_password": new_password(); break;
        case "send_new_password": send_new_password(); break;
        case "save_new_password": save_new_password(); break;
        case "smiles": show_smiles(); break;
        case "mail": user_email(); break;
        case "send_mail": send_user_email(); break;
        case "usercheck": usercheck(); break;
        case "userinfo":require_once "modules/{$main->module}/userinfo.php"; break;
        default: seatch_plugin(); break;
    }
} else main();
?>