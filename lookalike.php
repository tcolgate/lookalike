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


chdir('../../');
include_once("./include/auth.php");
include_once("./lib/functions.php");
include_once("./lib/api_graph.php");
include_once($config["base_path"]."/include/top_graph_header.php");

$rrdid = $_GET['rrdid'];
$dsname = $_GET['dsname'];
$rrdbase = $config["rra_path"];

$lklkbin = read_config_option("lookalike_binary");
$lklkrrdglobs = read_config_option("lookalike_rrdglobs");
$lklkrrdglobs = str_replace('<path_rra>',$rrdbase, $lklkrrdglob);

$lklkpaasize = read_config_option("lookalike_filtersize");
input_validate_input_number($lklkpaasize);

$local_graph_id = $_GET['local_graph_id'];
input_validate_input_number($local_graph_id);

$graph_start = $_GET['graph_start'];
input_validate_input_number($graph_start);
$graph_end = $_GET['graph_end'];
input_validate_input_number($graph_end);

$graph_width = read_graph_config_option("default_width");
$graph_height = read_graph_config_option("default_height");
$cols = read_graph_config_option("num_columns");

$title_font_size = 0;
if ((read_config_option("rrdtool_version")) != "rrd-1.0.x") {
	if (read_graph_config_option("title_font") == "") {
		if (read_config_option("title_font") == "") {
			$title_font_size = 10;
		}else {
			$title_font_size = read_config_option("title_size");
		}
	}else {
		$title_font_size = read_graph_config_option("title_size");
	}
};

/* required for zoom out function */
if ($graph_start == $graph_end) {
	$graph_start--;
}

$rrdpath = get_data_source_path($rrdid, true);
$lklkglobopts = explode("\n",$lklkrrdglobs);
$lklkglobopts = implode(" -g ",$lklkglobopts);
$cmd = escapeshellcmd("$lklkbin -g $lklkglobopts -s $lklkpaasize $rrdpath $dsname $graph_start $graph_end");
print_r "$cmd <br>";
exec($cmd, $output);

$currcol = 1;
print "<table>";
$done = array( );
foreach ($output as $line){
  preg_match("/Match:([^:]*):.*/",$line,$matches);
  if($matches){
    $matchedrrd = $matches[1] ;
    $data_source_path = str_replace($rrdbase,'<path_rra>', $matchedrrd);
    $dsquery = "select local_data_id from data_template_data where data_source_path = \"$data_source_path\"";
    $dsid = db_fetch_cell($dsquery);
    $matchedgraphs = api_get_graphs_from_datasource($dsid);

    foreach(array_keys($matchedgraphs) as $matchedgraph){
        if(isset($done[$matchedgraph])) { break ;};

	$graph = db_fetch_row("select
		graph_templates_graph.height,
		graph_templates_graph.width
		from graph_templates_graph
		where graph_templates_graph.local_graph_id=" . $matchedgraph);

        $graph_title = get_graph_title($matchedgraph);

	if($currcol == 0) {
          print "<tr>";
        };
	?>
		<td align='center'>
			<table width='1' cellpadding='0'>
				<tr>
					<td>
                                                <a href='<?php print $config['url_path'] . "graph.php?action=view&local_graph_id=" . $matchedgraph ?>'>
						<img src='<?php print htmlspecialchars($config['url_path'] . "graph_image.php?graph_nolegend=true&local_graph_id=" . $matchedgraph . "&graph_start=" . $graph_start . "&graph_end=" . $graph_end . "&graph_height=" . $graph_height . "&graph_width=" . $graph_width . "&title_font_size=" . $title_font_size);?>' border='0' alt='<?php print htmlspecialchars($graph_title);?>'>
						</a>
					</td>
				</tr>
				<tr>
					<td>
                                                <center><?php print $graph_title ?></center>
					</td>
				</tr>
			</table>
		</td>
      <?php
      $done[$matchedgraph] = 1;
      if($currcol == $cols) {
        print "</tr>";
        $currcol = 1;
      } else {
        $currcol ++;
      };
    };
  }; 
};
print "</table>";

include_once($config["base_path"]."/include/bottom_footer.php");

?>
