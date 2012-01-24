<?php

chdir('../../');
include_once("./include/auth.php");
include_once("./lib/api_graph.php");
include_once($config["base_path"]."/include/top_graph_header.php");

$lklkbin = read_config_option("lookalikee_binary");
$local_graph_id = $_GET['local_graph_id'];
$graph_start = $_GET['graph_start'];
$graph_end = $_GET['graph_end'];
/* required for zoom out function */
if ($graph_start == $graph_end) {
	$graph_start--;
}
$rrdid = $_GET['rrdid'];
$dsname = $_GET['dsname'];
$rrdbase = $config["rra_path"];


$rrdpath = get_data_source_path($rrdid, true);
$cmd = "$lklkbin $rrdpath $dsname $graph_start $graph_end";
exec($cmd, $output);

foreach ($output as $line){
  preg_match("/Match:([^:]*):.*/",$line,$matches);
  if($matches){
    $matchedrrd = $matches[1] ;
    $data_source_path = str_replace($rrdbase,'<path_rra>', $matchedrrd);
    $dsquery = "select local_data_id from data_template_data where data_source_path = \"$data_source_path\"";
    $dsid = db_fetch_cell($dsquery);
    print "matched $rrdbase $data_source_path $dsid<br>";
    $matchedgraphs = api_get_graphs_from_datasource($dsid);

    print "<table>";
    foreach(array_keys($matchedgraphs) as $matchedgraph){

	$graph = db_fetch_row("select
		graph_templates_graph.height,
		graph_templates_graph.width
		from graph_templates_graph
		where graph_templates_graph.local_graph_id=" . $matchedgraph);

	$graph_height = $graph["height"];
	$graph_width = $graph["width"];
        $graph_title = get_graph_title($matchedgraph);

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
	}else {
		$title_font_size = 0;
	}

	?>
	<tr>
		<td align='center'>
			<table width='1' cellpadding='0'>
				<tr>
					<td>
						<img src='<?php print htmlspecialchars($config['url_path'] . "graph_image.php?graph_nolegend=true&local_graph_id=" . $matchedgraph . "&graph_start=" . $graph_start . "&graph_end=" . $graph_end . "&graph_height=" . $graph_height . "&graph_width=" . $graph_width . "&title_font_size=" . $title_font_size);?>' border='0' alt='<?php print htmlspecialchars($graph_title);?>'>
					</td>
				</tr>
			</table>
		</td>
	</tr>
	<?php
    };
    print "</table>";

  } else {
    print "$line<br>";
  };
};


include_once($config["base_path"]."/include/bottom_footer.php");

?>
