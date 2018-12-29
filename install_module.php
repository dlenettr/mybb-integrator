<?php
/*
=============================================
 Name      : MyBB Integrator v1.4.4
 Author    : Mehmet Hanoğlu ( MaRZoCHi )
 Site      : http://dle.net.tr/
 License   : MIT License
=============================================
*/

if( ! defined( 'E_DEPRECATED' ) ) {
	@error_reporting ( E_ALL ^ E_NOTICE );
	@ini_set ( 'error_reporting', E_ALL ^ E_NOTICE );
} else {
	@error_reporting ( E_ALL ^ E_DEPRECATED ^ E_NOTICE );
	@ini_set ( 'error_reporting', E_ALL ^ E_DEPRECATED ^ E_NOTICE );
}

define ( 'DATALIFEENGINE', true );
define ( 'ROOT_DIR', dirname ( __FILE__ ) );
define ( 'ENGINE_DIR', ROOT_DIR . '/engine' );
define ( 'LANG_DIR', ROOT_DIR . '/language/' );

require_once ENGINE_DIR . "/data/config.php";
require_once ENGINE_DIR . "/inc/include/functions.inc.php";
require_once ROOT_DIR   . "/language/".$config['langs']."/adminpanel.lng";
require_once ROOT_DIR   . "/language/".$config['langs']."/mybb.lng";
require_once ENGINE_DIR . "/classes/mysql.php";
require_once ENGINE_DIR . "/data/dbconfig.php";
require_once ENGINE_DIR . "/classes/install.class.php";
require_once ENGINE_DIR . "/api/api.class.php";

@header( "Content-type: text/html; charset=" . $config['charset'] );

$Turkish = array ( 'm01' => "Kuruluma Başla", 'm02' => "Yükle", 'm03' => "Kaldır", 'm04' => "Yapımcı", 'm05' => "Çıkış Tarihi", 'm08' => "Kurulum Tamamlandı", 'm10' => "dosyasını silerek kurulumu bitirebilirsiniz", 'm11' => "Modül Kaldırıldı", 'm21' => "Kuruluma başlamadan önce olası hatalara karşı veritabanınızı yedekleyin", 'm22' => "Eğer herşeyin tamam olduğuna eminseniz", 'm23' => "butonuna basabilirsiniz.", 'm24' => "Güncelle", 'm25' => "Site", 'm26' => "Çeviri" );
$English = array ( 'm01' => "Start Installation", 'm02' => "Install", 'm03' => "Uninstall", 'm04' => "Author", 'm05' => "Release Date", 'm06' => "Module Page", 'm07' => "Support Forum", 'm08' => "Installation Finished", 'm10' => "delete this file to finish installation", 'm11' => "Module Uninstalled", 'm21' => "Back up your database before starting the installation for possible errors", 'm22' => "If you are sure that everything is okay, ", 'm23' => "click button.", 'm24' => "Upgrade", 'm25' => "Site", 'm26' => "Translation" );
$Russian = array ( 'm01' => "Начало установки", 'm02' => "Установить", 'm03' => "Удалить", 'm04' => "Автор", 'm05' => "Дата выпуска", 'm06' => "Страница модуля", 'm07' => "Форум поддержки", 'm08' => "Установка завершена", 'm10' => "удалите этот фаля для окончания установки", 'm11' => "Модуль удален", 'm21' => "Сделайте резервное копирование базы данных для избежания возможных ошибок", 'm22' => "Если вы уверены что всё впорядке, ", 'm23' => "нажмите кнопку.", 'm24' => "обновлять", 'm25' => "сайт", 'm26' => "перевод" );
$lang = array_merge( $lang, ${$config['langs']} );

function mainTable_head( $title ) {
	echo <<< HTML
	<div class="box">
		<div class="box-header">
			<div class="title"><div class="box-nav"><font size="2">{$title}</font></div></div>
		</div>
		<div class="box-content">
			<table class="table table-normal">
HTML;
}

function mainTable_foot() {
	echo <<< HTML
			</table>
		</div>
	</div>
HTML;
}


$module = array(
	'name'		=> "MWS MyBB Integrator v1.4.4",
	'date'		=> "31.05.2017",
	'ifile'		=> "install_module.php",
	'link'		=> "http://dle.net.tr",
	'image'		=> "http://img.dle.net.tr/mws/mybb_integrator.png",
	'author_n'	=> "Mehmet Hanoğlu (MaRZoCHi)",
	'author_s'	=> "http://mehmethanoglu.com.tr",
);


if ( $is_logged && $member_id['user_group'] == "1" ) {

	echoheader("<i class=\"icon-comments\"></i>" . $module['name'], $lang['m01'] );

	if ($_REQUEST['action'] == "install") {
		if ( ! isset( $_POST['mybb_ver'] ) ) $_POST['mybb_ver'] = "16";
		$mybb_ver = intval( $_POST['mybb_ver'] );

		$dle_api->install_admin_module("mybb-forum", "MyBB Integrator", $lang['mws_install_inf'], "mybb-forum.png", "1");

		$mod = new VQEdit();
		$mod->backup = True;
		$mod->bootup( $path = ROOT_DIR, $logging = True );
		if ( $mybb_ver == "16" ) {
			$mod->file( ROOT_DIR. "/install/xml/mybb-integrator16.xml" );
		} else if ( $mybb_ver == "18" ) {
			$mod->file( ROOT_DIR. "/install/xml/mybb-integrator18.xml" );
		} else if ( $mybb_ver == "189" ) {
			$mod->file( ROOT_DIR. "/install/xml/mybb-integrator189.xml" );
		}
		$mod->close();
		mainTable_head($lang['mws_fd_install']);
		$stat_info = str_replace("install.php", "install_module.php", $lang['stat_install']);
		echo <<< HTML
	<table width="100%">
		<tr>
			<td width="210" align="center" valign="middle" style="padding:4px;">
				<img src="{$module['image']}" alt="" />
			</td>
			<td style="padding-left:20px;padding-top: 4px;" valign="top">
				<b><a href="{$module['link']}">{$module['name']}</a></b><br /><br />
				<b>{$lang['m04']}</b> : <a href="{$module['author_s']}">{$module['author_n']}</a><br />{$translation}
				<b>{$lang['m05']}</b> : <font color="#555555">{$module['date']}</font><br />
				<b>{$lang['m25']}</b> : <a href="{$module['link']}">{$module['link']}</a><br />
				<br /><br />
				<b><font color="#BF0000">{$module['ifile']}</font> {$lang['m10']}</b><br />
			</td>
		</tr>
	</table>
HTML;
		mainTable_foot();
	} else {
		mainTable_head($lang['mws_s_install']);
		echo <<< HTML
	<table width="100%">
		<tr>
			<td width="210" align="center" valign="middle" style="padding:4px;">
				<img src="{$module['image']}" alt="" /><br /><br />
			</td>
			<td style="padding-left:20px;padding-top: 4px;" valign="top">
				<b><a href="{$module['link']}">{$module['name']}</a></b><br /><br />
				<b>{$lang['m04']}</b> : <a href="{$module['author_s']}">{$module['author_n']}</a><br />{$translation}
				<b>{$lang['m05']}</b> : <font color="#555555">{$module['date']}</font><br />
				<b>{$lang['m25']}</b> : <a href="{$module['link']}">{$module['link']}</a><br />
				<br /><br />
				<b><font color="#BF0000">{$lang['m01']} ...</font></b><br /><br />
				<b>*</b> {$lang['m21']}<br />
				<b>*</b> {$lang['m22']} <font color="#51A351"><b>{$lang['m02']}</b></font> {$lang['m23']}<br />
			</td>
		</tr>
		<tr>
			<td width="150" align="left" style="padding:4px;"></td>
			<td colspan="2" style="padding:4px;" align="right">
				<form method="post" action="{$PHP_SELF}">
					<select name="mybb_ver" class="uniform">
						<option value="16">MyBB 1.6.x</option>
						<option value="18">MyBB 1.8</option>
						<option value="189">MyBB 1.8.9+</option>
					</select>&nbsp;&nbsp;
					<input type="hidden" value="install" name="action" />
					<input type="submit" value="{$lang['m02']}" class="btn btn-green" />
				</form>
			</td>
		</tr>
	</table>
HTML;
		mainTable_foot();
	}
	echofooter();
} else {
	msg("home", $lang['mws_noauth'], $lang['mws_noauth_text'], $config["http_home_url"]);
}
?>