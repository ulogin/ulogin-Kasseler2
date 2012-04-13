<?php
function hook_user_sign(){
    global $main, $userconf;
    if (!function_exists('file_get_contents')) return;
    if (isset($_POST['token'])){
        
        $user_data = file_get_contents('http://ulogin.ru/token.php?token=' . $_POST['token'] . '&host=' . urlencode($_SERVER['HTTP_HOST']));
        $user_data = json_decode($user_data, true);
        echo $user_data;
        if (isset($user_data['error'])) return;
        $group_number = get_ulogin_group();
        if (!$group_number){
            $result=$main->db->sql_query("INSERT INTO ".GROUPS." (title, description, img, color, points, special) VALUES ('uLogin', 'uLogin', 'ulogin.png', '80ff00', 5, 0)");
            $group_number = get_ulogin_group();
        }
        $result = $main->db->sql_query("SELECT user_name, user_website, user_email FROM ".USERS." WHERE user_group = ".$group_number." and user_website ='".$user_data['identity']."'");
        if($main->db->sql_numrows($result) > 0){
            $user = $main->db->sql_fetchrow($result);
            $email_parts = explode('@', $user['user_email']);
            $identity_parts = parse_url($user['user_website']);
            $_POST['user_name'] = $user['user_name'];
            $_POST['user_password'] =  md5($email_parts[0].'z'.$identity_parts['path'].'Q'.$user_data['network'].'c1'.$user_data['uid']);
        } else {
            $user_pass_len = 10;
            if (isset($userconf['password_length']) && !empty($userconf['password_length'])){
                $user_pass_len = $userconf['password_length'];
            }
            
            if (isset($userconf['user_name_length']) && !empty($userconf['user_name_length']))
                $user_name = isset($user_data['nickname']) ? substr($user_data['nickname'], 0, $userconf['user_name_length']) : substr($user_data['first_name'].'_'.$user_data['last_name'], 0, $userconf['user_name_length']);
            else
                $user_name = isset($user_data['nickname']) ? $user_data['nickname'] : $user_data['first_name'];
            $email_parts = explode('@', $user_data['email']);
            $identity_parts = parse_url($user_data['identity']);
            $user_password = md5($email_parts[0].'z'.$identity_parts['path'].'Q'.$user_data['network'].'c1'.$user_data['uid']);
            $bdate = explode('.', $user_data['bdate']);
            $user_data['sex'] = $user_data['sex'] == '2' ? 1 : 2;
            $result = $main->db->sql_query("SELECT uid FROM ".USERS." WHERE user_name='{$user_name}' OR user_id='".cyr2lat($user_name)."'");
            while($main->db->sql_numrows($result) > 0){
                if (isset($userconf['user_name_length']) && !empty($userconf['user_name_length']))
                    $user_name = isset($user_data['nickname']) ? substr($user_data['nickname'].'_'.time(), 0, $userconf['user_name_length']) : substr($user_data['first_name'].'_'.$user_data['last_name'].'_'.time(), 0, $userconf['user_name_length']);
                else
                    $user_name = isset($user_data['nickname']) ? $user_data['nickname'].'_'.time() : $user_data['first_name'].'_'.time();
                $result = $main->db->sql_query("SELECT uid FROM ".USERS." WHERE user_name='{$user_name}' OR user_id='".cyr2lat($user_name)."'");
            }
            $main->db->sql_query("INSERT INTO ".USERS." (user_id, user_name, user_email, user_website,user_password, user_birthday, user_gender,user_avatar,user_regdate,user_last_visit,user_group,user_password_update)
                                  VALUES ('".cyr2lat($user_name)."', '".$user_name."', '".$user_data['email']."','".$user_data['identity']."','".pass_crypt($user_password)."','".$bdate[2].'-'.$bdate[1].'-'.$bdate[0]."',".$user_data['sex'].
                                  ",'".$user_data['photo']."','".kr_date("Y-m-d H:i:s")."','".kr_date("Y-m-d H:i:s")."',".get_ulogin_group().",".time().")");
            $_POST['user_name'] = $user_name;
            $_POST['user_password'] = $user_password;
        }
    }
}

function hook_user_saveprofile(){
    global $main;
    if (isset($_POST['user_website']) && $main->user['user_group'] == get_ulogin_group()){
        $_POST['user_website'] = $main->user['user_website'];
    }
}

function hook_user_info($msg=''){
    global $main, $img, $userconf, $tpl_create, $config,$template;
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
            $avatar = $row['user_group'] == get_ulogin_group() ?$row['user_avatar'] : 'uploads/avatars/'.$row['user_avatar'];
            echo "<table width='100%' class='table' id='table_{$main->module}'>".
            "<tr><td width='160' valign='top' class='user_info'>".
            "<div align='center'><h3>".get_flag($row['user_country'])."{$row['user_name']}</h3><img src='{$avatar}' alt='{$row['user_name']}' /></div><hr />".
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

function hook_user_controls($msg=""){
    global $userconf, $main;
    main(false);
    $avatar = $main->user['user_group'] == get_ulogin_group() ? $main->user['user_avatar'] : $userconf['directory_avatar'].$main->user['user_avatar'];
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
            "<tr class='row_tr'><td class='form_text'>{$main->lang['you_avatar']}:</td><td class='form_input_account' align='center'><input type='hidden' id='id_set_avatar' name='set_avatar' value='' /><img id='avatar' class='img_avatar' src='{$avatar}' alt='{$main->lang['you_avatar']}' title='{$main->lang['you_avatar']}' /></td></tr>\n".
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

function get_ulogin_group(){
    global $main;
    $group_number = 0;
    $result = $main->db->sql_query("SELECT id FROM ".GROUPS." WHERE title = 'uLogin'");
    if($main->db->sql_numrows($result) > 0)
    $group_number = $main->db->sql_fetchrow($result);
    return $group_number['id'];
}

hook_register('sign', 'hook_user_sign');
hook_register('save_controls', 'hook_user_saveprofile');
hook_register('information', 'hook_user_info');
hook_register('controls', 'hook_user_controls');
?>
