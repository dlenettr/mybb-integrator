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

if ($mws_mmybb['1_onoff']) {

	if ( isset($uid) && isset($loc) ) {
		global $mybb;
		require_once ENGINE_DIR . '/classes/mybb.class.php';
		$points = $mybb->get_reputations($member_id['user_id'], $plain = True);
		echo "<span class=\"mwsrep minus\" onclick=\"MWSRep('{$uid}', 'm', '{$loc}');\">&#045;</span>&nbsp;<span class=\"mwsrep tab\" id=\"t_{$uid}\">{$points}</span>&nbsp;<span class=\"mwsrep plus\" onclick=\"MWSRep('{$uid}', 'p', '{$loc}');\">&#043;</span>";
	} else {
		echo "";
	}
} else {
	echo "";
}
?>