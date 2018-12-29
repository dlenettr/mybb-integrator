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

if (!defined('DATALIFEENGINE')) {
	die("Hacking attempt!");
}

global $mws_mmybb, $mybb;

require_once ENGINE_DIR . '/classes/mybb.class.php';
require_once ENGINE_DIR . '/data/mybb_modules.conf.php';


if ( dle_cache("mybb-info", $config['skin']) ) {
	$mybb_info = dle_cache("mybb-info", $config['skin']);
} else {
	$data = $mybb->mdb->super_query("
		SELECT numusers, numthreads, numposts
		FROM {$mybb->prefix}stats
		"
	);
	$tpl->load_template('mws-mybb-info.tpl');

	$tpl->set( '{forum}', $mybb->conf['forum_url'] );
	$tpl->set( '{users}', $data['numusers'] );
	$tpl->set( '{threads}', $data['numthreads'] );
	$tpl->set( '{posts}', $data['numposts'] );
	$tpl->set( '{only-posts}', intval($data['numposts']) - intval($data['numthreads']) );
	$tpl->compile('mws-mybb-info.tpl');

	$mybb->mdb->free();
	unset($data);

	$mybb_info = $tpl->result['mws-mybb-info.tpl'];
	if ( $config['allow_cache'] ) {
	//if ( ($mws_mmybb['1_ucache'] == "on") || ($config['allow_cache'] == "yes" && $mws_mmybb['1_ucache'] == "auto") ) {
		create_cache( "mybb-info", $mybb_info, $config['skin'] );
	}
	$tpl->clear();
}

echo $mybb_info;
?>