
lookalike is a cacti plugin for searching for graphs that 
look similar to a graph that you are currently viewing.
It is loosely based on the techniques used in the SAX time
series indexing suite. The plugin itself is based on code
from the Spike Kill plugin by the Cacti Group, the graph icon 
is adapted from a thold plugin image by Jimmy Connor.

This plugin makes use of wz_toolip.js which by Walter Zorn.

Installation
------------

1. Copy the plugin into your cacti plugins folder.

2. You will need to build the lookalike binary. This 
  requires, librrd developement libraries, and the gnu
  getopt headers.

  # yum install glibc-headers rrdtool-devel
  # cd bin
  # make

3. You will need to ensure that php will have permissions
  to execute the binary. The location of the binary can
  be changed and is configurable in the plugin's settings.

4. Install and activate the plugin through cacti plugin
  management.

5. Check the plugin setting in Console->Settings->Misc. The
  RRD Path will need to match all the RRD files you want to 
  search. The most common settings will be...
  - <rra_path>/*.rrd
  - <rra_path>/[0-9]*/*.rrd

6. Allow users to use the plugin via cacti user management.

ScreenShots
-----------

![Image of Selection](screenshot1.png)

![Image of Result](screenshot2.png)

Support
-------

  Please visit the homepage:
  https://github.com/tcolgate/lookalike
