<?php
/*
=====================================================
 Modül Adı : MyBB Integrator v1.4.3
 Yapımcı   : Mehmet Hanoğlu ( MaRZoCHi )
 Site      : http://dle.net.tr/ (c) 2015
 Tarih     : 21.06.2015
 Lisans    : GNU License
=====================================================
*/

@error_reporting ( E_ALL ^ E_WARNING ^ E_NOTICE );
@ini_set ( 'display_errors', true );
@ini_set ( 'html_errors', true );
@ini_set ( 'error_reporting', E_ALL ^ E_WARNING ^ E_NOTICE );

define( 'DATALIFEENGINE', true);
define( 'ROOT_DIR', substr( dirname(  __FILE__ ), 0, -12 ) );
define( 'ENGINE_DIR', ROOT_DIR . '/engine' );

global $mws_mmybb, $mybb;

require_once ENGINE_DIR . '/data/config.php';
require_once ENGINE_DIR . '/modules/functions.php';
require_once ENGINE_DIR . '/classes/mysql.php';					// Need for sitelogin.php
require_once ENGINE_DIR . '/data/dbconfig.php';
require_once ENGINE_DIR . '/classes/mybb.class.php';			// MyBB Class
require_once ENGINE_DIR . '/data/mybb_modules.conf.php';		// MyBB Module Configs.

dle_session();

require_once ENGINE_DIR . '/modules/sitelogin.php';

if ( $config['version_id'] >= "10.3" ) {
	date_default_timezone_set( $config['date_adjust'] );
}

if ($config['http_home_url'] == "") {
	$config['http_home_url'] = explode("engine/ajax/mybb.rep.php", $_SERVER['PHP_SELF']);
	$config['http_home_url'] = reset($config['http_home_url']);
	$config['http_home_url'] = "http://".$_SERVER['HTTP_HOST'].$config['http_home_url'];
}

@header("Content-type: text/html; charset=".$config['charset']);

if ( $is_logged ) {

	require_once ROOT_DIR . '/language/' . $config['langs'] . '/mybb.rep.lng';
	$_agr = unserialize(str_replace("'", '"', $mws_mmybb['1_agrps']));

	// Kullanıcı grubu izni
	if ( in_array( $member_id['user_group'], array_values($_agr) ) ) {

		if ( isset($_POST['uid']) && isset($_POST['action']) && isset($_POST['text']) && isset($_POST['local']) ) {

			$_tid = $mybb->mdb->safesql( $_POST['uid'] );
			$_gid = $mybb->mdb->safesql( $member_id['user_id'] );
			$_msg = $mybb->mdb->safesql( $_POST['text'] );
			$_ref = $mybb->mdb->safesql( $_POST['local'] );
			$_rep = ( $mybb->mdb->safesql( $_POST['action'] ) == 'm' ) ? $mybb->sett['neurep'] : $mybb->sett['posrep'];
			$_dte = time() + ( $config['date_adjust'] * 60 );
			unset( $_POST['uid'], $_POST['action'], $_POST['text'], $_POST['local']);

			$stop = "";

			// Mesaj karakteri sınırlaması
			$_msg = (dle_strlen($_msg, $config['charset']) > intval($mws_mmybb['1_txtlmt'])) ? dle_substr( $_msg, 0, intval($mws_mmybb['1_txtlmt']), $config['charset'] )."..." : $_msg;

			// Günlük REP verme sınırı
			if ( intval( $mws_mmybb['1_replmt'] ) != 0 ) {
				$cont = $mybb->mdb->super_query("SELECT COUNT(pid) as count FROM {$mybb->prefix}reputation WHERE FROM_UNIXTIME(dateline) > NOW() - INTERVAL 1 DAY;");
				if ( ( intval( $cont['count'] ) + 1 > intval( $mws_mmybb['1_replmt'] ) ) ) $stop .= "<li>".$messages['6']." (" . $cont['count'] . ")</li>";
			}

			// Aynı sayfada max. rep verme
			$cont = $mybb->mdb->super_query("SELECT COUNT(pid) as count FROM {$mybb->prefix}reputation WHERE adduid = '{$_gid}'");
			if ( ( intval( $cont['pid'] ) > 0 ) && ( $mws_mmybb['1_multrep'] == "0" ) ) $stop .= "<li>".$messages['5']."</li>";

			// Kendine rep verme
			if ( ( intval( $_gid ) == intval( $_tid ) ) && ( $mws_mmybb['1_gownr'] == "0" ) ) $stop .= "<li>".$messages['4']."</li>";

			if ( empty( $stop ) ) {
				$mybb->mdb->query("
					INSERT INTO {$mybb->prefix}reputation (uid, adduid, pid, reputation, dateline, comments)
					VALUES ('{$_tid}', '{$_gid}', '{$_ref}', '{$_rep}', '{$_dte}', '{$_msg}')
				");
				$mybb->mdb->free();
				unset($_tid, $_gid, $_msg, $_rep, $_ref, $_dte, $cont);
				echo "ok";
			} else {
				echo $messages['3']."<br /><ul>".$stop."<ul>";
			}
		} else {
			echo $messages['2'];
		}
	} else {
		echo $messages['1'];
	}
	unset( $_agr );
} else {
	echo $messages['0'];
}

?>