<?php
/*
 ex: set tabstop=4 shiftwidth=4 autoindent:
 +-------------------------------------------------------------------------+
 | Copyright (C) 2011 - 2012 Tristan Colgate-McFarlane                     |
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
 | https://github.com/tcolgate/lookalike                                   |
 +-------------------------------------------------------------------------+
*/

function plugin_lookalike_install() {
	api_plugin_register_hook('lookalike', 'config_arrays',            'lookalike_config_arrays',   "setup.php");
	api_plugin_register_hook('lookalike', 'config_settings',          'lookalike_config_settings', "setup.php");
	api_plugin_register_hook('lookalike', 'graph_buttons',            'lookalike_graph_button',    "setup.php");
	api_plugin_register_hook('lookalike', 'graph_buttons_thumbnails', 'lookalike_graph_button',    "setup.php");
	api_plugin_register_hook('lookalike', 'page_head',                'lookalike_page_head',       "setup.php");
	api_plugin_register_hook('lookalike', 'top_graph_header_tabs',    'lookalike_top_graph_header_tabs', "setup.php");
        api_plugin_register_hook('lookalike', 'draw_navigation_text',     'lookalike_draw_navigation_text',  'setup.php');

	lookalike_setup_table_new ();
}

function plugin_lookalike_uninstall () {
	/* Do any extra Uninstall stuff here */
}

function plugin_lookalike_check_config () {
	/* Here we will check to ensure everything is configured */
	lookalike_check_upgrade();
	return true;
}

function plugin_lookalike_upgrade () {
	/* Here we will upgrade to the newest version */
	lookalike_check_upgrade();
	return false;
}

function plugin_lookalike_version () {
	return lookalike_version();
}

function lookalike_check_upgrade () {
	global $config;

	$files = array('index.php', 'plugins.php', 'lookalike.php');
	if (isset($_SERVER['PHP_SELF']) && !in_array(basename($_SERVER['PHP_SELF']), $files)) {
		return;
	}

	$current = plugin_lookalike_version();
	$current = $current['version'];
	$old     = db_fetch_row("SELECT * FROM plugin_config WHERE directory='lookalike'");
	if (sizeof($old) && $current != $old["version"]) {
		/* if the plugin is installed and/or active */
		if ($old["status"] == 1 || $old["status"] == 4) {
			/* re-register the hooks */
			plugin_lookalike_install();

			/* perform a database upgrade */
			lookalike_database_upgrade();
		}

		/* update the plugin information */
		$info = plugin_lookalike_version();
		$id   = db_fetch_cell("SELECT id FROM plugin_config WHERE directory='lookalike'");
		db_execute("UPDATE plugin_config
			SET name='" . $info["longname"] . "',
			author='"   . $info["author"]   . "',
			webpage='"  . $info["homepage"] . "',
			version='"  . $info["version"]  . "'
			WHERE id='$id'");
	}
}

function lookalike_database_upgrade () {
}

function lookalike_check_dependencies() {
	global $plugins, $config;

	return true;
}

function lookalike_setup_table_new () {
}

function lookalike_draw_navigation_text ($nav) {
        $nav['lookalike.php:'] = array('title' => 'Lookalike', 'mapping' => '', 'url' => 'lookalike.php', 'level' => '1');
	return $nav;
}

function lookalike_top_graph_header_tabs () {
	global $config;
	print '<script language="JavaScript" type="text/javascript" src="' . $config['url_path'] . 'plugins/lookalike/wz_tooltip.js"></script>';
	echo '<div class="lookalike" id="lookalike" style="width:auto;overflow:hidden;z-index:1010;visibility:hidden;position:absolute;top:0px;left:0px;"></div>';
}

function lookalike_version () {
	return array(
		'name'     => 'lookalike',
		'version'  => '0.1',
		'longname' => 'Lookalike for Cacti Graphs',
		'author'   => 'Tristan Colgate-McFarlane',
		'homepage' => 'https://github.com/tcolgate/lookalike',
		'email'    => '',
		'url'      => ''
	);
}

function lookalike_config_settings () {
	global $tabs, $settings;

	/* check for an upgrade */
	plugin_lookalike_check_config();

	$tabs["misc"] = "Misc";

	$temp = array(
		"lookalike_header" => array(
			"friendly_name" => "Lookalike Settings",
			"method" => "spacer",
			),
		"lookalike_binary" => array(
			"friendly_name" => "Lookalike executable",
			"description" => "This gives the size of the gnerated PAA hash",
			"method" => "filepath",
			"default" => "/var/www/cacti/plugins/lookalike/bin/lookalike",
			"max_length" => "255",
			),
		"lookalike_rrdglob" => array(
			"friendly_name" => "RRD Pattern",
			"description" => "A glob describing the RRDs to search",
			"method" => "textbox",
          		"default" => "<path_rra>/[0-9]*/*.rrd",
			"max_length" => "255",
			"size" => "60"
			),
		"lookalike_filtersize" => array(
			"friendly_name" => "Filter Size",
			"description" => "This gives the size of the gnerated PAA hash",
			"method" => "textbox",
			"default" => "10",
			"max_length" => "255",
			"size" => "4"
			),
		);

	if (isset($settings["misc"])) {
		$settings["misc"] = array_merge($settings["misc"], $temp);
	}else {
		$settings["misc"] = $temp;
	}
}

function lookalike_page_head () {
	global $config;

}

function lookalike_config_arrays () {
	global $user_auth_realms, $user_auth_realm_filenames, $menu, $messages;

	$user_auth_realm_filenames['lookalike.php'] = 2077;
	$user_auth_realms[2077]='Plugin -> Find visually similar graphs';
}

function lookalike_graph_button($data) {
	global $config;

	if (lookalike_authorized()){
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
 			$menu = $menu . "<a href=" . $config['url_path'] . "plugins/lookalike/lookalike.php?local_graph_id=" . $local_graph_id . "&graph_start=" . $graph_start . "&graph_end=" . $graph_end . "&rrdid=" . $ds['rrdid'] . "&dsname=" . $ds['dsname'] . ">" . $ds['dsname'] . "</a><br>";
		};
                $tip = "'<div class=\'lookalike\'>" . $menu . "</div>', FIX, [this, 18, -18], STICKY, true, BORDERWIDTH, 0, BGCOLOR, '#F1F1F1', CLICKCLOSE, true, CLICKSTICKY, true, SHADOW, true, PADDING, 0, TITLE, 'Find Similar Graphs', TITLEFONTSIZE, '6pt', TITLEBGCOLOR, '#6D88AD', DURATION, -10000";

		print "<img alt='Find graphs similar to...' border='0' id='lklk" . $local_graph_id . "' style='padding:3px;' src='" . $config['url_path'] . "plugins/lookalike/lookalike.png' onMouseOver=\"Tip($tip)\" onMouseOut='UnTip()'><br>";

	}
}

function lookalike_authorized() {
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
