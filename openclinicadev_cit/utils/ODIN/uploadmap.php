<?php

require_once 'includes/connection.inc.php';
require_once 'includes/html_top.inc.php';
is_logged_in();
require($_SESSION['settingsfile']);

if (count($_FILES)!=0) {

//add the session id to the mapfile's name
$target_dir = 'map/'.htmlspecialchars($_SESSION['user_name']).'/';
$target_dir = $target_dir . basename( "map_".$_SESSION['importid']."_.csv");
$uploadOk=1;

echo '<br/>';
?>




<?php


// Check if $uploadOk is set to 0 by an error
if ($uploadOk == 0) {
    echo "<p>Sorry, your file was not uploaded.";
    echo '<a href="uploadmap.php" class="easyui-linkbutton" data-options="iconCls:\'icon-next\'">Go back</a>';
// if everything is ok, try to upload file
} else {
    if (move_uploaded_file($_FILES["uploadFile"]["tmp_name"], $target_dir)) {
        echo "<br/>The file ". basename( $_FILES["uploadFile"]["name"]). " has been uploaded.<br/>";
        echo '<a href="crfdef.php" class="easyui-linkbutton" data-options="iconCls:\'icon-next\'">Continue to CRF definitions</a>';
    } else {
        echo "<p>Sorry, there was an error uploading your file.<br/>";
            echo '<a href="uploadmap.php" class="easyui-linkbutton" data-options="iconCls:\'icon-add\'">You can try again</a> or <a href="crfdef.php" class="easyui-linkbutton" data-options="iconCls:\'icon-next\'">Skip this step and continue to CRF definitions</a></p>';
    }
}
}
else{
?> 
 
<form action="uploadmap.php" method="post" enctype="multipart/form-data">

<table id="uploader">

<tr><td>Please choose a mapping file:</td><td> <input type="file" name="uploadFile"></td></tr>

  <tr><td><input type="submit" value="Upload File"></td></tr></table>
  <p><a href="crfdef.php" class="easyui-linkbutton" data-options="iconCls:'icon-next'">Skip this step and continue to CRF definitions</a></p>
</form>  
<?php 
}//end of else
require_once 'includes/html_bottom.inc.php';
?> 
 