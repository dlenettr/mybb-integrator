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

if ($mws_mmybb['0_onoff']) {

	if ( dle_cache("mybb-stats", $config['skin']) ) {
		$mybb_stats = dle_cache("mybb-stats", $config['skin']);
	} else {
		$order = 't.lastpost';
		$by = 'DESC';
		$limit = $mws_mmybb['0_lastx'];
		$clause = "WHERE t.tid != 0";

		if ( !empty($mws_mmybb['0_exfid']) ) {
			$exfid = trim($mws_mmybb['0_exfid']);
			if ( substr($exfid, -1) == "," ) { $exfid = substr($exfid, 0, -1); }
			$clause .= " AND t.fid NOT IN ( {$exfid} )";
		}

		$forum_url = $mybb->conf['forum_url'];
		if (!empty( $limit) ) $limit = "LIMIT {$limit}";
		$mybb->mdb->query("
			SELECT t.tid, t.subject, t.lastposter, t.username, t.dateline, t.views, t.replies, t.visible, t.lastpost,
				   f.name, f.fid, f.lastpost as flastpost, f.lastposter as flastposter, f.lastposttid as flastposttid,
				   u.avatar, p.displaystyle as prefix
			FROM {$mybb->prefix}threads as t
			INNER JOIN {$mybb->prefix}users as u ON t.lastposteruid = u.uid
			INNER JOIN {$mybb->prefix}forums as f ON t.fid = f.fid
			LEFT OUTER JOIN {$mybb->prefix}threadprefixes as p ON p.pid = t.prefix
			{$clause}
			ORDER BY {$order} {$by}
			{$limit}"
		);

		$messages = "";
		$mcount = 0;

		while ( $row = $mybb->mdb->get_row() ) {
			$subject = (dle_strlen($row['subject'], $config['charset']) > intval($mws_mmybb['0_tlimit'])) ? dle_substr( $row['subject'], 0, $mws_mmybb['0_tlimit'], $config['charset'] )."..." : $row['subject'];
			$username = (dle_strlen($row['username'], $config['charset']) > intval($mws_mmybb['0_ulimit'])) ? dle_substr( $row['username'], 0, $mws_mmybb['0_ulimit'], $config['charset'] )."..." : $row['username'];
			$lastposter = (dle_strlen($row['lastposter'], $config['charset']) > intval($mws_mmybb['0_ulimit'])) ? dle_substr( $row['lastposter'], 0, $mws_mmybb['0_ulimit'], $config['charset'] )."..." : $row['lastposter'];
			if ( $config['charset'] != "utf8" AND function_exists('iconv') ) {
				$subject = iconv( "utf-8", $config['charset'], $subject );
				$username = iconv( "utf-8", $config['charset'], $username );
				$lastposter = iconv( "utf-8", $config['charset'], $lastposter );
				$row['subject'] = iconv( "utf-8", $config['charset'], $row['subject'] );
			}
			if (!$mws_mmybb['0_lastml']) {
				$link = ($mws_mmybb['0_mybbs']) ? $forum_url."thread-".$row['tid']."-lastpost.html" : $forum_url."showthread.php?tid=".$row['tid']."&action=lastpost";
			} else {
				$link = ($mws_mmybb['0_mybbs']) ? $forum_url."thread-".$row['tid'].".html" : $forum_url."showthread.php?tid=".$row['tid'];
			}
			$lastposter = ($mws_mmybb['0_showpp']) ? "<a onclick=\"ShowProfile('{$lastposter}', '/user/{$lastposter}/', '1'); return false;\" href=\"/user/{$lastposter}/\">{$lastposter}</a>" : "<a href=\"/user/{$lastposter}/\">{$lastposter}</a>";

			if ($mws_mmybb['0_sflink']) {
				$rel = ($mws_mmybb['0_nflwl']) ?  " rel=\"nofollow\"" : "";
				$forum = ($mws_mmybb['0_mybbs']) ? "<a href=\"{$forum_url}forum-{$row['fid']}.html\"{$rel}>{$row['name']}</a>" : "<a href=\"{$forum_url}forumdisplay.php?fid={$row['fid']}\"{$rel}>{$row['name']}</a>";
			} else {
				$forum = $row['name'];
			}

			$tpl->load_template('mws-mybb-row.tpl');

			$tpl->set('{f-id}', $row['fid']);
			$tpl->set('{f-lastpost}', langdate($mws_mmybb['0_datef'], $row['flastpost']) );
			$tpl->set('{f-lastposttid}', $row['flastposttid']);

			$tpl->set('{username}', $username);
			$tpl->set('{lastposter}', $lastposter);
			$tpl->set('{avatar}', $row['avatar']);
			$tpl->set('{forum}', $forum);
			$tpl->set('{replies}', intval($row['replies']));
			$tpl->set('{views}', intval($row['views']));
			$tpl->set('{date}', langdate($mws_mmybb['0_datef'], $row['lastpost']));

			$tpl->set('{a-href}', $link );
			$tpl->set('{a-title}', $lang['pm_from'] . " : " . $username . ", " . $row['subject']);
			$tpl->set('{a-date}', langdate($mws_mmybb['0_datef'], $row['dateline']) );
			if ($mws_mmybb['0_spref']) {
				$tpl->set('{subject}', $row['prefix'].$subject );
			} else {
				$tpl->set('{subject}', $subject );
			}

			$mcount++;
			$tpl->compile('mws-mybb-row.tpl');
			unset($subject, $username, $lastposter, $link, $forum);
		}

		$tpl->load_template('mws-mybb-table.tpl');
		$tpl->set( '{forum}', $forum_url );
		$tpl->set( '{limit}', $mcount . "/" . $mws_mmybb['0_lastx'] );
		$tpl->set( '{messages}', $tpl->result['mws-mybb-row.tpl'] );
		$tpl->compile('mws-mybb-table.tpl');

		$mybb->mdb->free();
		unset($forum_url, $mcount);

		$mybb_stats = $tpl->result['mws-mybb-table.tpl'];
		if ( ($mws_mmybb['0_ucache']) || ($config['allow_cache'] == "yes" && $mws_mmybb['0_ucache'] == "auto") ) {
			create_cache( "mybb-stats", $mybb_stats, $config['skin'] );
		}
		$tpl->clear();
	}

	echo $mybb_stats;
}
?>