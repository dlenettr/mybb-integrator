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

define( 'DATALIFEENGINE', true );
define( 'ROOT_DIR', ".." );
define( 'ENGINE_DIR', "../engine" );

@ob_start ();
@ob_implicit_flush ( 0 );
if ( !defined( 'E_DEPRECATED' ) ) {
	@error_reporting ( E_ALL ^ E_WARNING ^ E_NOTICE );
	@ini_set ( 'error_reporting', E_ALL ^ E_WARNING ^ E_NOTICE );
} else {
	@error_reporting ( E_ALL ^ E_WARNING ^ E_DEPRECATED ^ E_NOTICE );
	@ini_set ( 'error_reporting', E_ALL ^ E_WARNING ^ E_DEPRECATED ^ E_NOTICE );
}

@ini_set ( 'display_errors', true );
@ini_set ( 'html_errors', false );

include ENGINE_DIR . "/data/config.php";
include ENGINE_DIR . '/classes/mysql.php';
include ENGINE_DIR . '/data/dbconfig.php';
require_once ENGINE_DIR . '/modules/functions.php';
require_once ENGINE_DIR . "/modules/sitelogin.php";
require_once ENGINE_DIR . "/data/mybb.conf.php";

header( "Location: {$mws_mybb['forum_url']}" );

?>