<?php
/**
* Файл инициализации системы
* 
* @author Igor Ognichenko
* @copyright Copyright (c)2007-2010 by Kasseler CMS
* @link http://www.kasseler-cms.net/
* @filesource includes/function/init.php
* @version 2.0
*/
if(!defined("KASSELERCMS") AND !defined("ADMIN_FILE")) die("Access is limited");

define("BLOCK_FILE", true);
define("FUNC_FILE", true);

define('SAFE_MODE', ini_get('safe_mode')=='1'?true:false);
define('ISWIN', preg_match('/win/i', PHP_OS)?true:false);

//session_cache_limiter("private");
if(isset($_POST['PHPSESSID'])) session_id($_POST['PHPSESSID']);
session_start();
  
if(function_exists('date_default_timezone_set')) date_default_timezone_set("Europe/London");

function load_includes($pattern, $path){
    $files=array();
    if(($tmpHandle = kr_opendir($path))){
        while(false !== ($tmpFile = readdir($tmpHandle))) if(!kr_is_dir($path.$tmpFile) AND preg_match("#$pattern#i",$tmpFile)) $files[]=$path.$tmpFile;
        closedir($tmpHandle);
    }
    return $files;
}

global $config, $is_mb_string, $is_config_utf, $database, $cross_db, $revision, $template, $tpl_main;
require_once 'includes/config/config.php';
require_once 'includes/config/configdb.php';

$is_config_utf = preg_match('/(.*?)utf(.*?)/i', $config['charset']) ? true : false;
$is_mb_string = function_exists('mb_strpos') ? true : false;
$is_mb_string_used = ($is_mb_string==true AND $is_config_utf==true) ? true : false;
header("Content-type: text/html; charset={$config['charset']}");
require_once "includes/function/xining.php";
require_once "includes/function/debugging.php";
//Подключение файлов конфигурации 
foreach(load_includes('config_.*?\.php', 'includes/config/') as $filename) require_once $filename;
require_once "includes/function/ipblock.php";
//Подключение файлов define
foreach(load_includes('.*?\.php', 'includes/define/') as $filename) require_once $filename;

global $database;
$adminfile = $config['adminfile'];
$magic_quotes = true;
$dbg_status = true;
$redirect = "";
$parse_ref = (isset($_SERVER['HTTP_REFERER']) AND !empty($_SERVER['HTTP_REFERER'])) ? parse_url($_SERVER['HTTP_REFERER']) : array();

//Подключение основных классов системы
if(kr_file_exists("includes/classes/{$database['type']}.class.php") AND (!defined("INSTALLCMS")||defined("UPDATECMS"))) require_once "includes/classes/{$database['type']}.class.php";
require_once "includes/classes/main.class.php";

$main->init_class('templates', 'session', 'autoload');
$template = new template();

//Подключение функций системы
$main->init_function('templates', 'hooks', 'functions', 'replace', 'gets', 'bool', 'kernel', 'forms');

foreach(load_includes('.*?\.php', 'hooks/autoload/') as $filename) require_once $filename;
//other_function
if(is_ajax()) $main->init_function('additional');
if(!function_exists('glob')) $main->init_function('glob');

function remove_port($host_value){
    $out = "";
    if(preg_match("/([^:]+)/", $host_value, $out)) return $out[1];
    return $host_value; 
}

if($is_mb_string) mb_internal_encoding($config['charset']);                //Устанавливаем кодировку функций MB

if(function_exists('get_magic_quotes_gpc')){                                                       //Проверяем наличие функции
    if(!get_magic_quotes_gpc()){                                                                   //Проверяем включен ли magic_quotes 
        function mq($value){                                                                       //Создаем функция экранирования
            if(is_array($value)) $value = array_map('mq', $value);
            elseif(!empty($value) AND is_string($value)) $value = addslashes($value);
            return $value;
        }
        $_GET     = isset($_GET) ? mq($_GET) : array();                                  //Экранируем $_GET масив
        $_POST    = isset($_POST) ? mq($_POST) : array();                                //Экранируем $_POST масив
        $_COOKIE  = isset($_COOKIE) ? mq($_COOKIE) : array();                            //Экранируем $_COOKIE масив
        $_REQUEST = isset($_REQUEST) ? mq($_REQUEST) : array();                          //Экранируем $_REQUEST масив
    }
}

global $lang, $db, $main, $language, $base; 
$httphost = remove_port(get_env('HTTP_HOST'));
if(!empty($parse_ref)) $parse_host=remove_port($parse_ref['host']);
if($config['filrer_referer']==ENABLED AND isset($_POST) AND count($_POST)>0 AND !empty($parse_ref) AND $httphost!=$parse_host) redirect(get_env('HTTP_REFERER')); 
//if($config['filrer_referer']==ENABLED AND isset($_POST) AND count($_POST)>0 AND !empty($parse_ref) AND $httphost!=$parse_host AND $parse_host!='ulogin.ru') redirect(get_env('HTTP_REFERER')); 
load_tpl();
$geterate_time = new timer;
$version_sys = "2";
$revision = '809';
$license_sys = "FREE";

$modules_sitemap = array(
    'news'     => NEWS,
    'files'    => FILES,
    'media'    => MEDIA,
    'pages'    => PAGES,
    'shop'     => SHOP,
    'jokes'    => '',
    'top_site' => '',
    'faq'      => '',
    'voting'   => '',
    'content'  => ''  
);

$copyright_file = "<?php\n/**********************************************/\n/* Kasseler CMS: Content Management System    */\n/**********************************************/\n/*                                            */\n/* Copyright (c)2007-2010 by Igor Ognichenko  */\n/* http://www.kasseler-cms.net/               */\n/*                                            */\n/**********************************************/\n\nif (!defined('FUNC_FILE')) die('Access is limited');\n\n";
$cache_clear_ignore = false;

//Проверка и создание директорий для временных файлов
if(!kr_file_exists("uploads/cache/")) kr_mkdir("uploads/cache/", 0777);
if(!kr_file_exists("uploads/tmpfiles/")) kr_mkdir("uploads/tmpfiles/", 0777);
  
@ini_set('url_rewriter.tags', '');
@ini_set('arg_separator.output', '&amp;');
@ini_set('register_globals', 'off');
@ini_set('display_errors', true);
@ini_set('html_errors', false);
@ini_set('error_reporting', E_ALL ^ E_NOTICE);
@ini_set("safe_mode", false);

if(isset($_REQUEST['GLOBALS']) || isset($_FILES['GLOBALS']) || isset($_SERVER['GLOBALS']) || isset($_COOKIE['GLOBALS']) || isset($_ENV['GLOBALS'])) die('GLOBALS overwrite attempt');
if(count($_REQUEST) > 1000) die('possible exploit');
foreach ($GLOBALS as $key => $dummy) if(is_numeric($key)) die('numeric key detected');

if(is_ajax()){
    foreach($_POST as $key => $dummy) if(!empty($_POST[$key]) AND !is_int($_POST[$key]) AND !is_array($_POST[$key])) $_POST[$key] = utf8decode($dummy);
}

//Отключение register_globals on
if (ini_get('register_globals') == 1) foreach($_REQUEST as $key => $var) unset($GLOBALS[$key]);

foreach (array('PHP_SELF', 'HTTP_ACCEPT_LANGUAGE', 'HTTP_AUTHORIZATION') as $current) {
    if(get_env($current) AND false === kr_strpos(get_env($current), '<')) $$current = get_env($current);
    elseif(!isset($$current) OR false !== kr_strpos($$current, '<')) $$current = '';  // очистка XSS
}
unset($current);
if($main->mod_rewrite AND isset($_GET['mod_rewrite'])) $main->parse_rewrite();

$_arr_home_modules = explode(',', $config['default_module']);
$module_name = (isset($_GET['module'])) ? $_GET['module'] : $_arr_home_modules[0];
if(isset($_GET['id']) AND !preg_match('/[a-zа-я_\-0-9\.]+/is', $_GET['id'])) kr_http_ereor_logs("403");
$session = new session($config['interval_session_update']);
if(isset($_GET['http_error'])) kr_http_ereor_logs($_GET['http_error']);
//Определение языкового файла
get_language();

if($config['disable_site']==ENABLED AND !is_support() AND !defined("ADMIN_FILE")){
    die(stripslashes($config['disable_description']));
}
$main->init();
$template = new template();

define("USER_FOLDER", "filedata-".$main->user['user_folder']);
load_tpl();
//Подключение графических элементов систем
(isset($main->tpl) AND kr_file_exists("templates/{$main->tpl}/{$language}/images.php")) ? require_once "templates/{$main->tpl}/{$language}/images.php" : require_once "includes/language/{$language}/images.php";
if(!defined("ENGINE") AND !isset($_GET['blockfile'])){
    if(!is_ajax()){
        if(!defined("ADMIN_FILE")) $template->get_tpl('index', 'index');
        else {
            if(!defined("INSTALLCMS")){
                if(isset($_SESSION['admin'])) {
                    $template->path = 'templates/admin/';
                    $template->get_tpl('index', 'index');
                } else {
                    $template->path = 'templates/admin/';
                    $template->get_tpl('login', 'index');
                }
            } else {
                $template->path = 'install/template/';
                $template->get_tpl('index', 'index');
            }
        }
    }
    if(!isset($_GET['ajaxed'])){
        if(!defined("ADMIN_FILE")){
            $main->init_class('usertpl');
            $tpl_create = new user_tpl;
        } else {
            $main->init_class('admintpl');
            $tpl_create = new admin_tpl;
        }
        if(kr_file_exists($main->config['licence_file'])){
            $li = "";
            $match1 = ""; $lic_file = array();
            $lic_content = kr_get_content($main->config['licence_file']);
            $key_lic = array('number', 'version', 'type', 'expiration', 'domains', 'copyright');
            preg_match_all('/<number>(.*?)<\/number>|<version>(.*?)<\/version>|<type>(.*?)<\/type>|<expiration>(.*?)<\/expiration>|<domains>(.*?)<\/domains>|<copyright>(.*?)<\/copyright>/is', $lic_content, $match1);
            unset($match1[0]);
            foreach($match1 as $key => $value) foreach($match1[$key] as $k => $v) if(!empty($v)) $lic_file[$key_lic[$k]] = $v;
            foreach($lic_file as $key => $value) $li .= "<{$key}>{$value}</{$key}>\n";
            if(!defined("ADMIN_FILE") AND !is_ajax() AND !preg_match('/\$license/s', $template->template['index']) AND $lic_file['copyright']!='false') error_page($lang['system_error'], "#001: ".$lang['error_license']);
            if($license_sys==$lic_file['type']){
                preg_match('/<key>(.*?)<\/key>/is', $lic_content, $match2);
                $lic_key_check = $_BEE(md5($_BEE($li)).$lic_file['number']);
                if($match2[1]==$lic_key_check){
                    if($lic_file['type']!='FREE' AND $license_sys!='FREE'){
                        if(count($lic_file)==count($key_lic)){
                            $_arr_d = explode(',', $lic_file['domains']);
                            if(!in_array(get_env('SERVER_NAME'), $_arr_d)) error_page($lang['system_error'], "#002: ".$lang['error_license_file']);
                        } else error_page($lang['error'], "#003: ".$lang['error_license_parsing']);
                    }
                } else error_page($lang['system_error'], "#004: ".$lang['error_license_key']);
            } else error_page($lang['system_error'], "#005: ".$lang['error_license_typekey']);
        } else error_page($lang['system_error'], "#006: ".$lang['error_file_license']);
        $tpl_create->add2script("includes/language/{$language}/lang.js");
        $tpl_create->tpl_creates();
    } else $tpl_create = new tpl_create;
    if(!is_ajax() AND !defined("INSTALLCMS")) $template->set_tpl(array('time' => preg_replace(array("#{query}#", "#{query_time}#", "#{time}#"), array($db->num_queries, $db->total_time_db, $geterate_time->stop()), $lang['page_generate'])), 'index');
} elseif(defined("ENGINE")) {
    $main->init_class('usertpl');
    $tpl_create = new user_tpl;
}
?>