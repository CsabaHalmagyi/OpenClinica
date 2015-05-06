<?php
require_once 'includes/connection.inc.php';
require_once 'includes/html_top.inc.php';
is_logged_in();
require($_SESSION['settingsfile']);
?>


<table>
<tr><td>Data file template</td><td>Mapping file template</td></tr>
<tr><td><a href="templates/odin_data_template.csv">(Right click, save as)</a></td><td><a href="templates/odin_mapping_template.csv">(Right click, save as)</a></td></tr>

</table>



<?php 
echo '<p><a href="index.php" class="easyui-linkbutton" data-options="iconCls:\'icon-back\'">Go back</a></p>';

require_once 'includes/html_bottom.inc.php';
?>