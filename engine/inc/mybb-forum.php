<?php
/*
=============================================
 Name      : MyBB Integrator v1.4.4
 Author    : Mehmet Hanoğlu ( MaRZoCHi )
 Site      : http://dle.net.tr/
 License   : MIT License
=============================================
*/

if ( !defined('DATALIFEENGINE') OR !defined('LOGGED_IN') ) {
    die("Hacking attempt!");
}

global $mybb_db, $fail;
$conf_file  = ENGINE_DIR ."/data/mybb.conf.php";
$mconf_file = ENGINE_DIR ."/data/mybb_modules.conf.php";
require_once ($mconf_file);
require_once ENGINE_DIR . "/data/config.php";
require_once ENGINE_DIR . "/classes/mysql.php";
require_once ENGINE_DIR . "/data/dbconfig.php";
require_once ENGINE_DIR . "/classes/mybb.class.php";
$MNAME = "mybb-forum";


include_once ROOT_DIR . "/language/" . $config['langs'] . "/mybb.lng";

$mws_mybb = &$mybb->conf;

function isInteger( $input ) { return( ctype_digit( strval( $input ) ) ); }

if (!is_writable($conf_file)) {
    $lang['stat_system'] = str_replace("{file}", "engine/data/mybb.conf.php", $lang['stat_system']);
    $fail .= $lang['stat_system'] . "<br />";
}
if (!is_writable($mconf_file)) {
    $lang['stat_system'] = str_replace("{file}", "engine/data/mybb_modules.conf.php", $lang['stat_system']);
    $fail .= $lang['stat_system'] . "<br />";
}

if ($_REQUEST['action'] == "save") {
    if ($member_id['user_group'] != 1) {msg("error", $lang['opt_denied'], $lang['opt_denied']);}
    if ($_REQUEST['user_hash'] == "" or $_REQUEST['user_hash'] != $dle_login_hash) {die("Tamamlanamadı");}
    $save_con = $_POST['save'];
    $save_con['delete_posts'] = intval( $save_con['delete_posts'] );
    $save_con['use_mwshc'] = intval( $save_con['use_mwshc'] );
    $handler = fopen($conf_file, "w");
    fwrite($handler, "<?php \n\$mws_mybb = array (\n");
    foreach($save_con as $name => $value) {
        $value = (is_array($value)) ? str_replace('"',"'", serialize($value)) : $value;
        if ( isInteger( $value ) ) $value = intval( $value );
        fwrite($handler, "\t'{$name}' => \"{$value}\",\n");
    }
    fwrite($handler, "\t'home_url' => \"{$config['http_home_url']}\",\n");
    fwrite($handler, "\t'skin' => \"{$config['skin']}\",\n");
    fwrite($handler, ");\n?>");
    fclose($handler);
    clear_cache();
    msg("info", "<a href=\"{$PHP_SELF}?mod={$MNAME}\">DLE-MyBB Integrator v{$lang['mws_mybbint_vid']} - {$lang['mws_home_page']}</a>", "{$lang['opt_sysok_1']}<br /><br /><a href=\"{$PHP_SELF}?mod={$MNAME}&action=settings\">{$lang['db_prev']}</a>");
}

if ($_REQUEST['action'] == "save-module") {
    if ($member_id['user_group'] != 1) {msg("error", $lang['opt_denied'], $lang['opt_denied']);}
    if ($_REQUEST['user_hash'] == "" or $_REQUEST['user_hash'] != $dle_login_hash) {die("Tamamlanamadı");}
    $save_con = $_POST['savem'];
    $save_con['1_multrep'] = intval( $save_con['1_multrep'] );
    $handler = fopen($mconf_file, "w");
    fwrite($handler, "<?php \n\$mws_mmybb = array (\n");
    foreach($save_con as $name => $value) {
        $value = (is_array($value)) ? str_replace('"',"'", serialize($value)) : $value;
        if ( isInteger( $value ) ) $value = intval( $value );
        fwrite($handler, "\t'{$name}' => \"{$value}\",\n");
    }
    fwrite($handler, ");\n?>");
    fclose($handler);
    clear_cache();
    msg("info", "<a href=\"{$PHP_SELF}?mod={$MNAME}\">DLE-MyBB Integrator v{$lang['mws_mybbint_vid']} - {$lang['mws_home_page']}</a>", "{$lang['opt_sysok_1']}<br /><br /><a href=\"{$PHP_SELF}?mod={$MNAME}&action=modules\">{$lang['db_prev']}</a>");
}

function en_serialize( $value ) { return str_replace( '"', "'", serialize( $value ) ); }
function de_serialize( $value ) { return unserialize( str_replace("'", '"', $value ) ); }

function mainTable_head( $title, $right = "", $id = false ) {
    if ( $id ) {
        $id = " id=\"{$id}\"";
        $style = " style=\"display:none\"";
    } else { $style = ""; }
    echo <<< HTML
    <div class="box">
        <div class="box-header">
            <div class="title">{$title}</div>
            <ul class="box-toolbar">
                <li class="toolbar-link">
                    {$right}
                </li>
            </ul>
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


function showRow( $title = "", $description = "", $field = "", $hide = false, $id = "" ) {
    $hide = ($hide) ? " style=\"display:none;\"" : "";
    $id = ($id != "") ? " id=\"{$id}\"" : "";
    echo "<tr{$hide}{$id}>
        <td class=\"col-xs-10 col-sm-6 col-md-7\"><h6>{$title}</h6><span class=\"note large\">{$description}</span></td>
        <td class=\"col-xs-2 col-md-5 settingstd\">{$field}</td>
    </tr>";
}


function openTab( $id, $active = false ) {
    $active = ( $active ) ? " active" : "";
    echo <<<HTML
<div class="tab-pane{$active}" id="tab{$id}" >
    <table class="table table-normal table-hover settingsgr">
HTML;
}

function closeTab() {
    echo <<<HTML
    </table>
</div>
HTML;
}

function makeDropDown( $options, $name, $selected ) {
    $output = "<select class=\"uniform\" type=\"settings\" style=\"min-width:100px;\" name=\"{$name}\" id=\"{$name}\">\r\n";
    foreach ( $options as $value => $description ) {
        $output .= "<option value=\"{$value}\"";
        if ( $selected == $value ) {
            $output .= " selected ";
            $tname = $value;
        }
        $output .= ">{$description}</option>\n";
    }
    $output .= "</select>";
    return $output;
    unset( $output );
}


function makeMultiSelect($options, $name, $selected, $class = '') {
    if ( ! is_array( $selected ) ) $selected = de_serialize( $selected );
    $class = ( $class != '' ) ? " {$class}" : "";
    $size = (count($options) >= 6) ? 6 : count($options);
    $output = "<select class=\"uniform{$class}\" style=\"min-width:100px;\" size=\"".$size."\" name=\"{$name}[]\" multiple=\"multiple\">\r\n";
    foreach ( $options as $value => $description ) {
        $output .= "<option value=\"{$value}\"";
        for ($x = 0; $x <= count($selected); $x++) {
            if ($value == $selected[$x]) $output .= " selected ";
        }
        $output .= ">{$description}</option>\n";
    }
    $output .= "</select>";
    return $output;
}


function makeButton( $name, $selected ) {
    $selected = $selected ? "checked" : "";
    return "<input class=\"iButton-icons-tab\" type=\"checkbox\" name=\"{$name}\" value=\"1\" {$selected}>";
}


function hiddenRow($name = "", $value = "") {
    echo "<input type=\"hidden\" name=\"{$name}\" value=\"{$value}\" />";
}

function showCategories( $name = "", $selected ) {
    global $lang, $cat_info, $config, $dle_login_hash, $categories;
    $categories = "<select class=\"uniform\" name=\"{$name}\" id=\"{$name}\">";
    function DisplayCategories($selected, $parentid = 0, $sublevelmarker = '') {
        global $lang, $cat_info, $config, $dle_login_hash, $categories;
        if( $parentid != 0 ) {
            $sublevelmarker .= "--";
        }
        if( count( $cat_info ) ) {
            foreach ( $cat_info as $cats ) {
                if( $cats['parentid'] == $parentid ) $root_category[] = $cats['id'];
            }
            if( count( $root_category ) ) {
                foreach ( $root_category as $id ) {
                    $sel = ( $selected == $cat_info[$id]['id'] ) ? " selected" : "";
                    $categories .= "<option value=\"{$cat_info[$id]['id']}\"{$sel}>{$sublevelmarker}{$cat_info[$id]['name']}</option>";
                    DisplayCategories( $selected, $id, $sublevelmarker );
                }
            }
        }
    }
    DisplayCategories($selected);
    $categories .= "</select>";
    return $categories;
}


function msgbox($title) {
    global $lang, $MNAME;
    mainTable_head($title);
    echo <<< HTML
    <table class="table table-normal" width="100%">
        <tr>
            <td align="center">
                {$lang['opt_sysok_1']}<br /><br /><a href="{$PHP_SELF}?mod={$MNAME}">{$lang['db_prev']}</a>
            </td>
        </tr>
    </table>
HTML;
    mainTable_foot();
}


function naviGation() {
    global $lang, $MNAME;
    echo <<< HTML
    <div class="box">
        <div class="box-content">
            <div class="row box-section">
                <div class="action-nav-normal action-nav-line">
                    <div class="row action-nav-row">
                        <div class="col-sm-3 action-nav-button">
                            <a data-original-title="{$lang['mws_home_page']}" href="javascript:window.location='{$PHP_SELF}?mod={$MNAME}'" class="tip" title=""><i class="icon-home"></i><span>{$lang['mws_home_page']}</span></a>
                        </div>
                        <div class="col-sm-3 action-nav-button">
                            <a data-original-title="{$lang['mws_int_set_info']}" href="javascript:window.location='{$PHP_SELF}?mod={$MNAME}&action=settings'" class="tip" title=""><i class="icon-wrench"></i><span>{$lang['mws_int_set']}</span></a>
                        </div>
                        <div class="col-sm-3 action-nav-button">
                            <a data-original-title="{$lang['mws_module_set_info']}" href="javascript:window.location='{$PHP_SELF}?mod={$MNAME}&action=modules'" class="tip" title=""><i class="icon-desktop"></i><span>{$lang['mws_fmodule_set']}</span></a>
                        </div>
                        <div class="col-sm-3 action-nav-button">
                            <a data-original-title="{$lang['mws_operation_set_info']}" href="javascript:window.location='{$PHP_SELF}?mod={$MNAME}&action=operations'" class="tip" title=""><i class="icon-cogs"></i><span>{$lang['mws_operation_set']}</span></a>
                            <span class="triangle-button green"><i class="icon-plus"></i></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
HTML;
}



function DisplayCategories($parentid = 0, $sublevelmarker = '') {
    global $lang, $cat_info, $config, $dle_login_hash, $mws_mybb, $mybb;
    if( $parentid == 0 ) {
        echo <<<HTML
        <thead>
        <tr>
            <td style="padding:2px;"><b>ID</b></td>
            <td width="%20"><b>{$lang['cat_cat']}</b></td>
            <td align="right" width="%58"><b>{$lang['mws_convers_f']}</b></td>
        </tr>
        </thead>
HTML;
    } else {
        $sublevelmarker .= '--';
    }
    if( count( $cat_info ) ) {
        foreach ( $cat_info as $cats ) {
            if( $cats['parentid'] == $parentid ) $root_category[] = $cats['id'];
        }
        if( count( $root_category ) ) {
            foreach ( $root_category as $id ) {
                $category_name = $cat[$id];
                if( $config['allow_alt_url'] == "yes" ) $link = "<a href=\"" . $config['http_home_url'] . get_url( $id ) . "/\" target=\"_blank\">" . stripslashes( $cat_info[$id]['name'] ) . "</a>";
                else $link = "<a href=\"{$config['http_home_url']}index.php?do=cat&category=" . $cat_info[$id]['alt_name'] . "\" target=\"_blank\">" . stripslashes( $cat_info[$id]['name'] ) . "</a>";
                echo "
                <tr>
                    <td height=\"30\">
                        &nbsp;<b>{$cat_info[$id]['id']}</b>
                    </td>
                    <td>
                        &nbsp;{$sublevelmarker}&nbsp;{$link}
                    </td>
                    <td align=\"right\">";
                    echo $mybb->get_forums("save[forumid_{$id}]","--",$mws_mybb["forumid_{$id}"]);
                    echo "</td>
                </tr>";
                DisplayCategories( $id, $sublevelmarker );
            }
        }
    }
}


function DisplayGroups() {
    global $lang, $mws_mybb, $mybb, $db;
    echo <<<HTML
    <thead>
    <tr>
        <td style="padding:2px;"><b>ID</b></td>
        <td width="%20"><b>{$lang['group_name']}</b></td>
        <td align="right" width="%58"><b>{$lang['mws_convers_g']}</b></td>
    </tr>
    </thead>
HTML;
    $db->query( "SELECT id,group_name FROM " . PREFIX . "_usergroups" );
    while ( $row = $db->get_row() ) {
        $id = $row['id'];
        echo "
        <tr>
            <td height=\"30\">
                &nbsp;<b>{$id}</b>
            </td>
            <td>
                &nbsp;{$row['group_name']}
            </td>
            <td align=\"right\">";
            echo $mybb->get_usergroups("save[groupid_{$id}]",$mws_mybb["groupid_{$id}"]);
            echo "</td>
        </tr>";
    }
}


function GetGroups() {
    global $db;
    $db->query( "SELECT id, group_name FROM " . PREFIX . "_usergroups" );
    $result = array();
    while ( $row = $db->get_row() ) {
        $id = $row['id'];
        $name = $row['group_name'];
        $result[$id] = $name;
    }
    unset($id, $name);
    return $result;
}

$groups = GetGroups();

function table_row( $a, $b, $c, $d, $e, $f, $g ) {
    global $groups;
    $a = substr( $a, 0, 10 );
    $group = $groups[ $e ];
    echo "<tr><td width=\"127\">{$a}</td><td width=\"143\">{$b}</td><td width=\"131\">{$c}</td><td width=\"167\">{$d}</td><td width=\"161\">{$group}</td><td width=\"125\">{$f}</td><td width=\"91\">{$g}</td><td style=\"background: #87B64C; color: #fff; text-align: center;\">OK</td></tr>";
}


echoheader("<i class=\"icon-comments\"></i>MyBB Integrator v{$lang['mws_mybbint_vid']}", $lang['mws_install_inf'] );

$auto_set = $mybb->get_mybb_settings();
echo <<< HTML
<script type="text/javascript">
    function auto_set() {
        var data = {$auto_set};
        ShowLoading('');
        $("#forum_url").val(data.forum_url);
        $("#cookie_forum").val(data.cookiepath);
        $("#cookie_admin").val(data.cookiepath + 'admin/');
        $("#avatar_w").val(data.avatar.w);
        $("#avatar_h").val(data.avatar.h);
        HideLoading('');
        return false;
    }
</script>
HTML;
naviGation();



if ($_REQUEST['action'] == "settings") {
    echo <<< HTML
    <form action="{$PHP_SELF}?mod={$MNAME}&action=save" name="conf" id="conf" method="post">
        <div class="box">
            <div class="box-header">
                <ul class="nav nav-tabs nav-tabs-left">
                    <li class="active"><a href="#tabsettings" data-toggle="tab"><i class="icon-wrench"></i> {$lang['mws_general_set']}</a></li>
                    <li><a href="#tabosettings" data-toggle="tab"><i class="icon-magnet"></i> {$lang['mws_other_set']}</a></li>
                    <li><a href="#tabcategories" data-toggle="tab"><i class="icon-file-alt"></i> {$lang['mws_category_set']}</a></li>
                    <li><a href="#tabgroups" data-toggle="tab"><i class="icon-pencil"></i> {$lang['mws_group_set']}</a></li>
                </ul>
                <ul class="box-toolbar">
                    <li class="toolbar-link">
                        <a style="cursor:pointer" id="auto_link" onclick="auto_set();" title="{$lang['mws_autoset_info']}"><i class="icon-magic"></i> {$lang['mws_autoset']}</a>
                    </li>
                </ul>
            </div>
            <div class="box-content">
                <div class="tab-content">
HTML;

    openTab( "settings", $active = true );
    showRow(
        $lang['mws_forum_index'],
        $lang['mws_forum_index_info'],
        "<input type=\"text\" style=\"text-align: left;\" name='save[forum_dir]' value=\"{$mws_mybb['forum_dir']}\" size=\"20\">&nbsp;<a href=\"#\" class=\"hintanchor\" onMouseover=\"showhint('<img src=\'engine/skins/images/mybb-forum/hint_0.png\' />', this, event, '141px')\">[?]</a>"
    );
    showRow(
        $lang['mws_forum_acp'],
        $lang['mws_forum_acp_info'],
        "<input type=\"text\" style=\"text-align: left;\" name='save[admin_dir]' value=\"{$mws_mybb['admin_dir']}\" size=\"20\">&nbsp;<a href=\"#\" class=\"hintanchor\" onMouseover=\"showhint('<img src=\'engine/skins/images/mybb-forum/hint_1.png\' />', this, event, '182px')\">[?]</a>"
    );
    showRow(
        $lang['mws_subdomain'],
        $lang['mws_subdomain_info'],
        makeDropDown(array(
            "1" => $lang['opt_sys_yes'],
            "0" => $lang['opt_sys_no']
        ),
        "save[use_sdomain]", "{$mws_mybb['use_sdomain']}")."&nbsp;&nbsp;<input type=\"text\" style=\"text-align: left;\" name='save[sdomain_name]' value=\"{$mws_mybb['sdomain_name']}\" size=\"20\">"
    );
    showRow(
        $lang['mws_forum_url'],
        $lang['mws_forum_url_info'],
        "<input type=\"text\" style=\"text-align: left;\" name=\"save[forum_url]\" id=\"forum_url\" value=\"{$mws_mybb['forum_url']}\" size=\"60\">"
    );
    showRow(
        $lang['mws_fcookiep'],
        $lang['mws_fcookiep_info']."<br />".$lang['mws_acookiep_info'],
        "<input type=\"text\" style=\"text-align: left;\" name=\"save[forum_path]\" id=\"cookie_forum\" value=\"{$mws_mybb['forum_path']}\" size=\"24\">  <input type=\"text\" style=\"text-align: left;\" name=\"save[admin_path]\" id=\"cookie_admin\" value=\"{$mws_mybb['admin_path']}\" size=\"24\">"
    );
    showRow(
        $lang['mws_f_online'],
        $lang['mws_f_online_info'],
        makeDropDown(array(
            "1"  => $lang['opt_sys_yes'],
            "0" => $lang['opt_sys_no']
        ),
        "save[login_register]", "{$mws_mybb['login_register']}")
    );
    showRow(
        $lang['mws_login_admin'],
        $lang['mws_login_admin_info'],
        makeDropDown(array(
            "1" => $lang['opt_sys_yes'],
            "0" => $lang['opt_sys_no']
        ),
        "save[login_admin]", "{$mws_mybb['login_admin']}")
    );
    showRow(
        $lang['mws_usecookie'],
        $lang['mws_usecookie_info'],
        "<input type=\"text\" style=\"text-align: left;\" name='save[cookie_time]' value=\"{$mws_mybb['cookie_time']}\" size=\"4\">"
    );
    closeTab();


    openTab( "osettings", $active = false );
    showRow(
        $lang['mws_avatar'],
        $lang['mws_avatar_info'],
        "<input type=\"text\" style=\"text-align: left;\" id=\"avatar_w\" name=\"save[avatar_width]\" value=\"{$mws_mybb['avatar_width']}\" size=\"3\">&nbsp;x&nbsp;<input type=\"text\" style=\"text-align: left;\" name=\"save[avatar_height]\" id=\"avatar_h\" value=\"{$mws_mybb['avatar_height']}\" size=\"3\">"
    );
    showRow(
        $lang['mws_defuser'],
        $lang['mws_defuser_info'],
        makeMultiSelect(array(
            "showavatars"       => $lang['mws_defuser_1'],
            "showsigs"          => $lang['mws_defuser_2'],
            "showredirect"      => $lang['mws_defuser_3'],
            "showquickreply"    => $lang['mws_defuser_4'],
        ),
        "save[default_user]", $mws_mybb['default_user'])
    );
    showRow(
        $lang['mws_deluser'],
        $lang['mws_deluser_info'],
        makeButton("save[delete_posts]", $mws_mybb['delete_posts'])
    );
    showRow(
        $lang['mws_thread_cont'],
        $lang['mws_thread_cont_info'],
        makeDropDown(array(
            "1" => $lang['mws_short_story'],
            "0" => $lang['mws_full_story']
        ),
        "save[add_sstory]", $mws_mybb['add_sstory'])
    );
    showRow(
        $lang['mws_deftopic'],
        $lang['mws_deftopic_info'],
        makeMultiSelect(array(
            "visible"           => $lang['mws_deftopic_1'],
            "smilieoff"         => $lang['mws_deftopic_2'],
            "includesig"        => $lang['mws_deftopic_3'],
        ),
        "save[default_topic]", $mws_mybb['default_topic'])
    );
    $mwshc_link = " <a href=\"http://www.marzochi.ws/mws-urunleri/ucretsiz/113-mybb-mesaj-sayisina-gore-icerik-gizleme.html\">MWS Hidden Content v1.2</a> ";
    showRow(
        $lang['mws_mwshidden'],
        str_replace("{link}", $mwshc_link, $lang['mws_mwshidden_info']),
        makeButton("save[use_mwshc]", $mws_mybb['use_mwshc'])
    );

    showRow( $lang['mws_repltext'], $lang['mws_repltext_info'],
    "<textarea style=\"width:350px;height:100px;\" name=\"save[repl_text]\">{$mws_mybb['repl_text']}</textarea>&nbsp;<a href=\"#\" class=\"hintanchor\" onMouseover=\"showhint('Her satıra bir yazım gelecek şekilde girin.<b>Örnek :</b><br />:wink:=>[img]http://siteniz.com/resim.png[/img]', this, event, '300px')\">[?]</a>" );

    showRow(
        $lang['mws_newstag'],
        $lang['mws_newstag_info'],
        "<textarea style=\"width:350px;height:40px;\" name=\"save[news_tag]\">{$mws_mybb['news_tag']}</textarea>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"
    );

    showRow(
        $lang['mws_forum_newspre'],
        $lang['mws_forum_newspre_info'],
        "<input type=\"text\" style=\"text-align: left;\" name='save[news_pre]' value=\"{$mws_mybb['news_pre']}\" size=\"20\">"
    );

    showRow(
        $lang['mws_mapforum'],
        $lang['mws_mapforum_info'],
        $mybb->get_forums('save[map_forums]', "--", $mws_mybb['map_forums'], $self = True, $sett = array(8, 350) )
    );

    closeTab();

    openTab( "categories", $active = false );
    DisplayCategories();
    closeTab();

    openTab( "groups", $active = false );
    DisplayGroups();
    closeTab();
    echo <<< HTML
            </div>
            <div class="padded">
                <input type="hidden" name="user_hash" value="{$dle_login_hash}" /><input type="submit" class="btn btn-green" value="{$lang['user_save']}">&nbsp;&nbsp;
                <input type="button" value="{$lang['user_brestore']}" class="btn btn-gold" onclick="window.location='{$PHP_SELF}?mod={$MNAME}'">
            </div>
        </form>
    </div>
HTML;

    mainTable_foot();

} else if ($_REQUEST['action'] == "modules") {
    echo <<< HTML
    <form action="{$PHP_SELF}?mod={$MNAME}&action=save-module" name="conf" id="conf" method="post">
        <div class="box">
            <div class="box-header">
                <ul class="nav nav-tabs nav-tabs-left">
                    <li class="active"><a href="#tabmodule_set" data-toggle="tab"><i class="icon-signal"></i> {$lang['mws_0_name']}</a></li>
                    <li><a href="#tabmodule_set2" data-toggle="tab"><i class="icon-star"></i> {$lang['mws_1_name']}</a></li>
                </ul>
            </div>
            <div class="box-content">
                <div class="tab-content">
HTML;

    openTab( "module_set", $active = true );

    showRow($lang['mws_activate_mod'], $lang['mws_activate_info'], makeDropDown(array(
        "1" => $lang['opt_sys_yes'],
        "0" => $lang['opt_sys_no']
    ) , "savem[0_onoff]", $mws_mmybb['0_onoff']));

    if ( $mws_mmybb['0_onoff'] == "1" ) {

        showRow($lang['mws_0_set1'], $lang['mws_0_set1_info'],
        "<input type=\"text\" style=\"text-align: left;\" name='savem[0_tlimit]' value=\"{$mws_mmybb['0_tlimit']}\" size=\"10\">" );
        showRow($lang['mws_0_set2'], $lang['mws_0_set2_info'],
        "<input type=\"text\" style=\"text-align: left;\" name='savem[0_ulimit]' value=\"{$mws_mmybb['0_ulimit']}\" size=\"10\">" );
        showRow($lang['mws_0_set12'], $lang['mws_0_set12_info'], makeDropDown(array(
            "1" => $lang['opt_sys_yes'],
            "0" => $lang['opt_sys_no']
        ) , "savem[0_spref]", $mws_mmybb['0_spref']));
        showRow($lang['mws_0_set3'], $lang['mws_0_set3_info'],
        "<input type=\"text\" style=\"text-align: left;\" name='savem[0_lastx]' value=\"{$mws_mmybb['0_lastx']}\" size=\"10\">" );
        showRow($lang['mws_0_set4'], $lang['mws_0_set4_info'] ."<br /><a onClick=\"javascript:Help('date'); return false;\" class=main href=\"#\">{$lang[opt_sys_and]}</a>",
        "<input type=\"text\" style=\"text-align: left;\" name='savem[0_datef]' value=\"{$mws_mmybb['0_datef']}\" size=\"20\">" );
        showRow($lang['mws_0_set5'], $lang['mws_0_set5_info'], makeDropDown(array(
            "auto" => $lang['mws_0_sets1'],
            "1" => $lang['safe_mode_on'],
            "0" => $lang['safe_mode_off']
        ) , "savem[0_ucache]", $mws_mmybb['0_ucache']));
        showRow($lang['mws_0_set6'],$lang['mws_0_set6_info'], makeDropDown(array(
            "1" => $lang['safe_mode_on'],
            "0" => $lang['safe_mode_off']
        ) , "savem[0_mybbs]", $mws_mmybb['0_mybbs']));
        showRow($lang['mws_0_set7'], $lang['mws_0_set7_info'],
        "<input type=\"text\" style=\"text-align: left;\" name='savem[0_exfid]' value=\"{$mws_mmybb['0_exfid']}\" size=\"20\">" );
        showRow($lang['mws_0_set8'], $lang['mws_0_set8_info'], makeDropDown(array(
            "1" => $lang['mws_0_sets2'],
            "0" => $lang['mws_0_sets3']
        ) , "savem[0_lastml]", $mws_mmybb['0_lastml']));
        showRow($lang['mws_0_set9'], $lang['mws_0_set9_info'], makeDropDown(array(
            "1" => $lang['opt_sys_yes'],
            "0" => $lang['opt_sys_no']
        ) , "savem[0_showpp]", $mws_mmybb['0_showpp']));
        showRow($lang['mws_0_set10'],$lang['mws_0_set10_info'], makeDropDown(array(
            "1" => $lang['opt_sys_yes'],
            "0" => $lang['opt_sys_no']
        ) , "savem[0_sflink]", $mws_mmybb['0_sflink']));
        showRow($lang['mws_0_set11'],$lang['mws_0_set11_info'], makeDropDown(array(
            "1" => $lang['opt_sys_yes'],
            "0" => $lang['opt_sys_no']
        ) , "savem[0_nflwl]", $mws_mmybb['0_nflwl']));
    }
    closeTab();


    openTab( "module_set2", $active = false );
    showRow($lang['mws_activate_mod'], $lang['mws_activate_info'], makeDropDown(array(
        "1" => $lang['opt_sys_yes'],
        "0" => $lang['opt_sys_no']
    ) , "savem[1_onoff]", $mws_mmybb['1_onoff']));

    if ( $mws_mmybb['1_onoff'] == "1" ) {

        showRow($lang['mws_1_set1'], $lang['mws_1_set1_info'], makeDropDown(array(
            "1" => $lang['opt_sys_yes'],
            "0" => $lang['opt_sys_no']
        ) , "savem[1_gownr]", $mws_mmybb['1_gownr']));
        showRow($lang['mws_1_set2'], $lang['mws_1_set2_info'],
        "<input type=\"text\" style=\"text-align: left;\" name='savem[1_maxrep]' value=\"{$mybb->sett['posrep']}\" size=\"10\" disabled />" );
        showRow(
            $lang['mws_1_set3'],
            $lang['mws_1_set3_info'],
            makeButton("savem[1_multrep]", $mws_mmybb['1_multrep'])
        );
        showRow($lang['mws_1_set4'], $lang['mws_1_set4_info'],
        "<input type=\"text\" style=\"text-align: left;\" name='savem[1_txtlmt]' value=\"{$mws_mmybb['1_txtlmt']}\" size=\"10\">" );
        showRow($lang['mws_1_set5'], $lang['mws_1_set5_info'],
        "<input type=\"text\" style=\"text-align: left;\" name='savem[1_replmt]' value=\"{$mws_mmybb['1_replmt']}\" size=\"10\">" );

        $grp = GetGroups();unset($grp[5]);
        showRow(
            $lang['mws_1_set6'],
            $lang['mws_1_set6_info'],
            makeMultiSelect(
                $grp,
                "savem[1_agrps]", $mws_mmybb['1_agrps']
            )
        );
        unset($grp);
    }
    closeTab();

    echo <<< HTML
            </div>
            <div class="padded">
                <input type="hidden" name="user_hash" value="{$dle_login_hash}" /><input type="submit" class="btn btn-green" value="{$lang['user_save']}">&nbsp;&nbsp;
                <input type="button" value="{$lang['user_brestore']}" class="btn btn-gold" onclick="window.location='{$PHP_SELF}?mod={$MNAME}'">
            </div>
        </form>
    </div>
HTML;
    mainTable_foot();


} else if ($_REQUEST['action'] == "import_to_mybb") {
    mainTable_head($lang['mws_suc_transfer']);
echo <<< HTML
    <thead>
        <tr>
            <td>{$lang['mws_password']} (Hash)</td>
            <td>{$lang['mws_user_id']}</td>
            <td>{$lang['mws_user_name']}</td>
            <td>{$lang['mws_user_email']}</td>
            <td>{$lang['mws_user_group']}</td>
            <td>{$lang['mws_reg_date']}</td>
            <td>{$lang['mws_reg_ip']}</td>
            <td>{$lang['mws_status']}</td>
        </tr>
    </thead>
HTML;
    echo "<table class=\"table table-normal\" width=\"100%\">";
    $mybb->user_conversion( $from = 0, $range = 0 );
    echo "</table>";
    mainTable_foot();
    echo <<< HTML
    <table class="table-normal table">
        <tr><td>
            <input type="button" value="{$lang['user_brestore']}" class="btn btn-green" onclick="window.location='{$PHP_SELF}?mod={$MNAME}'">
        </td></tr>
    </table>
HTML;

} else if ($_REQUEST['action'] == "refresh_cache") {
    $user = false;$thread = false;$post = false;$sess = false;
    if( $_REQUEST['thread'] == "1") $threads = True;
    if( $_REQUEST['post'] == "1") $post = True;
    if( $_REQUEST['user'] == "1") $user = True;
    if( $_REQUEST['sess'] == "1") $mybb->clean_session();
    $mybb->refresh_cache($threads = $threads, $post = $post, $user = $user);
    msgbox($lang['mws_datacache_updated'], "{$lang['mws_datacache_updated']}<br /><br /><a href={$PHP_SELF}?mod={$MNAME}>{$lang['db_prev']}</a>");

} else if ($_REQUEST['action'] == "operations") {
    mainTable_head($lang['mws_operation_set']);
    $sett['showavatars'] = $lang['opt_sys_no'];
    $sett['showsigs'] = $lang['opt_sys_no'];
    $sett['showredirect'] = $lang['opt_sys_no'];
    $sett['showquickreply'] = $lang['opt_sys_no'];
    for ($x = 0; $x < count($mybb->conf['default_user']); $x++) {
        $sett[$mybb->conf['default_user'][$x]] = $lang['opt_sys_yes'];
    }

    echo <<< HTML
    <table class="table-normal table">
        <tr>
            <td style="padding:2px;border: 0;" width="48%">
                <table>
                    <tr>
                        <td width="60" height="60" style="border: 0;">
                            <img src="engine/skins/images/mybb-forum/cache.png" title="" alt="" />
                        </td>
                        <td>
                            <h2>{$lang['mws_refresh_dcache']}</h2>
                        </td>
                    </tr>
                </table>

                &#8226&nbsp;{$lang['mws_refresh_dcache_info']}
                <br /><br />
                <form action="{$PHP_SELF}?mod={$MNAME}&action=refresh_cache" name="conf" id="conf" method="post">
                    <input name="thread" type="checkbox"> {$lang['mws_threads_num']} {$lang['mws_cache_where']}<br />
                    <input name="post" type="checkbox"> {$lang['mws_posts_num']} {$lang['mws_cache_where']}<br />
                    <input name="user" type="checkbox"> {$lang['mws_users_num']} {$lang['mws_cache_where']}<br />
                    <input name="sess" type="checkbox"> {$lang['mws_sess_tails']}<br /><br />
                    <div style="float:right">
                        <input type="hidden" name="user_hash" value="{$dle_login_hash}" /><br />
                        <input type="submit" class="btn btn-green" value="{$lang['mws_refresh_dcache']}">
                    </div>
                </form>
            </td>
            <td width="4%">&nbsp;</td>
            <td style="padding:2px;border: 0;" width="48%">
                <table>
                    <tr>
                        <td width="60" height="60" style="border: 0;">
                            <img src="engine/skins/images/mybb-forum/mybb.png" title="" alt="" />
                        </td>
                        <td>
                            <h2>{$lang['mws_import_to_mybb']}</h2>
                        </td>
                    </tr>
                </table>
                &#8226&nbsp;{$lang['mws_conver_users_info']}
                <br /><br />
                <b>{$lang['mws_current_set']}</b>
                <ul>
                    <li>{$lang['mws_defuser_1']} : <b>{$sett['showavatars']}</b></li>
                    <li>{$lang['mws_defuser_2']} : <b>{$sett['showsigs']}</b></li>
                    <li>{$lang['mws_defuser_3']} : <b>{$sett['showredirect']}</b></li>
                    <li>{$lang['mws_defuser_4']} : <b>{$sett['showquickreply']}</b></li>
                </ul>
                <form action="{$PHP_SELF}?mod={$MNAME}&action=import_to_mybb" name="conf" id="conf" method="post">
                    <input type="hidden" name="user_hash" value="{$dle_login_hash}" /><br />
                    <div align="right">
                        <input type="submit" class="btn btn-green" value="{$lang['mws_convert_users']}">
                    </div>
                </form>
            </td>
        </tr>
    </table>

HTML;
    mainTable_foot();
}


else {
    $core_file = ROOT_DIR . $mws_mybb['forum_dir'] ."/inc/class_core.php";
    if ( file_exists( $core_file ) ) {
        require_once $core_file;
        $mybbcore = new MyBB();
        $mybbversion = $mybbcore->version;
    } else $mybbversion = "1.6.x";

    mainTable_head("{$lang['mws_home_page']}");
    $count = ( $mws_mmybb['0_onoff'] == "1" ) ? 1 : 0;
    $count = ( $mws_mmybb['1_onoff'] == "1" ) ? ++$count : $count;
    echo <<< HTML
    <table class="table-normal table">
        <tr>
            <td style="padding:4px;" width="60%">
                <b>DLE + MyBB Integrator {$lang['mws_mybbint_vid']} : <a href="http://dle.net.tr/user/MaRZoCHi/" target="_blank">Mehmet Hanoğlu ( MaRZoCHi )</a></b><br />
                {$lang['mws_module_page']} : <a href="http://forum.dle.net.tr/datalife-engine/modul/138-mybb-integrator-destek-support.html" target="_blank">DLE.NET.TR | MyBB Integrator v{$lang['mws_mybbint_vid']}</a>
                <br /><br />
                {$lang['mws_mail_notify']}<br />
                {$lang['mws_client_info']}<br />
                <a href="http://forum.dle.net.tr/datalife-engine/modul/138-mybb-integrator-destek-support.html" target="_blank">{$lang['mws_special_sforum']} (<i><span class="small">{$lang['mws_client_forum']}</span></i>)
                <br /><br />

            </td>
            <td style="padding-left:10px; padding-bottom:10px;padding-right:10px;" colspan="4" valign="top" width="35%">
                <table class="table-normal table">
                    <tr>
                        <td width="%50">
                            {$lang['mws_lang_support']}
                        </td>
                        <td width="%50">
                            <a href="http://{$lang['mws_translator_site']}" title="{$lang['mws_translator_name']}">{$lang['mws_translator_name']}</a><br />
                            <span class="small">{$lang['mws_translator_mail']}</span>
                        </td>
                    </tr>
                    <tr>
                        <td width="%50">
                            {$lang['dle_version']}
                        </td>
                        <td width="%50">
                            {$config['version_id']}
                        </td>
                    </tr>
                    <tr>
                        <td width="%50">
                            {$lang['mws_mybb_version']}
                        </td>
                        <td width="%50">
                            {$mybbversion}
                        </td>
                    </tr>
                    <tr>
                        <td width="%50">
                            {$lang['mws_int_version']}
                        </td>
                        <td width="%50">
                            {$lang['mws_mybbint_vid']} ({$lang['mws_mybbint_date']})
                        </td>
                    </tr>
                    <tr>
                        <td width="%50">
                            Aktif Modül Sayısı
                        </td>
                        <td width="%50">
                            {$count}
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
    <div style="padding: 5px; border-top: 1px solid #E0E1E5;">
        <div style="float: right; padding: 5px;">
            <a href="http://dle.net.tr" target="_blank"><img src="engine/skins/images/mybb-forum/dnt.png" width="100" /></a>
        </div>
    </div>
    <div style="clear: both"></div>


HTML;
    mainTable_foot();
    if ($fail) {
        mainTable_head($lang['mws_warnings_label']);
        echo <<< HTML
        <table class="table-normal table">
            <tr>
                <td colspan="2" class="ui-state-error" style="padding:10px; margin-bottom:10px;">
                    {$fail}
                </td>
            </tr>
        </table>
HTML;
        mainTable_foot();
    }
}


echofooter();

?>