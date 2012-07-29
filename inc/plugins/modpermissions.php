<?php
/**
 * Moderator CP Permissions
 * Copyright 2011 Starpaul20
 */

// Disallow direct access to this file for security reasons
if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

// Neat trick for caching our custom template(s)
if(my_strpos($_SERVER['PHP_SELF'], 'modcp.php'))
{
	global $templatelist;
	if(isset($templatelist))
	{
		$templatelist .= ',';
	}
	$templatelist .= 'modcp_latestfivemodactions';
}

// Tell MyBB when to run the hooks
$plugins->add_hook("modcp_new_announcement", "modpermissions_announcement");
$plugins->add_hook("modcp_delete_announcement", "modpermissions_announcement");
$plugins->add_hook("modcp_edit_announcement", "modpermissions_announcement");
$plugins->add_hook("modcp_announcements", "modpermissions_announcement");
$plugins->add_hook("modcp_modlogs_start", "modpermissions_modlogs");
$plugins->add_hook("modcp_finduser_start", "modpermissions_profiles");
$plugins->add_hook("modcp_editprofile_start", "modpermissions_profiles");
$plugins->add_hook("modcp_banning_start", "modpermissions_ban");
$plugins->add_hook("modcp_liftban_start", "modpermissions_ban");
$plugins->add_hook("modcp_banuser_start", "modpermissions_ban");
$plugins->add_hook("modcp_warninglogs_start", "modpermissions_warnlogs");
$plugins->add_hook("modcp_ipsearch_posts_start", "modpermissions_ipsearch");
$plugins->add_hook("modcp_ipsearch_users_start", "modpermissions_ipsearch");
$plugins->add_hook("modcp_iplookup_end", "modpermissions_ipsearch");
$plugins->add_hook("modcp_end", "modpermissions_modlogs_index");

$plugins->add_hook("admin_user_groups_edit_graph_tabs", "modpermissions_usergroups_permission");
$plugins->add_hook("admin_user_groups_edit_graph", "modpermissions_usergroups_graph");
$plugins->add_hook("admin_user_groups_edit_commit", "modpermissions_usergroups_commit");

// The information that shows up on the plugin manager
function modpermissions_info()
{
	return array(
		"name"				=> "Moderator CP Permissions",
		"description"		=> "Allows you to limit what sections of the Moderator CP a specific usergroup can use.",
		"website"			=> "http://galaxiesrealm.com/index.php",
		"author"			=> "Starpaul20",
		"authorsite"		=> "http://galaxiesrealm.com/index.php",
		"version"			=> "1.0.2",
		"guid"				=> "263e29c9ac7a23ec5c12587de1299fef",
		"compatibility"		=> "16*"
	);
}

// This function runs when the plugin is installed.
function modpermissions_install()
{
	global $db, $cache;
	modpermissions_uninstall();

	$db->add_column("usergroups", "canmanageannounce", "int(1) NOT NULL default '1'");
	$db->add_column("usergroups", "canviewmodlogs", "int(1) NOT NULL default '1'");
	$db->add_column("usergroups", "caneditprofiles", "int(1) NOT NULL default '1'");
	$db->add_column("usergroups", "canbanusers", "int(1) NOT NULL default '1'");
	$db->add_column("usergroups", "canviewwarnlogs", "int(1) NOT NULL default '1'");
	$db->add_column("usergroups", "canuseipsearch", "int(1) NOT NULL default '1'");

	// Setting some basic moderator permissions...
	$update_array = array(
		"canmanageannounce" => 0,
		"canviewmodlogs" => 0,
		"caneditprofiles" => 0,
		"canbanusers" => 0,
		"canviewwarnlogs" => 0,
		"canuseipsearch" => 0
	);
	$db->update_query("usergroups", $update_array, "canmodcp != '1'");

	$cache->update_usergroups();
}

// Checks to make sure plugin is installed
function modpermissions_is_installed()
{
	global $db;
	if($db->field_exists("canmanageannounce", "usergroups"))
	{
		return true;
	}
	return false;
}

// This function runs when the plugin is uninstalled.
function modpermissions_uninstall()
{
	global $db, $cache;
	if($db->field_exists('canmanageannounce', 'usergroups'))
	{
		$db->drop_column("usergroups", "canmanageannounce");
	}

	if($db->field_exists('canviewmodlogs', 'usergroups'))
	{
		$db->drop_column("usergroups", "canviewmodlogs");
	}

	if($db->field_exists('caneditprofiles', 'usergroups'))
	{
		$db->drop_column("usergroups", "caneditprofiles");
	}

	if($db->field_exists('canbanusers', 'usergroups'))
	{
		$db->drop_column("usergroups", "canbanusers");
	}

	if($db->field_exists('canviewwarnlogs', 'usergroups'))
	{
		$db->drop_column("usergroups", "canviewwarnlogs");
	}

	if($db->field_exists('canuseipsearch', 'usergroups'))
	{
		$db->drop_column("usergroups", "canuseipsearch");
	}

	$cache->update_usergroups();
}

// This function runs when the plugin is activated.
function modpermissions_activate()
{
	global $db;

	// Insert templates
	$insert_array = array(
		'title'		=> 'modcp_latestfivemodactions',
		'template'	=> $db->escape_string('<br />
<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
<tr>
	<td class="thead" align="center" colspan="5"><strong>{$lang->latest_5_modactions}</strong></td>
</tr>
<tr>
<td class="tcat"><span class="smalltext"><strong>{$lang->username}</strong></span></td>
<td class="tcat" align="center"><span class="smalltext"><strong>{$lang->date}</strong></span></td>
<td class="tcat" align="center"><span class="smalltext"><strong>{$lang->action}</strong></span></td>
<td class="tcat" align="center"><span class="smalltext"><strong>{$lang->information}</strong></span></td>
<td class="tcat" align="center"><span class="smalltext"><strong>{$lang->ip}</strong></span></td>
</tr>
{$modlogresults}
</table>'),
		'sid'		=> '-1',
		'version'	=> '',
		'dateline'	=> TIME_NOW
	);
	$db->insert_query("templates", $insert_array);

	// Update templates
	include MYBB_ROOT."/inc/adminfunctions_templates.php";
	find_replace_templatesets("modcp", "#".preg_quote('<br />
<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
<tr>
	<td class="thead" align="center" colspan="5"><strong>{$lang->latest_5_modactions}</strong></td>
</tr>
<tr>
<td class="tcat"><span class="smalltext"><strong>{$lang->username}</strong></span></td>
<td class="tcat" align="center"><span class="smalltext"><strong>{$lang->date}</strong></span></td>
<td class="tcat" align="center"><span class="smalltext"><strong>{$lang->action}</strong></span></td>
<td class="tcat" align="center"><span class="smalltext"><strong>{$lang->information}</strong></span></td>
<td class="tcat" align="center"><span class="smalltext"><strong>{$lang->ip}</strong></span></td>
</tr>
{$modlogresults}
</table>')."#i", '{$modlog}');
}

// This function runs when the plugin is deactivated.
function modpermissions_deactivate()
{
	global $db;
	$db->delete_query("templates", "title IN('modcp_latestfivemodactions')");

	include MYBB_ROOT."/inc/adminfunctions_templates.php";
	find_replace_templatesets("modcp", "#".preg_quote('{$modlog}')."#i", '<br />
<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
<tr>
	<td class="thead" align="center" colspan="5"><strong>{$lang->latest_5_modactions}</strong></td>
</tr>
<tr>
<td class="tcat"><span class="smalltext"><strong>{$lang->username}</strong></span></td>
<td class="tcat" align="center"><span class="smalltext"><strong>{$lang->date}</strong></span></td>
<td class="tcat" align="center"><span class="smalltext"><strong>{$lang->action}</strong></span></td>
<td class="tcat" align="center"><span class="smalltext"><strong>{$lang->information}</strong></span></td>
<td class="tcat" align="center"><span class="smalltext"><strong>{$lang->ip}</strong></span></td>
</tr>
{$modlogresults}
</table>', 0);
}

// Usergroup permissions
function modpermissions_usergroups_permission($tabs)
{
	global $lang;
	$lang->load("modpermissions");

	$tabs['modcp'] = $lang->mod_cp;
	return $tabs;
}

function modpermissions_usergroups_graph()
{
	global $lang, $form, $mybb;
	$lang->load("modpermissions");

	// Mod CP Permissions
	echo "<div id=\"tab_modcp\">";	
	$form_container = new FormContainer($lang->mod_cp);

	$forum_post_options = array(
		$form->generate_check_box("canmanageannounce", 1, $lang->can_manage_announce, array("checked" => $mybb->input['canmanageannounce'])),
		$form->generate_check_box("canviewmodlogs", 1, $lang->can_view_modlogs, array("checked" => $mybb->input['canviewmodlogs']))
	);
	$form_container->output_row($lang->forum_post_options, "", "<div class=\"group_settings_bit\">".implode("</div><div class=\"group_settings_bit\">", $forum_post_options)."</div>");

	$user_options = array(
		$form->generate_check_box("caneditprofiles", 1, $lang->can_edit_profiles, array("checked" => $mybb->input['caneditprofiles'])),
		$form->generate_check_box("canbanusers", 1, $lang->can_ban_users, array("checked" => $mybb->input['canbanusers'])),
		$form->generate_check_box("canviewwarnlogs", 1, $lang->can_view_warnlogs, array("checked" => $mybb->input['canviewwarnlogs'])),
		$form->generate_check_box("canuseipsearch", 1, $lang->can_use_ipsearch, array("checked" => $mybb->input['canuseipsearch']))
	);
	$form_container->output_row($lang->user_options, "", "<div class=\"group_settings_bit\">".implode("</div><div class=\"group_settings_bit\">", $user_options)."</div>");

	$form_container->end();
	echo "</div>";
}

function modpermissions_usergroups_commit()
{
	global $updated_group, $mybb;
	$updated_group['canmanageannounce'] = intval($mybb->input['canmanageannounce']);
	$updated_group['canviewmodlogs'] = intval($mybb->input['canviewmodlogs']);
	$updated_group['caneditprofiles'] = intval($mybb->input['caneditprofiles']);
	$updated_group['canbanusers'] = intval($mybb->input['canbanusers']);
	$updated_group['canviewwarnlogs'] = intval($mybb->input['canviewwarnlogs']);
	$updated_group['canuseipsearch'] = intval($mybb->input['canuseipsearch']);
}

// Announcement permission
function modpermissions_announcement()
{
	global $mybb;
	if($mybb->usergroup['canmanageannounce'] == 0 && $mybb->usergroup['cancp'] != 1)
	{
		error_no_permission();
	}
}

// Mod Log permission
function modpermissions_modlogs()
{
	global $mybb;
	if($mybb->usergroup['canviewmodlogs'] == 0 && $mybb->usergroup['cancp'] != 1)
	{
		error_no_permission();
	}
}

// Editing profile permission
function modpermissions_profiles()
{
	global $mybb;
	if($mybb->usergroup['caneditprofiles'] == 0 && $mybb->usergroup['cancp'] != 1)
	{
		error_no_permission();
	}
}

// Banning User permission
function modpermissions_ban()
{
	global $mybb;
	if($mybb->usergroup['canbanusers'] == 0 && $mybb->usergroup['cancp'] != 1)
	{
		error_no_permission();
	}
}

// Warning Log permission
function modpermissions_warnlogs()
{
	global $mybb;
	if($mybb->usergroup['canviewwarnlogs'] == 0 && $mybb->usergroup['cancp'] != 1)
	{
		error_no_permission();
	}
}

// IP Search permission
function modpermissions_ipsearch()
{
	global $mybb;
	if($mybb->usergroup['canuseipsearch'] == 0 && $mybb->usergroup['cancp'] != 1)
	{
		error_no_permission();
	}
}

// Mod Log permission
function modpermissions_modlogs_index()
{
	global $db, $mybb, $lang, $templates, $theme, $modlogresults, $modlog;
	if($mybb->usergroup['canviewmodlogs'] == 1 || $mybb->usergroup['cancp'] != 0)
	{
		eval("\$modlog = \"".$templates->get("modcp_latestfivemodactions")."\";");
	}
}

?>