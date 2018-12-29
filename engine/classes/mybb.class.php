<?php
/*
=============================================
 Name      : MyBB Integrator v1.4.4
 Author    : Mehmet Hanoğlu ( MaRZoCHi )
 Site      : http://dle.net.tr/
 License   : MIT License
=============================================
*/

if( !defined( 'E_DEPRECATED' ) ) {
	@error_reporting ( E_ALL ^ E_NOTICE );
	@ini_set ( 'error_reporting', E_ALL ^ E_NOTICE );
} else {
	@error_reporting ( E_ALL ^ E_DEPRECATED ^ E_NOTICE );
	@ini_set ( 'error_reporting', E_ALL ^ E_DEPRECATED ^ E_NOTICE );
}
@ini_set ( 'display_errors', true );
@ini_set ( 'html_errors', false );

define ( 'IN_MYBB', true );

abstract class MyBBMainFunctions {

	function secure_seed_rng($count = 8) {
		$output = '';
		if(@is_readable('/dev/urandom') && ($handle = @fopen('/dev/urandom', 'rb'))) {
			$output = @fread($handle, $count);
			@fclose($handle);
		}
		if(strlen($output) < $count) {
			$output = '';
			$unique_state = microtime().@getmypid();
			for($i = 0; $i < $count; $i += 16) {
				$unique_state = md5(microtime().$unique_state);
				$output .= pack('H*', md5($unique_state));
			}
		}
		$output = hexdec(substr(dechex(crc32(base64_encode($output))), 0, $count));
		return $output;
	}


	function my_rand($min=null, $max=null, $force_seed=false) {
		static $seeded = false;
		static $obfuscator = 0;
		if($seeded == false || $force_seed == true) {
			mt_srand($this->secure_seed_rng());
			$seeded = true;
			$obfuscator = abs((int) $this->secure_seed_rng());
			if($obfuscator > mt_getrandmax()) {
				$obfuscator -= mt_getrandmax();
			}
		}
		if($min !== null && $max !== null) {
			$distance = $max - $min;
			if ($distance > 0) {
				return $min + (int)((float)($distance + 1) * (float)(mt_rand() ^ $obfuscator) / (mt_getrandmax() + 1));
			} else {
				return mt_rand($min, $max);
			}
		} else {
			$val = mt_rand() ^ $obfuscator;
			return $val;
		}
	}


	function random_str($length="8") {
		$set = array("a","A","b","B","c","C","d","D","e","E","f","F","g","G","h","H","i","I","j","J","k","K","l","L","m","M","n","N","o","O","p","P","q","Q","r","R","s","S","t","T","u","U","v","V","w","W","x","X","y","Y","z","Z","1","2","3","4","5","6","7","8","9");
		$str = '';
		for($i = 1; $i <= $length; ++$i) {
			$ch = $this->my_rand(0, count($set)-1);
			$str .= $set[$ch];
		}
		return $str;
	}


	function set_cookie($name, $value, $expire) {
		header('P3P: CP="CUR ADM"');
		setcookie($this->sett['cookieprefix'].$name, $value, $expire, $this->sett['cookiepath'], $this->sett['cookiedomain'], isset($_SERVER["HTTPS"]), true);
		$_SESSION[$name] = (empty($value)) ? "" : $value;
	}


	function salt_password($password, $salt) {
		return md5(md5($salt).$password);
	}


	function generate_salt() {
		return $this->random_str(8);
	}


	function generate_loginkey() {
		return $this->random_str(50);
	}
}



class MyBBIntegrator extends MyBBMainFunctions {
	public $prefix = Null;
	public $mdb = Null;
	public $conf = Null;
	public $sett = Null;
	public $mybb_ver = "1.8.5";
	public $dle_ver = "10.5";


	public function __construct() {
		include_once( ENGINE_DIR . "/classes/mysql.php" );
		include_once( ENGINE_DIR . "/data/mybb.conf.php" );
		$this->conf = &$mws_mybb;
		$this->conf['expire'] = time() + (86400 * intval($this->conf['cookie_time']));
		$this->conf['default_user'] = unserialize( str_replace( "'", '"', $this->conf['default_user'] ) );
		$this->conf['default_topic'] = unserialize( str_replace( "'", '"', $this->conf['default_topic'] ) );
		include_once( ROOT_DIR . $this->conf['forum_dir']."/inc/config.php" );
		include_once( ROOT_DIR . $this->conf['forum_dir']."/inc/settings.php" );
		$this->sett = &$settings;
		$this->prefix = $config['database']['table_prefix'];
		$this->mdb = new db();
		$this->mdb->connect($config['database']['username'], $config['database']['password'], $config['database']['database'], $config['database']['hostname'] = 'localhost', $show_error = 1);
	}


	/*
	 * Convert php array type to sql insert format
	 * @param array $array :
	 *
	 * @return array : Included two indexs 0 and 1
	 *		0 : Table column names
	 *		1 : Insert values
	*/
	public function _arrayToInsertSQL($array, $nbyte = true) {
		$columns = array();
		$data = array();
		$array = array_filter( $array );
		foreach ( $array as $key => $value) {
			$columns[] = $key;
			if ($nbyte) {
				$data[] = ($value != "") ? "\"" . $value . "\"" : "NULL";
			} else {
				if (is_int($value)) {
					$data[] = intval($value);
				} else {
					$data[] = ($value != "") ? "\"" . $value . "\"" : "\"\"";
				}
			}
		}
		return array(implode(",",$columns), implode(",",$data));
	}


	/*
	 * Convert php array type to sql update format
	 * @param array $array :
	 *
	 * @return string : set key = value
	*/
	public function _arrayToUpdateSQL($array, $nbyte = true) {
		$sql = "";
		$array = array_filter( $array );
		foreach ( $array as $key => $value) {
			if ($nbyte) {
				$value = ($value != "") ? "\"" . $value . "\"" : "NULL";
			} else {
				if (is_int($value)) {
					$value = intval($value);
				} else {
					$value = ($value != "") ? "\"" . $value . "\"" : "\"\"";
				}
			}
			$sql .= "{$key} = {$value}, ";
		}
		return substr($sql, 0, -2);
	}


	/*
	 * Get real category id from category sync.
	 * @param integer $fid :
	 *
	 * @return integer :
	*/
	private function _getRealForum($fid) {
		$name = "forumid_".$fid;
		if ( isset($this->conf[$name]) ) {
			return intval($this->conf[$name]);
		}
		unset($name, $fid);
	}


	/*
	 * Get real group id from group sync.
	 * @param integer $groupid :
	 *
	 * @return integer :
	*/
	private function _getRealGroup($gid) {
		$name = "groupid_".strval($gid);
		if ( isset($this->conf[$name]) ) {
			return intval($this->conf[$name]);
		}
		unset($name, $gid);
	}


	/*
	 * Convert DLE BB Code into MyBB
	 * @param string $text :
	 *
	 * @return string :
	*/
	public function _BBConvert($text) {
		$find = array(
			'[center]','[/center]','[left]','[/left]','[right]','[/right]','[thumb]','[/thumb]','[img=left]','[img=right]','[img=none]','[code]','[/code]','{PAGEBREAK}','[ol=','[/ol]',
		);
		$replace = array(
			'[align=center]','[/align]','[align=left]','[/align]','[align=right]','[/align]','[img]','[/img]','[img]','[img]','[img]','[php]','[/php]','\r\n','[list=','[/list]',
		);

		if (True) { // Apply manuel replacement texts ?
			$_lines = explode("\n", $this->conf['repl_text']);
			foreach ($_lines as $_line) {
				$_tmp = explode("=>", $_line);
				$text = str_replace($_tmp[0], $_tmp[1], $text);
			}
			unset($_lines, $_tmp);
		}

		if (! $this->conf['use_mwshc']) {
			$text = preg_replace(array('[hide]', '[\hide]'), array('', ''), $text);
		}

		if(function_exists("str_ireplace")) {
			$text = str_ireplace($find, $replace, $text);
		} else {
			$text = str_replace($find, $replace, $text);
		}

		$find = array(
			'#\[img\=(.*?)\|(.*?)\](.*?)\[/img\]#i','#\[leech\=(.*?)](.*?)\[/leech\]#i','#\[url\=\"(.*?)\"](.*?)\[/url\]#i','#\[url\=(.*?)](.*?)\[/url\]#i','#\[video\=(.*?)]#i','#\[audio\=(.*?)]#i','#\[media\=(.*?)]#i','#\[flash\=(.*?)](.*?)\[/flash\]#i','#\[spoiler\=(.*?)](.*?)\[/spoiler\]#i',
		);
		$replace = array(
			'[align=$1][img]$3[/img][/align]','[url=$1]$2[/url]','[url=$1]$2[/url]','[url=$1]$2[/url]','[url=$1]Video[/url]','[url=$1]Audio[/url]','[video=youtube]$1[/video]','[url=$2]Flash[/url]','$2',
		);
		$text = preg_replace($find, $replace, $text);
		unset($replace, $find);
		return $text;
	}


	/*
	 * Is cookied ?
	 * @param string $name
	 *
	 * @return bool :
	*/
	private function is_cookie($name) {
		$name = $this->conf['cookie_prefix'].$name;
		return (isset($_COOKIE[$name]));
	}


	/*
	 * Read created cookie content
	 * @param string $name
	 *
	 * @return string :
	*/
	private function read_cookie($name) {
		$name = $this->conf['cookie_prefix'].$name;
		return $_COOKIE[$name];
	}


	/*
	 * Login into forum with user name or user id values
	 * @param string $user_name :
	 * @param integer $user_id :
	 * @param float $time :
	*/
	public function login($user_name = False, $user_id = False, $time = False) {
		if ( !$time ) $time = time();
		if ($user_name) {
			$userq = $this->mdb->super_query("SELECT uid, loginkey, usergroup, lastactive FROM {$this->prefix}users WHERE username = '{$user_name}'");
			$user_id = $userq['uid'];
		}
		if ($user_id) {
			$user_id = intval($user_id);
			$userq = $this->mdb->super_query("SELECT loginkey, usergroup, lastactive FROM {$this->prefix}users WHERE uid = {$user_id}");
		}
		$user_ip = $this->mdb->safesql( $_SERVER['REMOTE_ADDR'] );
		if ($user_name || $user_id) {
			$this->mdb->query("DELETE FROM {$this->prefix}sessions WHERE uid = '{$user_id}' AND ip = '{$user_ip}'");
			$session_id = $this->random_str(32);
			$sql_array = array (
				'sid'		=> $session_id,
				'uid'		=> intval($user_id),
				'time'		=> $time,
				'ip'		=> $user_ip,
				'useragent'	=> $this->mdb->safesql($_SERVER['HTTP_USER_AGENT']),
			);
			$data = $this->_arrayToInsertSQL($sql_array, $nbyte = false);
			$this->mdb->query("
				INSERT INTO {$this->prefix}sessions ({$data[0]})
				VALUES ({$data[1]})"
			);
			unset($sql_array, $data);
			$this->mdb->free();
			if (!$this->is_cookie("mybbuser")) {
				$this->set_cookie("mybbuser", $user_id."_".$userq['loginkey'], $this->conf['expire']);
				$this->set_cookie("sid", $session_id, $this->conf['expire']);
				$this->set_cookie("mybb[lastvisit]", $time, $this->conf['expire']);
				$this->set_cookie("mybb[lastactive]", $userq['lastactive'], $this->conf['expire']);
				$this->set_cookie("loginattempts", "1", $this->conf['expire']);
			}
			if ($this->conf['login_admin'] && (intval($userq['usergroup']) == 4) ) {
				$this->mdb->query("DELETE FROM {$this->prefix}adminsessions WHERE uid = {$user_id}");
				$admin_sid = md5(uniqid(microtime()));
				$admin_session = array(
					"sid"			=> $admin_sid,
					"uid"			=> $user_id,
					"loginkey"		=> $userq['loginkey'],
					"ip"			=> $user_ip,
					"dateline"		=> $time,
					"lastactive"	=> $time,
				);
				$data = $this->_arrayToInsertSQL($admin_session);
				$this->mdb->query("
					INSERT INTO {$this->prefix}adminsessions ({$data[0]})
					VALUES ({$data[1]})"
				);
				$this->mdb->query("
					UPDATE {$this->prefix}adminoptions
					SET loginattempts = 0, loginlockoutexpiry = 0
					WHERE uid = {$user_id}"
				);
				$this->mdb->free();
				$this->set_cookie("adminsid", $admin_sid, $this->conf['expire']);
				$this->set_cookie("acploginattempts", "0", $this->conf['expire']);
				unset($data, $admin_session, $admin_sid, $session_id, $userq, $user_ip, $user_name, $user_id);
			}
		}
	}


	/*
	 * Logout from forum with user id number
	 * @param integer $user_id :
	 * @param string $user_ip :
	*/
	public function logout($user_id, $user_ip = "") {
		$user_ip = ( empty( $user_ip ) ) ? $this->mdb->safesql( $_SERVER['REMOTE_ADDR'] ) : $this->mdb->safesql( $user_ip );
		$this->mdb->query("DELETE FROM {$this->prefix}sessions WHERE uid = '{$user_id}' AND ip = '{$user_ip}'");
		$this->set_cookie("mybbuser", "", $this->conf['expire']);
		$this->set_cookie("sid", "", $this->conf['expire']);
		$this->set_cookie("mybb[lastvisit]", "", $this->conf['expire']);
		$this->set_cookie("mybb[lastactive]", "", $this->conf['expire']);
		$this->set_cookie("loginattempts", "", $this->conf['expire']);

		if ($this->conf['login_admin']) {
			$sess = $this->mdb->super_query("SELECT sid FROM {$this->prefix}adminsessions WHERE uid = {$user_id}");
			if ( $this->read_cookie("adminsid") == $sess["sid"] ) {
				$this->mdb->query("DELETE FROM {$this->prefix}adminsessions WHERE uid = {$user_id}");
				$this->mdb->free();
				$this->set_cookie("adminsid", "", $this->conf['expire']);
				$this->set_cookie("acploginattempts", "", $this->conf['expire']);
				unset($sess);
			}
		}
		unset($user_id, $user_ip);
	}


	/*
	 * Is registered ?
	 * @param integer $user_id :
	 *
	 * @return bool :
	*/
	public function is_registered_user($user_id) {
		$result = $this->mdb->super_query("SELECT usergroup FROM {$this->prefix}users WHERE uid = {$user_id}");
		return ($result['usergroup'] != '') ? True : False;
	}


	/*
	 * Is user moderator ?
	 * @param integer $user_id :
	 *
	 * @return bool :
	*/
	public function is_moderator_user($user_id) {
		$result = $this->mdb->super_query("SELECT mid FROM {$this->prefix}moderators WHERE id = {$user_id}");
		return ($result) ? True : False;
	}


	/*
	 * Is user banned ?
	 * @param integer $user_id :
	 *
	 * @return bool :
	*/
	public function is_banned_user($user_id) {
		$result = $this->mdb->super_query("SELECT uid FROM {$this->prefix}banned WHERE uid = {$user_id}");
		return ($result) ? True : False;
	}


	/*
	 * Add new user
	 * @param array $sql :
	 * @param boolean $count :
	 * @param boolean $login :
	 * @param boolean $self :
	*/
	public function add_user($sql, $count = True, $login = True, $self = False) {
		$sql['usergroup'] = $this->_getRealGroup($sql['usergroup']);
		$sql['displaygroup'] = $sql['usergroup'];
		$sql['lastip'] = $sql['regip'];
		if (intval($sql['displaygroup']) == 0) {
			$sql['displaygroup'] = 2;
			$sql['usergroup'] = 2;
		}
		if (!$self) $sql['regpass'] = md5($sql['regpass']);
		$sql['salt'] = $this->generate_salt();
		$sql['loginkey'] = $this->generate_loginkey();
		$sql['password'] = $this->salt_password($sql['regpass'], $sql['salt']);
		unset($sql['regpass']);

		if ( isset( $sql['avatar'] ) ) {
			$sql['avatartype'] = "remote";
			if ( empty($this->conf['avatar_width']) || empty($this->conf['avatar_height']) ) $sql['avatardimensions'] = "100|100";
			else $sql['avatardimensions'] = $this->conf['avatar_width']."|".$this->conf['avatar_height'];
		}

		$sql['showsigs'] = '0';
		$sql['showredirect'] = '0';
		$sql['showquickreply'] = '0';
		$sql['showavatars'] = '0';
		for ($x = 0; $x < count($this->conf['default_user']); $x++) {
			$sql[$this->conf['default_user'][$x]] = '1';
		}

		if (! $this->is_registered_user( $sql['uid'] ) ) {
			$data = $this->_arrayToInsertSQL($sql, $nbyte = false);
			$this->mdb->query("
				INSERT INTO {$this->prefix}users ({$data[0]})
				VALUES ({$data[1]})"
			);
		} else {
			$this->update_user($sql, $sql['uid'], $self = True);
		}

		if ($this->conf['login_register'] && $login == True) {$this->login("", intval($sql['uid']) );}
		if ($count) {
			$cache = $this->read_cache($title = "stats");
			$cache['numusers'] = $cache['numusers'] + 1;
			$cache['lastuid'] = $sql['uid'];
			$cache['lastusername'] = $sql['username'];
			$this->update_cache($title = "stats", $cache, $read = False);
			unset($cache, $sql);
		}
		unset($sql, $data, $login, $count);
	}


	/*
	 * Update user informations
	 * @param array $sql :
	 * @param integer $user_id :
	 * @param boolean $self :
	 * @param boolean $admin :
	*/
	public function update_user($sql, $user_id, $self = False, $admin = False) {
		if ( $self == True ) {
			$this->mdb->query("
				UPDATE {$this->prefix}users
				SET ".$this->_arrayToUpdateSQL($sql, $nbyte = false)."
				WHERE uid = {$user_id}"
			);
		} else {
			$check = False;

			if ( isset( $sql['editpass'] ) && ( $sql['editpass'] != "" ) ) {
				$sql['editpass'] = md5($sql['editpass']);
				$sql['salt'] = $this->generate_salt();
				$sql['loginkey'] = $this->generate_loginkey();
				$sql['password'] = $this->salt_password($sql['editpass'], $sql['salt']);
				unset( $sql['editpass'] );
				$check = True;
			}

			if ( isset( $sql['avatar'] ) ) {
				$sql['avatartype'] = "remote";
				$sql['avatardimensions'] = $this->conf['avatar_width']."|".$this->conf['avatar_height'];
			}

			if ( isset( $sql['usergroup'] ) ) {
				if ( !empty( $sql['usergroup'] ) ) {
					$_group = $this->_getRealGroup($sql['usergroup']);
					$sql['usergroup'] = $_group;
					$sql['displaygroup'] = $_group;
				}
			}

			if ( count( $sql ) > 0 ) {
				$sql = array_filter( $sql, "empty" );
				$this->mdb->query("
					UPDATE {$this->prefix}users
					SET ".$this->_arrayToUpdateSQL($sql, $nbyte = false)."
					WHERE uid = {$user_id}"
				);
			}

			if ( $check AND !$admin ) {
				$this->logout( intval($user_id) );
				$this->login( "", intval($user_id) );
			}
		}
	}


	/*
	 * Delete user and all activities
	 * @param integer $user_id :
	*/
	public function delete_user($user_id) {
		@$this->logout($user_id);
		if ($this->conf['delete_posts']) {
			$this->mdb->query("DELETE FROM {$this->prefix}posts WHERE uid = {$user_id} AND replyto = 1");
		} else {
			$this->mdb->query("
				UPDATE {$this->prefix}posts
				SET uid = 0
				WHERE uid = {$user_id}"
			);
		}
		$this->mdb->query("DELETE FROM {$this->prefix}userfields WHERE ufid = {$user_id}");
		$this->mdb->query("DELETE FROM {$this->prefix}privatemessages WHERE uid = {$user_id}");
		$this->mdb->query("DELETE FROM {$this->prefix}events WHERE uid = {$user_id}");
		$this->mdb->query("DELETE FROM {$this->prefix}forumsubscriptions WHERE uid = {$user_id}");
		$this->mdb->query("DELETE FROM {$this->prefix}threadsubscriptions WHERE uid = {$user_id}");
		$this->mdb->query("DELETE FROM {$this->prefix}sessions WHERE uid = {$user_id}");
		$this->mdb->query("DELETE FROM {$this->prefix}banned WHERE uid = {$user_id}");
		$this->mdb->query("DELETE FROM {$this->prefix}threadratings WHERE uid = {$user_id}");
		$this->mdb->query("DELETE FROM {$this->prefix}users WHERE uid = {$user_id}");
		$this->mdb->query("DELETE FROM {$this->prefix}joinrequests WHERE uid = {$user_id}");
		$this->mdb->query("DELETE FROM {$this->prefix}warnings WHERE uid = {$user_id}");
		$this->mdb->query("DELETE FROM {$this->prefix}reputation WHERE uid = {$user_id}");
		$this->mdb->query("DELETE FROM {$this->prefix}awaitingactivation WHERE uid = {$user_id} OR aid = {$user_id}");
		if ( $this->is_moderator_user($user_id) ) $this->mdb->query("DELETE FROM {$this->prefix}moderators WHERE id = {$user_id}");
		$this->refresh_cache();
		$this->mdb->free();
	}


	/*
	 * Update user avatar
	 * @param string $avatar :
	 * @param integer $user_id :
	*/
	public function update_avatar($avatar, $user_id) {
		if ( empty($this->conf['avatar_width']) || empty($this->conf['avatar_height']) ) $dimensions = "100|100";
		else $dimensions = $this->conf['avatar_width']."|".$this->conf['avatar_height'];
		$avatar = $avatar . "?dateline=" . time();
		if ( $this->dle_ver < "9.7" ) $avatar = str_replace( "dleimages", "images", $avatar );
		$this->mdb->query("
			UPDATE {$this->prefix}users
			SET avatardimensions = '{$dimensions}', avatar = '{$avatar}', avatartype = 'remote'
			WHERE uid = {$user_id}"
		);
		$this->mdb->free();
		unset($avatar, $dimensions, $user_id);
	}


	/*
	 * Ban user with user id number
	 * @param array $sql_array :
	 * @param integer $user_id :
	*/
	public function add_banned_user($sql_array, $user_id) {
		$banned_gid = 7;		// MyBB Banned Usergroup ID
		if (!$this->is_banned_user($user_id)) {
			$sql_array['gid'] = $banned_gid;
			$sql_array['uid'] = $user_id;
			$data = $this->_arrayToInsertSQL($sql_array, $nbyte = false);
			$this->mdb->query("
				INSERT INTO {$this->prefix}banned ({$data[0]})
				VALUES ({$data[1]})"
			);unset($data);
		} else {
			$this->update_banned_user($sql_array, $user_id);
		}
		$this->mdb->query("
			UPDATE {$this->prefix}users
			SET ".$this->_arrayToUpdateSQL(
				array(
					'usergroup'		=> $banned_gid,
					'displaygroup'	=> $banned_gid
				)
			)."
			WHERE uid = {$user_id}"
		);
		$this->mdb->free();
	}


	/*
	 * Update banned user details with user id number
	 * @param array $sql_array :
	 * @param integer $user_id :
	*/
	public function update_banned_user($sql_array, $user_id) {
		$this->mdb->query("
			UPDATE {$this->prefix}banned
			SET ".$this->_arrayToUpdateSQL($sql_array)."
			WHERE uid = {$user_id}"
		);
	}


	/*
	 * Delete banned user with user id number
	 * @param integer $user_id :
	*/
	public function delete_banned_user($user_id) {
		if ($this->is_banned_user($user_id)) {
			$this->mdb->query("DELETE FROM {$this->prefix}banned WHERE uid = {$user_id}");
			$this->mdb->query("
				UPDATE {$this->prefix}users
				SET ".$this->_arrayToUpdateSQL(
					array(
						'usergroup' => $this->conf['groupid_4'],
						'displaygroup' => $this->conf['groupid_4']
					)
				)."
				WHERE uid = {$user_id}"
			);
		}
	}


	/*
	 * Is user banned ?
	 * @param integer $user_id :
	 *
	 * @return bool :
	*/
	public function is_real_forum($forum_id) {
		$result = $this->mdb->super_query("SELECT name FROM {$this->prefix}forums WHERE fid = {$forum_id} AND type = 'f'");
		return ($result) ? True : False;
	}


	/*
	 * Create new forum thread
	 * @param array $sql : Thread informations
	*/
	public function add_thread($sql) {
		$sql['forumid'] = $this->_getRealForum($sql['forumid']);
		if ( !empty($sql['forumid']) && $this->is_real_forum($sql['forumid']) ) {
			$postmessage = $this->_BBConvert($sql['post']);

			if ( !empty($this->conf['news_pre']) ) {
				$sql['subject'] = $this->conf['news_pre'] . " " . $sql['subject'];
			}

			$f_thread = array (
				'fid'			=> $sql['forumid'],
				'subject'		=> $sql['subject'],
				'uid'			=> $sql['user_id'],
				'lastposteruid'	=> $sql['user_id'],
				'dateline'		=> $sql['addtime'],
				'lastpost'		=> $sql['addtime'],
				'username'		=> $sql['user_name'],
				'lastposter'	=> $sql['user_name'],
				'views'			=> '0',
				'replies'		=> '0',
			);
			$f_post = array (
				'fid'			=> $sql['forumid'],
				'subject'		=> $sql['subject'],
				'uid'			=> $sql['user_id'],
				'username'		=> $sql['user_name'],
				'ipaddress'		=> $sql['ip'],
				'message'		=> $postmessage."\r\n".$this->conf['news_tag'],
				'dateline'		=> $sql['addtime'],
				'posthash'		=> md5($sql['user_id'].$this->random_str()),
				'edituid'		=> '0',
				'edittime'		=> '0',
				'includesig'	=> '0',
				'smilieoff'		=> '0',
				'replyto'		=> '0',
				'visible'		=> '0',
			);
			$f_forum = array (
				'lastpostsubject'	=> $sql['subject'],
				'lastposteruid'		=> $sql['user_id'],
				'lastposter'		=> $sql['user_name'],
				'lastpost'			=> time(),
			);

			// MyBB 1.8
			if ( $this->mybb_ver == "1.8" ) unset( $f_post['posthash'] );

			for ($x = 0; $x < count($this->conf['default_topic']); $x++) {
				$f_post[$this->conf['default_topic'][$x]] = '1';
			}
			$f_thread['visible'] = $f_post['visible'];
			$ptid = $this->mdb->super_query("SELECT MAX(p.pid) AS pid, MAX(t.tid) AS tid FROM {$this->prefix}posts as p, {$this->prefix}threads as t");
			$f_thread['firstpost'] = $f_post['pid'] = $ptid['pid'] + 1;
			$f_thread['tid'] = $f_post['tid'] = $f_forum['lastposttid'] = $ptid['tid'] + 1;
			$data = $this->_arrayToInsertSQL($f_post);
			$this->mdb->query("
				INSERT INTO {$this->prefix}posts ({$data[0]})
				VALUES ({$data[1]})"
			);unset($data);
			$data = $this->_arrayToInsertSQL($f_thread);
			$this->mdb->query("
				INSERT INTO {$this->prefix}threads ({$data[0]})
				VALUES ({$data[1]})"
			);unset($data);
			$this->mdb->query("
				UPDATE {$this->prefix}forums
				SET ".$this->_arrayToUpdateSQL($f_forum)." ,threads = threads+1, posts = posts+1
				WHERE fid = {$sql['forumid']}"
			);
			$cache = $this->read_cache($title = "stats");
			$cache['numthreads'] = $cache['numthreads']+1;
			$cache['numposts'] = $cache['numposts']+1;
			$this->update_cache($title = "stats", $cache, $read = False);
			unset($cache, $f_forum, $f_thread, $f_post, $ptid, $postmessage, $sql);
		}
	}


	/*
	 * Get user groups list
	 * @param string $name : HTML selection list name
	 * @param string $selected : Selected item name
	 *
	 * @return string : HTML selection list with optgroup param
	*/
	public function get_usergroups($name, $selected) {
		$this->mdb->query( "SELECT gid, title FROM {$this->prefix}usergroups" );
		$output = "<select class=\"uniform\" name=\"{$name}\">\r\n<option value=\"\">----------</option>\n";
		while ( $group = $this->mdb->get_row() ) {
			$select = (intval($selected) == intval($group['gid'])) ? " selected " : "";
			$output .= "<option value=\"{$group['gid']}\"{$select}>{$group['title']}</option>\n";
		}
		$output .= "</select>";
		return $output;
	}


	/*
	 * Get forum/category list
	 * @param string $name : HTML selection list name
	 * @param string $selected : Selected item name
	 * @param string $levelmark : Seperator for forum/category
	 *
	 * @return string : HTML selection list with optgroup param
	*/
	public function get_forums($name, $levelmark, $selected, $self = False, $sett = False) {
		if (True) {
			$cats = array();
			$forums = array();
			$subforum = array();
			$names = array();
			$this->mdb->query("
				SELECT fid, name, pid, type, parentlist
				FROM {$this->prefix}forums
				WHERE active != 0
				ORDER BY disporder ASC"
			);
			while ( $item = $this->mdb->get_row() ) {
				if ( $item['type'] == 'c' ) {
					$cats[$item['fid']] = $item['pid'];
				} else if ( $item['type'] == 'f' ) {
					if (count(explode(",",$item['parentlist'])) == 3) {
						$subforum[$item['fid']] = $item['pid'];
					} else {
						$forums[$item['fid']] = $item['pid'];
					}
				}
				$names[$item['fid']] = $item['name'];
			}
			if ($self) {
				if (isset($sett[0]) && isset($sett[1])) {
					$output = "<select size=\"{$sett[0]}\" style=\"width:{$sett[1]}px;\" name=\"{$name}[]\" multiple=\"multiple\">\r\n";
				} else {
					$output = "<select size=\"8\" style=\"width:340px;\" name=\"{$name}[]\" multiple=\"multiple\">\r\n";
				}
				$selected = unserialize(str_replace("'",'"',$selected));
				foreach($cats as $cat => $cpid) {
					$output .= "<optgroup label=\"{$names[$cat]}\">\n";
					foreach($forums as $forum => $catid) {
						if ($catid == $cat) {
							$select = "";
							for ($x = 0; $x <= count($selected); $x++) {
								if ($forum == intval($selected[$x])) {
									$select = " selected ";
									break;
								}
							}
							$output .= "<option value=\"{$forum}\"{$select}> {$names[$forum]}</option>\n";
							if (is_array($subforum)) {
								$sselect = "";
								foreach($subforum as $sforum => $sfid) {
									if ($sfid == $forum) {
										for ($x = 0; $x <= count($selected); $x++) {
											if ($sforum == intval($selected[$x])) {
												$sselect = " selected ";
												break;
											}
										}
										$output .= "<option value=\"{$sforum}\"{$sselect}> {$levelmark} {$names[$sforum]}</option>\n";
									}
								}
							}
						}
					}
					$output .= "</optgroup>\n";
				}
				$output .= "</select>";
			} else {
				$output = "<select name=\"{$name}\">\r\n<option value=\"\">-----------------</option>\n";
				foreach($cats as $cat => $cpid) {
					$output .= "<optgroup label=\"{$names[$cat]}\">\n";
					foreach($forums as $forum => $catid) {
						if ($catid == $cat) {
							$select = (intval($selected) == intval($forum)) ? " selected " : "";
							$output .= "<option value=\"{$forum}\"{$select}> {$names[$forum]}</option>\n";
							if (is_array($subforum)) {
								foreach($subforum as $sforum => $sfid) {
									if ($sfid == $forum) {
										$select = (intval($selected) == intval($sforum)) ? " selected " : "";
										$output .= "<option value=\"{$sforum}\"{$select}> {$levelmark} {$names[$sforum]}</option>\n";
									}
								}
							}
						}
					}
					$output .= "</optgroup>\n";
				}
				$output .= "</select>";
			}
		} else {}
		unset($cats, $forums, $name, $levelmark, $selected);
		return $output;
	}


	/*
	 * Get user reputations summary
	 * @param integer $user_id :
	 *
	 * @return string :
	*/
	public function get_reputations($user_id, $plain = False) {
		$rep = $this->mdb->super_query( "
			SELECT SUM(reputation) AS rcount
			FROM {$this->prefix}reputation
			WHERE uid = {$user_id}
		");
		if ($plain == True) {
			return (intval($rep['rcount']) == 0) ? "0" : $rep['rcount'];
		} else {
			return (intval($rep['rcount']) == 0) ? "0" : "<a href=\"{$this->conf['forum_url']}reputation.php?uid={$user_id}\" title=\"Rep\">{$rep['rcount']}</a>";
		}
	}


	/*
	 * Get user threads summary
	 * @param integer $user_id :
	 *
	 * @return string :
	*/
	public function get_threads($user_id) {
		$thread = $this->mdb->super_query( "
			SELECT COUNT(*) AS tcount
			FROM {$this->prefix}threads
			WHERE uid = {$user_id}
		");
		return (intval($thread['tcount']) == 0) ? "0" : "<a href=\"{$this->conf['forum_url']}search.php?action=finduserthreads&uid={$user_id}\" title=\"\">{$thread['tcount']}</a>";
	}


	/*
	 * Get user posts summary
	 * @param integer $user_id :
	 *
	 * @return string :
	*/
	public function get_posts($user_id) {
		$post = $this->mdb->super_query( "
			SELECT COUNT(*) AS pcount
			FROM {$this->prefix}posts
			WHERE uid = {$user_id}
		");
		return (intval($post['pcount']) == 0) ? "0" : "<a href=\"{$this->conf['forum_url']}search.php?action=finduser&uid={$user_id}\" title=\"\">{$post['pcount']}</a>";
	}


	/*
	 * Get primary and display group IDs
	 * @param integer $user_id :
	 *
	 * @return array :
	*/
	public function get_user_group($user_id) {
		$result = $this->mdb->super_query("SELECT usergroup,displaygroup FROM {$this->prefix}users WHERE uid = {$user_id}");
		if (is_array($result)) {
			return array($result['usergroup'], $result['displaygroup']);
		}
		unset($result);
	}


	/*
	 * Read datacache table
	 * @param string $title :
	 *
	 * @return array :
	*/
	private function read_cache($title = "") {
		if (!empty($title)) {
 			$data = $this->mdb->super_query("SELECT cache FROM {$this->prefix}datacache WHERE title = '{$title}'");
			return unserialize($data['cache']);
		}
	}


	/*
	 * Refresh datacache stats table
	 * @param bool $threads :
	 * @param bool $post :
	 * @param bool $user :
	*/
	public function refresh_cache($threads = True, $post = True, $user = True) {
		$data = array();
		if ($threads) {
			$thread = $this->mdb->super_query( "SELECT COUNT(tid) AS tcount, SUM(unapprovedposts) as ucount  FROM {$this->prefix}threads");
			$data['numthreads'] = $thread['tcount'];
			$data['numunapprovedthreads'] = $thread['ucount'];
			unset($thread);
		}
		if ($post) {
			$_temp = $this->mdb->super_query( "SELECT COUNT(*) AS pcount FROM {$this->prefix}posts");
			$data['numposts'] = $_temp['pcount'];
			unset($_temp);
		}
		if ($user) {
			$_temp = $this->mdb->super_query( "SELECT COUNT(*) AS ucount FROM {$this->prefix}users");
			$data['numusers'] = $_temp['ucount'];
			$last = $this->mdb->super_query( "SELECT uid, username FROM {$this->prefix}users ORDER BY regdate DESC LIMIT 0,1");
			$data['lastuid'] = $last['uid'];
			$data['lastusername'] = $last['username'];
			unset($last);
		}
		$this->update_cache($title = "stats", $data);
	}


	/*
	 * Update datacache table
	 * @param string $title :
	 * @param array $sql_array :
	 * @param bool $read :
	*/
	private function update_cache($title = "", $sql_array, $read = true) {
		if ( !empty( $title ) ) {
			if ($read == true) {
				$query_cc = $this->read_cache($title=$title);
				$uns_cache = array_merge($query_cc, $sql_array);
			} else {
				$uns_cache = $sql_array;
			}
			$this->mdb->query("
				UPDATE {$this->prefix}datacache
				SET cache = '".serialize($uns_cache)."'
				WHERE title = '{$title}'"
			);
			$this->mdb->free();
		}
	}


	/*
	 * Get total user count
	 *
	 * @return integer : Total user count
	*/
	public function get_total_user( ) {
		global $db;
		$_temp = $db->super_query( "SELECT COUNT(user_id) as total FROM ".PREFIX."_users");
		return $_temp['total'];
	}


	/*
	 * Get MyBB Settings
	 *
	 * @return JSON :
	*/
	public function get_mybb_settings( ) {
		$avatar = explode( "|", $this->sett['useravatardims'] );
		$result = array(
			"forum_url"			=> $this->sett['homeurl'],
			"cookiepath"		=> $this->sett['cookiepath'],
			"avatar"			=> array( "w" => $avatar[0], "h" => $avatar[0] ),
		);
		unset( $avatar );
		return json_encode( $result );
	}


	/*
	 * User conversion from DLE into MyBB
	*/
	public function user_conversion( $from = 0, $range = 0 ) {
		global $db;
		$count = $this->get_total_user();
		$range = !$range ? $count-$from : $range;
		$clause = ($from || $range) ? " LIMIT {$from},{$range}" : "";
		@$this->clean_session( );
		$db->query( "SELECT * FROM ".PREFIX."_users ORDER BY user_id{$clause}");
		while ( $user = $db->get_row() ) {
			if ( empty( $user['foto'] ) ) {
				$foto_arr = $this->conf['home_url']."templates/".$this->conf['skin']."/dleimages/noavatar.png";
			} else {
				// DLE version ?
				$foto_arr = $user['foto'];
			}
			$this->update_avatar($foto_arr, $user['user_id'], $self = True);
			unset($foto_arr);
			$sql = array(
				'uid'              	=> $user['user_id'],
				'username'			=> $user['name'],
				'regpass'			=> $user['password'],
				'email' 			=> $user['email'],
				'regdate' 			=> $user['reg_date'],
				'lastvisit' 		=> $user['lastdate'],
				'usergroup'		 	=> $user['user_group'],
				'regip' 			=> $user['logged_ip'],
			);
			$this->add_user($sql, $count = False, $login = False, $self = True);
			table_row($sql['regpass'], $sql['uid'], $sql['username'], $sql['email'], $sql['usergroup'], date("Y-m-d", $sql['regdate']), $sql['regip'] );
		}
		$this->refresh_cache($threads = False, $post = False, $user = True);
	}


	/*
	 * Clean all sessions
	*/
	public function clean_session( ) {
		$this->mdb->query("TRUNCATE TABLE {$this->prefix}sessions");
	}
}

$mybb = new MyBBIntegrator();