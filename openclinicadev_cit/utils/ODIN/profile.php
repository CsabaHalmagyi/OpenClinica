<?php
require_once 'includes/connection.inc.php';
require_once 'includes/html_top.inc.php';

is_logged_in();
require($_SESSION['settingsfile']);

echo '<br/>';
echo '<table>';
echo '<tr><td>User</td><td><span style="font-weight: bold;">'.htmlspecialchars($_SESSION['user_name']).'</span></td></tr>';
echo '<tr><td>First name</td><td><span style="font-weight: bold;">'.htmlspecialchars($_SESSION['first_name']).'</span></td></tr>';
echo '<tr><td>Last name</td><td><span style="font-weight: bold;">'.htmlspecialchars($_SESSION['last_name']).'</span></td></tr>';
echo '<tr><td>Email</td><td><span style="font-weight: bold;">'.htmlspecialchars($_SESSION['email']).'</span></td></tr>';
echo '<tr><td>Current Import ID</td><td><span style="font-weight: bold;">'.$_SESSION['importid'].'</span></td></tr>';

echo '</table>';
echo '<br/><br/>';

echo '<table>';
echo '<tr><td>Mapping file name</td><td>Date created</td><td>Time created</td><td>XML Data file</td></tr>';
$path = 'map/'.htmlspecialchars($_SESSION['user_name']).'/';
$dataFilePath = 'savedxmls/'.htmlspecialchars($_SESSION['user_name']).'/';
$dir = opendir($path);
$list = array();
$importID='';
while($file = readdir($dir)){
	if ($file != '.' and $file != '..'){
		// add the filename, to be sure not to
		// overwrite a array key
		$ctime = filectime($path . $file) . ',' . $file;
		$list[$ctime] = $file;
	}
}
closedir($dir);
krsort($list);

//var_dump($list);
foreach ($list as $mfile){
	$importID=explode('_',$mfile);
	echo '<tr><td><button type="button" onclick="location.href=\'download.php?type=m&id='.$importID[1].'\'">'.$mfile.'</button></td><td>'.date ("Y-M-d", filemtime($path.$mfile)).'</td>
					<td>'.date ("H:i", filemtime($path.$mfile)).'</td><td>';

	$dataFileName = 'import_'.$importID[1].'.xml';
	if (file_exists($dataFilePath.$dataFileName)){
		echo '<button type="button" onclick="location.href=\'download.php?type=x&id='.$importID[1].'\'">'.$dataFileName.'</button>';
	}
	echo '</td></tr>';
}

echo '</table>';
echo '<br/><a href="index.php" class="easyui-linkbutton" data-options="iconCls:\'icon-back\'">Go back</a>';
?>

<?php 
echo '<br/><br/>';
require_once 'includes/html_bottom.inc.php';
?>
