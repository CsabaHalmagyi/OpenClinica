<?php
require_once 'includes/connection.inc.php';
require_once 'includes/html_top.inc.php';
is_logged_in();
require($_SESSION['settingsfile']);
?>


<table>
<tr><td>Data file template</td><td>Mapping file template</td></tr>
<tr><td><button type="button" onclick="location.href='download.php?type=dtemp&id=1'">Data template</button></td><td><button type="button" onclick="location.href='download.php?type=mtemp&id=1'">Mapping template</button></td></tr>

</table>



<?php 
echo '<p><a href="index.php" class="easyui-linkbutton" data-options="iconCls:\'icon-back\'">Go back</a></p>';

require_once 'includes/html_bottom.inc.php';
?>