<?php
/*
 ex: set tabstop=4 shiftwidth=4 autoindent:
 +-------------------------------------------------------------------------+
 | Copyright (C) 2004-2009 The Cacti Group                                 |
 |                                                                         |
 | This program is free software; you can redistribute it and/or           |
 | modify it under the terms of the GNU General Public License             |
 | as published by the Free Software Foundation; either version 2          |
 | of the License, or (at your option) any later version.                  |
 |                                                                         |
 | This program is distributed in the hope that it will be useful,         |
 | but WITHOUT ANY WARRANTY; without even the implied warranty of          |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the           |
 | GNU General Public License for more details.                            |
 +-------------------------------------------------------------------------+
 | Cacti: The Complete RRDTool-based Graphing Solution                     |
 +-------------------------------------------------------------------------+
 | This code is designed, written, and maintained by the Cacti Group. See  |
 | about.php and/or the AUTHORS file for specific developer information.   |
 +-------------------------------------------------------------------------+
 | http://www.cacti.net/                                                   |
 +-------------------------------------------------------------------------+
*/

function plugin_lookalikee_install() {
	api_plugin_register_hook('lookalikee', 'config_arrays',            'lookalikee_config_arrays',   "setup.php");
	api_plugin_register_hook('lookalikee', 'config_settings',          'lookalikee_config_settings', "setup.php");
	api_plugin_register_hook('lookalikee', 'graph_buttons',            'lookalikee_graph_button',    "setup.php");
	api_plugin_register_hook('lookalikee', 'graph_buttons_thumbnails', 'lookalikee_graph_button',    "setup.php");
	api_plugin_register_hook('lookalikee', 'page_head',                'lookalikee_page_head',       "setup.php");
	api_plugin_register_hook('lookalikee', 'top_graph_header_tabs',    'lookalikee_top_graph_header_tabs', "setup.php");

	lookalikee_setup_table_new ();
}

function plugin_lookalikee_uninstall () {
	/* Do any extra Uninstall stuff here */
}

function plugin_lookalikee_check_config () {
	/* Here we will check to ensure everything is configured */
	lookalikee_check_upgrade();
	return true;
}

function plugin_lookalikee_upgrade () {
	/* Here we will upgrade to the newest version */
	lookalikee_check_upgrade();
	return false;
}

function plugin_lookalikee_version () {
	return lookalikee_version();
}

function lookalikee_check_upgrade () {
	global $config;

	$files = array('index.php', 'plugins.php', 'removespikes.php');
	if (isset($_SERVER['PHP_SELF']) && !in_array(basename($_SERVER['PHP_SELF']), $files)) {
		return;
	}

	$current = plugin_lookalikee_version();
	$current = $current['version'];
	$old     = db_fetch_row("SELECT * FROM plugin_config WHERE directory='lookalikee'");
	if (sizeof($old) && $current != $old["version"]) {
		/* if the plugin is installed and/or active */
		if ($old["status"] == 1 || $old["status"] == 4) {
			/* re-register the hooks */
			plugin_lookalikee_install();

			/* perform a database upgrade */
			lookalikee_database_upgrade();
		}

		/* update the plugin information */
		$info = plugin_lookalikee_version();
		$id   = db_fetch_cell("SELECT id FROM plugin_config WHERE directory='lookalikee'");
		db_execute("UPDATE plugin_config
			SET name='" . $info["longname"] . "',
			author='"   . $info["author"]   . "',
			webpage='"  . $info["homepage"] . "',
			version='"  . $info["version"]  . "'
			WHERE id='$id'");
	}
}

function lookalikee_database_upgrade () {
}

function lookalikee_check_dependencies() {
	global $plugins, $config;

	return true;
}

function lookalikee_setup_table_new () {
}

function lookalikee_top_graph_header_tabs () {
	global $config;
	echo '<script language="JavaScript" type="text/javascript" src="' . $config['url_path'] . 'plugins/lookalikee/wz_tooltip.js"></script>';
	echo '<div class="lookalikee" id="lookalikee" style="width:auto;overflow:hidden;z-index:1010;visibility:hidden;position:absolute;top:0px;left:0px;"></div>';
}

function lookalikee_version () {
	return array(
		'name'     => 'lookalikee',
		'version'  => '1.2',
		'longname' => 'Spike Killer for Cacti Graphs',
		'author'   => 'The Cacti Group',
		'homepage' => 'http://www.cacti.net',
		'email'    => '',
		'url'      => 'http://versions.cactiusers.org'
	);
}

function lookalikee_config_settings () {
	global $tabs, $settings;

	/* check for an upgrade */
	plugin_lookalikee_check_config();

	$tabs["misc"] = "Misc";

	$temp = array(
		"lookalikee_header" => array(
			"friendly_name" => "Spike Kill Settings",
			"method" => "spacer",
			),
		"lookalikee_method" => array(
			"friendly_name" => "Removal Method",
			"description" => "There are two removal methods.  The first, Standard Deviation, will remove any
			sample that is X number of standard deviations away from the average of samples.  The second method,
			Variance, will remove any sample that is X% more than the Variance average.  The Variance method takes
			into account a certain number of 'outliers'.  Those are exceptinal samples, like the spike, that need
			to be excluded from the Variance Average calculation.",
			"method" => "drop_array",
			"default" => "1",
			"array" => array(1 => "Standard Deviation", 2=> "Variance Based w/Outliers Removed")
			),
		"lookalikee_avgnan" => array(
			"friendly_name" => "Replacement Method",
			"description" => "There are two replacement methods.  The first method replaces the spike with the
			the average of the data source in question.  The second method replaces the spike with a 'NaN'.",
			"method" => "drop_array",
			"default" => "1",
			"array" => array(1 => "Average", 2=> "NaN's")
			),
		"lookalikee_deviations" => array(
			"friendly_name" => "Number of Standard Deviations",
			"description" => "Any value that is this many standard deviations above the average will be excluded.
			A good number will be dependent on the type of data to be operated on.  We recommend a number no lower
			than 5 Standard Deviations.",
			"method" => "drop_array",
			"default" => "5",
			"array" => array(
				3 => "3 Standard Deviations",
				4 => "4 Standard Deviations",
				5 => "5 Standard Deviations",
				6 => "6 Standard Deviations",
				7 => "7 Standard Deviations",
				8 => "8 Standard Deviations",
				9 => "9 Standard Deviations",
				10 => "10 Standard Deviations"
				)
			),
		"lookalikee_percent" => array(
			"friendly_name" => "Variance Percentage",
			"description" => "This value represents the percentage above the adjusted sample average once outliers
			have been removed from the sample.  For example, a Variance Percentage of 100% on an adjusted average of 50
			would remove any sample above the quantity of 100 from the graph.",
			"method" => "drop_array",
			"default" => "500",
			"array" => array(
				100 => "100 Percent",
				200 => "200 Percent",
				300 => "300 Percent",
				400 => "400 Percent",
				500 => "500 Percent",
				600 => "600 Percent",
				700 => "700 Percent",
				800 => "800 Percent",
				900 => "900 Percent",
				1000 => "1000 Percent"
				)
			),
		"lookalikee_outliers" => array(
			"friendly_name" => "Variance Number of Outliers",
			"description" => "This value represents the number of high and low average samples will be removed from the
			sample set prior to calculating the Variance Average.  If you choose an outlier value of 5, then both the top
			and bottom 5 averages are removed.",
			"method" => "drop_array",
			"default" => "5",
			"array" => array(
				3 => "3 High/Low Samples",
				4 => "4 High/Low Samples",
				5 => "5 High/Low Samples",
				6 => "6 High/Low Samples",
				7 => "7 High/Low Samples",
				8 => "8 High/Low Samples",
				9 => "9 High/Low Samples",
				10 => "10 High/Low Samples"
				)
			),
		"lookalikee_number" => array(
			"friendly_name" => "Max Kills Per RRA",
			"description" => "This value represents the maximum spikes to remove from the graph RRA.",
			"method" => "drop_array",
			"default" => "5",
			"array" => array(
				3 => "3 Samples",
				4 => "4 Samples",
				5 => "5 Samples",
				6 => "6 Samples",
				7 => "7 Samples",
				8 => "8 Samples",
				9 => "9 Samples",
				10 => "10 Samples"
				)
			),
		"lookalikee_backupdir" => array(
			"friendly_name" => "RRDfile Backup Directory",
			"description" => "If this directory is not empty, then your original RRDfiles will be backed
			up to this location.",
			"method" => "dirpath",
			"default" => "",
			"max_length" => "255",
			"size" => "60"
			),
		);

	if (isset($settings["misc"])) {
		$settings["misc"] = array_merge($settings["misc"], $temp);
	}else {
		$settings["misc"] = $temp;
	}
}

function lookalikee_page_head () {
	global $config;

	print "<script type='text/javascript' src='" . $config["url_path"] . "plugins/lookalikee/lookalikee.js'></script>";
	print "<link type='text/css' rel='stylesheet' href='" . $config["url_path"] . "plugins/lookalikee/lookalikee.css'></link>";
}

function lookalikee_config_arrays () {
	global $user_auth_realms, $user_auth_realm_filenames, $menu, $messages;

	$user_auth_realm_filenames['lookalikee.php'] = 1043;
	$user_auth_realm_filenames['lookalikee_ajax.php'] = 1043;
	$user_auth_realms[1043]='Plugin -> Remove Spikes on Graphs';
}

function lookalikee_graph_button($data) {
	global $config;

	if (lookalikee_authorized()){
		$local_graph_id = $data[1]['local_graph_id'];
		$tip = "'<div class=\'lookalikee\'><a href=\'javascript:removeSpikesStdDev(&quot;" . $local_graph_id . "&quot;)\'>Remove Using StdDev</a><a href=\'javascript:removeSpikesVariance(&quot;" . $local_graph_id . "&quot;)\'>Remove Using Variance</a><a href=\'javascript:dryRunStdDev(&quot;" . $local_graph_id . "&quot;)\'>Analyze Using StdDev</a><a href=\'javascript:dryRunVariance(&quot;" . $local_graph_id . "&quot;)\'>Analyze Using Variance</a></div>', FIX, [this, 18, -18], STICKY, true, BORDERWIDTH, 0, BGCOLOR, '#F1F1F1', CLICKCLOSE, true, CLICKSTICKY, true, SHADOW, true, PADDING, 0, TITLE, 'Spike Killer Menu', TITLEFONTSIZE, '6pt', TITLEBGCOLOR, '#6D88AD', DURATION, -10000";
		print "<img border='0' id='sk" . $local_graph_id . "' style='padding:3px;' src='" . $config['url_path'] . "plugins/lookalikee/images/lookalikee.gif' onMouseOver=\"Tip($tip)\" onMouseOut='UnTip()'><br>";
	}
}

function lookalikee_authorized() {
	if (sizeof(db_fetch_assoc("SELECT realm_id
		FROM user_auth_realm
		WHERE user_id=" . $_SESSION["sess_user_id"] . "
		AND realm_id IN (1043)"))) {
		return true;
	}else{
		return false;
	}
}
?>
