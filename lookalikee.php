<?php

chdir('../../');
include_once("./include/auth.php");

include_once($config["base_path"]."/include/top_graph_header.php");

$local_graph_id = $_GET['local_graph_id'];
$rras = get_associated_rras($local_graph_id);
var_dump($local_graph_id);
print("<br>");


$dss = db_fetch_assoc("
                SELECT DISTINCT data_template_rrd.data_source_name AS dsname,
                                       data_template_rrd.local_data_id AS dsid,
                                       data_template_data.data_source_path AS dspath
                FROM 
                  graph_templates_graph
                  INNER JOIN graph_templates_item
                    ON graph_templates_graph.local_graph_id=graph_templates_item.local_graph_id
                  INNER JOIN data_template_rrd
                    ON graph_templates_item.task_item_id=data_template_rrd.id
                  JOIN data_template_data
                    ON data_template_rrd.local_data_id=data_template_rrd.local_data_id
                WHERE graph_templates_graph.local_graph_id = $local_graph_id 
                  AND data_template_data.local_data_id = data_template_rrd.local_data_id
");


var_dump($dss);

include_once($config["base_path"]."/include/bottom_footer.php");

?>
