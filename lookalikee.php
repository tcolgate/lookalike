<?php

chdir('../../');
include_once("./include/auth.php");

include_once($config["base_path"]."/include/top_graph_header.php");

$local_graph_id = $_GET['local_graph_id'];
$graph_start = $_GET['graph_start'];
$graph_end = $_GET['graph_end'];
$rrdid = $_GET['rrdid'];
$dsname = $_GET['dsname'];

print("$graph_start $graph_end<br>");

include_once($config["base_path"]."/include/bottom_footer.php");

?>
