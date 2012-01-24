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

	$files = array('index.php', 'plugins.php', 'lookalikee.php');
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
		'version'  => '0.1',
		'longname' => 'Lookalikee for Cacti Graphs',
		'author'   => 'Tristan Colgate-McFarlane',
		'homepage' => 'https://github.com/tcolgate/lookalikee',
		'email'    => '',
		'url'      => ''
	);
}

function lookalikee_config_settings () {
	global $tabs, $settings;

	/* check for an upgrade */
	plugin_lookalikee_check_config();

	$tabs["misc"] = "Misc";

	$temp = array(
		"lookalikee_header" => array(
			"friendly_name" => "Lookalikee Settings",
			"method" => "spacer",
			),
#		"lookalikee_method" => array(
#			"friendly_name" => "Removal Method",
#			"description" => "There are two removal methods.  The first, Standard Deviation, will remove any
#			sample that is X number of standard deviations away from the average of samples.  The second method,
#			Variance, will remove any sample that is X% more than the Variance average.  The Variance method takes
#			into account a certain number of 'outliers'.  Those are exceptinal samples, like the spike, that need
#			to be excluded from the Variance Average calculation.",
#			"method" => "drop_array",
#			"default" => "1",
#			"array" => array(1 => "Standard Deviation", 2=> "Variance Based w/Outliers Removed")
#			),
		"lookalikee_binary" => array(
			"friendly_name" => "Lookalikee executable",
			"description" => "This gives the size of the gnerated PAA hash",
			"method" => "filepath",
			"default" => "/var/www/cacti/plugins/lookalikee/bin/lookalikee",
			"max_length" => "255",
			),
		"lookalikee_filtersize" => array(
			"friendly_name" => "Filter Size",
			"description" => "This gives the size of the gnerated PAA hash",
			"method" => "textbox",
			"default" => "10",
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
}

function lookalikee_config_arrays () {
	global $user_auth_realms, $user_auth_realm_filenames, $menu, $messages;

	$user_auth_realm_filenames['lookalikee.php'] = 2077;
	$user_auth_realms[2077]='Plugin -> Find visually similar graphs';
}

function lookalikee_graph_button($data) {
	global $config;

	if (lookalikee_authorized()){
		$local_graph_id = $data[1]['local_graph_id'];
		$rraid = $data[1]['rra'];

                if(isset($_GET['graph_end'])){
		  $graph_end = $_GET['graph_end'];
		} else {
                  if($rraid == 0){
                	$graph_end = get_current_graph_end();
                  } else {
			$graph_end = time();
                  };
		};

                if(isset($_GET['graph_start'])){
		  $graph_start = $_GET['graph_start'];
		} else {
                  if($rraid == 0){
                	$graph_start = get_current_graph_start();
                  } else {
			$graph_start = $graph_end - db_fetch_cell("SELECT timespan FROM rra WHERE id=" . $rraid);
                  };
		};

		/* required for zoom out function */
		if ($graph_start == $graph_end) {
		        $graph_start--;
		}

		$dss = db_fetch_assoc("
				SELECT DISTINCT data_template_rrd.data_source_name AS dsname,
						data_template_rrd.local_data_id AS rrdid,
						data_template_data.data_source_path AS rrdpath
					FROM
						graph_templates_graph
						INNER JOIN graph_templates_item
							ON graph_templates_graph.local_graph_id=graph_templates_item.local_graph_id
						INNER JOIN data_template_rrd
							ON graph_templates_item.task_item_id=data_template_rrd.id
						INNER JOIN data_template_data
							ON data_template_rrd.local_data_id=data_template_rrd.local_data_id
						WHERE graph_templates_graph.local_graph_id = $local_graph_id
							AND data_template_data.local_data_id = data_template_rrd.local_data_id
			");
                #var_dump($dss);

                $menu = "";
                foreach ($dss as $ds){  
 			$menu = $menu . "<a href=" . $config['url_path'] . "plugins/lookalikee/lookalikee.php?local_graph_id=" . $local_graph_id . "&graph_start=" . $graph_start . "&graph_end=" . $graph_end . "&rrdid=" . $ds['rrdid'] . "&dsname=" . $ds['dsname'] . ">" . $ds['dsname'] . "</a><br>";
		};
                $tip = "'<div class=\'lookalikee\'>" . $menu . "</div>', FIX, [this, 18, -18], STICKY, true, BORDERWIDTH, 0, BGCOLOR, '#F1F1F1', CLICKCLOSE, true, CLICKSTICKY, true, SHADOW, true, PADDING, 0, TITLE, 'Find Similar Graphs', TITLEFONTSIZE, '6pt', TITLEBGCOLOR, '#6D88AD', DURATION, -10000";

		print "<img alt='Find graphs similar to...' border='0' id='lklk" . $local_graph_id . "' style='padding:3px;' src='" . $config['url_path'] . "plugins/lookalikee/lookalikee.gif' onMouseOver=\"Tip($tip)\" onMouseOut='UnTip()'><br>";

	}
}

function lookalikee_authorized() {
	if (sizeof(db_fetch_assoc("SELECT realm_id
		FROM user_auth_realm
		WHERE user_id=" . $_SESSION["sess_user_id"] . "
		AND realm_id IN (2077)"))) {
		return true;
	}else{
		return false;
	}
}
?>
