<?php
 // The connection string for the MongoDB replica set. This should be the dns you set up with the replica set.

 // Connect to the MongoDb replica set. Note: "rs" is the default name for the MongoDb replica set if you used the installer
 try {
     /* List of host options - Linux, Windows, Localhost respectively */
     if (strtolower($_GET["host"]) == "localhost"){
        $host = "localhost:27018"; //Localhost
        $loc = "host=localhost";
     }
     elseif (strtolower($_GET["host"]) == "windows"){
        $host = "mongodb://168.62.198.132:27017"; //Windows
        $loc = "host=windows";
     }
     else {
        $host = "mongolinuxdbrs.cloudapp.net:27020"; //Linux
        $loc = "";
     }

     $mongo = new Mongo($host);
     $db = $mongo->photodb;
     $collection = new MongoCollection($db, 'photos');
     $gridfs = $db->getGridFS();

     $cursor = $gridfs->find();

     if ($_GET['rem']){
         $filename = urldecode($_GET['rem']);
         $file = $gridfs->findOne($filename);             // Find file in GridFS
         $id = $file->file['_id'];                    // Get the files ID
         $gridfs->delete($id);
     }
 } catch (Exception $e) {
     echo 'Caught exception: ',  $e->getMessage(), "\n";
 }

 // This will enable us to read from a slave if, for some reason, the application cannot reach the master if we need to.
  //$mongo->setSlaveOkay(true);  
    
    // If we have post data add the new song to the database
    try {
     if ($_POST){
         // This demo only allows images to be uploaded
         if ((($_FILES["file"]["type"] == "image/gif")
            || ($_FILES["file"]["type"] == "image/jpeg")
            || ($_FILES["file"]["type"] == "image/pjpeg"))
            )
              {
              if ($_FILES["file"]["error"] > 0)
                {
                echo "Error: " . $_FILES["file"]["error"] . "<br />";
                }
              else
                {
                $sTitle = $_POST['title'];
                $sSubTitle = strtok($_FILES["file"]["name"], '.');
                $sDescription = $_POST['description'];
                $sGroup = $_POST['group'];
                $sFile = "\"_attachments\": {\"" . $_FILES["file"]["name"] . "\": { \"content_type\": \"image/jpg\",\"data\":\"$image\" }}";
                $sID = str_replace(" ", "_", $sTitle); //We will use the title as id (converting spaces to '_')

                $sSend = $gridfs->storeUpload('file', array("metadata" => array("title" => $sTitle, "subtitle" => $sSubTitle, "description" => $sDescription, "group" => $sGroup, "type" => "image")));
                }
              }
            else
              {
              echo "Invalid file";
             }
             $sImage = array();
        
     }
    } catch (Exception $e) {
    echo 'Caught exception: ',  $e->getMessage(), "\n";
}

 function getAll($group){
    global $cursor, $mongo, $gridfs;
    $grp_images = "";
    $grp_array = "";
    $i = 0;
    foreach ($cursor as $obj) {                   // iterate through the results

    $image = $gridfs->findOne($obj->getFilename());

    $metadata = $image->file['metadata'];

    $encodedData = base64_encode($image->getBytes());
        if ($metadata['group'] == $group){
            echo "<div style=\"float:left;width:120px; display:inline;cursor:pointer;padding-right:20px;\"><img src=\"data:image/png;base64,{$encodedData}\" width=100 onclick=\"swapImg" . $group . "('image" . $group . $i . "');\"></div><div style=\"float:left;display:inline;padding-right:20px;\">" . $metadata['title'] . "</div><div style=\"float:left;padding-top:10px;\">" . $metadata['description'] . "<br /><a href=\"" . $_SERVER['PHP_SELF'] . "?rem=" . urlencode($obj->getFilename()) . "&" . $loc . "\" class=small>Remove Image</a><br style=\"clear:both\" /><hr \>";
            if ($i < 1){
                $grp_images = "<div id=\"image" . $group . $i . "\" style=\"DISPLAY: block;width:400px;\"><img src=\"data:image/png;base64,{$encodedData}\" style=\"max-width:400px; max-height:400px\"></div>\n";
                $grp_array = "\"image" . $group . $i . "\"";
            }
            else {
                $grp_images = $grp_images . "<div id=\"image" . $group . $i . "\" style=\"DISPLAY: none;width:400px;\"><img src=\"data:image/png;base64,{$encodedData}\" style=\"max-width:400px; max-height:400px\"></div>\n";
                $grp_array = $grp_array . ", \"image" . $group . $i . "\"";
            }
            $i++;
        }
    }
    echo "</td></tr></table></td><td style=\"width:500px;padding-right:100px;\">\n" . $grp_images . "</td></tr></table>\n";
    echo "<script>
            function swapImg" . $group . "(item) {
                var ary = new Array($grp_array);
                var i = 0;
                for(i = 0; i < ary.length; i++) {
                    if(ary[i] == item) {
                        document.getElementById(ary[i]).style.display = 'block';
                    }
                    else {
                        document.getElementById(ary[i]).style.display = 'none';
                    }
                }
            }
        </script>";
 }
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8" />
        <title>MongoDB Photo Album</title>
        <STYLE TYPE="text/css"><!--
        body	{
            border-top: 5px solid #2D0D55;
	        background-color: #EAEFF2;
	        margin: 0;
	        padding: 0;
	        font-family: verdana, arial, sans-serif;
	        padding-bottom: 25px;
                scrollbar-arrow-color:#FFFFFF;
                scrollbar-base-color:#6D8693;
                scrollbar-shadow-color:#F3F3F3;
                scrollbar-face-color:#697278;
                scrollbar-highlight-color:#F3F3F3;
                scrollbar-dark-shadow-color:white;
                scrollbar-3d-light-color:#9BAAC1;
	        }

        p, ol, li {
        font-family: verdana, arial, sans-serif;
        font-weight: normal;
        }

        td, tr {
        font-family: verdana, arial, sans-serif;
        font-weight: normal;
        padding: 10px
        }
        
        h1 {font-family: brush script mt, verdana, arial, sans-serif;}
        .tabs {font-family: cursive, verdana, arial, sans-serif;}
        .small {font-size:8px; margin-top: 5px;}

        #div.content {max-width: 600px;}
        
        .paper {background-image:url('book_background2.jpg');background-color:#cccccc; background-size: 100%;} 

        .menutabactive {
            background: #1e5799; /* Old browsers */
            /* IE9 SVG, needs conditional override of 'filter' to 'none' */
            background: url(data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiA/Pgo8c3ZnIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyIgd2lkdGg9IjEwMCUiIGhlaWdodD0iMTAwJSIgdmlld0JveD0iMCAwIDEgMSIgcHJlc2VydmVBc3BlY3RSYXRpbz0ibm9uZSI+CiAgPGxpbmVhckdyYWRpZW50IGlkPSJncmFkLXVjZ2ctZ2VuZXJhdGVkIiBncmFkaWVudFVuaXRzPSJ1c2VyU3BhY2VPblVzZSIgeDE9IjAlIiB5MT0iMCUiIHgyPSIwJSIgeTI9IjEwMCUiPgogICAgPHN0b3Agb2Zmc2V0PSIwJSIgc3RvcC1jb2xvcj0iIzFlNTc5OSIgc3RvcC1vcGFjaXR5PSIxIi8+CiAgICA8c3RvcCBvZmZzZXQ9IjEwMCUiIHN0b3AtY29sb3I9IiM3ZGI5ZTgiIHN0b3Atb3BhY2l0eT0iMSIvPgogIDwvbGluZWFyR3JhZGllbnQ+CiAgPHJlY3QgeD0iMCIgeT0iMCIgd2lkdGg9IjEiIGhlaWdodD0iMSIgZmlsbD0idXJsKCNncmFkLXVjZ2ctZ2VuZXJhdGVkKSIgLz4KPC9zdmc+);
            background: -moz-linear-gradient(top,  #1e5799 0%, #7db9e8 100%); /* FF3.6+ */
            background: -webkit-gradient(linear, left top, left bottom, color-stop(0%,#1e5799), color-stop(100%,#7db9e8)); /* Chrome,Safari4+ */
            background: -webkit-linear-gradient(top,  #1e5799 0%,#7db9e8 100%); /* Chrome10+,Safari5.1+ */
            background: -o-linear-gradient(top,  #1e5799 0%,#7db9e8 100%); /* Opera 11.10+ */
            background: -ms-linear-gradient(top,  #1e5799 0%,#7db9e8 100%); /* IE10+ */
            background: linear-gradient(to bottom,  #1e5799 0%,#7db9e8 100%); /* W3C */
            filter: progid:DXImageTransform.Microsoft.gradient( startColorstr='#1e5799', endColorstr='#7db9e8',GradientType=0 ); /* IE6-8 */

        }
            
        .menutab {
            background: rgb(89,106,114); /* Old browsers */
            /* IE9 SVG, needs conditional override of 'filter' to 'none' */
            background: url(data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiA/Pgo8c3ZnIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyIgd2lkdGg9IjEwMCUiIGhlaWdodD0iMTAwJSIgdmlld0JveD0iMCAwIDEgMSIgcHJlc2VydmVBc3BlY3RSYXRpbz0ibm9uZSI+CiAgPGxpbmVhckdyYWRpZW50IGlkPSJncmFkLXVjZ2ctZ2VuZXJhdGVkIiBncmFkaWVudFVuaXRzPSJ1c2VyU3BhY2VPblVzZSIgeDE9IjAlIiB5MT0iMCUiIHgyPSIwJSIgeTI9IjEwMCUiPgogICAgPHN0b3Agb2Zmc2V0PSIwJSIgc3RvcC1jb2xvcj0iIzU5NmE3MiIgc3RvcC1vcGFjaXR5PSIxIi8+CiAgICA8c3RvcCBvZmZzZXQ9IjEwMCUiIHN0b3AtY29sb3I9IiNjZWRjZTciIHN0b3Atb3BhY2l0eT0iMSIvPgogIDwvbGluZWFyR3JhZGllbnQ+CiAgPHJlY3QgeD0iMCIgeT0iMCIgd2lkdGg9IjEiIGhlaWdodD0iMSIgZmlsbD0idXJsKCNncmFkLXVjZ2ctZ2VuZXJhdGVkKSIgLz4KPC9zdmc+);
            background: -moz-linear-gradient(top, rgba(89,106,114,1) 0%, rgba(206,220,231,1) 100%); /* FF3.6+ */
            background: -webkit-gradient(linear, left top, left bottom, color-stop(0%,rgba(89,106,114,1)), color-stop(100%,rgba(206,220,231,1))); /* Chrome,Safari4+ */
            background: -webkit-linear-gradient(top, rgba(89,106,114,1) 0%,rgba(206,220,231,1) 100%); /* Chrome10+,Safari5.1+ */
            background: -o-linear-gradient(top, rgba(89,106,114,1) 0%,rgba(206,220,231,1) 100%); /* Opera 11.10+ */
            background: -ms-linear-gradient(top, rgba(89,106,114,1) 0%,rgba(206,220,231,1) 100%); /* IE10+ */
            background: linear-gradient(to bottom, rgba(89,106,114,1) 0%,rgba(206,220,231,1) 100%); /* W3C */
            filter: progid:DXImageTransform.Microsoft.gradient( startColorstr='#596a72', endColorstr='#cedce7',GradientType=0 ); /* IE6-8 */
        }
            
             <!--[if gte IE 9]>
                .gradient {filter: none;}
            <![endif]-->
        //--></STYLE>
      
    </head>
    <body>
        <script>
            var ary = new Array("Tab 1", "Tab 2");
            var i = 0;
            var index = "Tab 1";
            var urlParams = {}; (function() { var e, a = /\+/g, r = /([^&=]+)=?([^&]*)/g, d = function(s) { return decodeURIComponent(s.replace(a, " ")); }, q = window.location.search.substring(1); while(e = r.exec(q)) { urlParams[d(e[1])] = d(e[2]); } })();
            function swapDiv(item) {
                for(i = 0; i < ary.length; i++) {
                    var l_name = ary[i] + "_nav";
                    if(ary[i] == item) {
                        document.getElementById(ary[i]).style.display = 'block';
                        document.getElementById(l_name).className = 'menutabactive';
                        index = item;
                    }
                    else {
                        document.getElementById(ary[i]).style.display = 'none';
                        document.getElementById(l_name).className = 'menutab';
                    }
                }
            }

            function mouserOut(chase, item) {
                if(index == chase) {
                    item.className = 'menutabactive';
                }
                else {
                    item.className = 'menutab';
                }
            }
        </script>
    <div width="100%" align="center">
    <h1>MongoDB Photo Album</h1>    
        <div id="Tabs" style="float: left;margin-left: 30px; margin-bottom: 10px;">
            <table cellpadding="20" cellspacing="0" border="0">
                <tr>
                    <td id="Tab 1_nav" class="menutabactive gradient" style="CURSOR: pointer" onmouseover="className='menutabactive';" onmouseout="mouserOut('Tab 1', this)" onclick="swapDiv('Tab 1');">Great Pictures</td>
                    <td id="Tab 2_nav" class="menutab gradient" style="CURSOR: pointer" onmouseover="className='menutabactive';" onmouseout="mouserOut('Tab 2', this)" onclick="swapDiv('Tab 2');">Wonderful Pictures</td>
                </tr>
            </table>
        </div>
        <br style="clear: both;"/>
        <hr />
        <div id="Tab 1" style="DISPLAY: block;margin-top: 10px;">
            <table cellpadding=4 cellspacing=0 border="0" bordercolor="black" width="1000" class="paper"><tr><td width="500">
                <table cellpadding="0" cellspacing="20" border="0">
                <tr>
                    <td>Thumbnails and descriptions. Click any thumbnail to view a larger version.</td><td></td><td></td>
                </tr><tr><td colspan="3"><div style="width:500px;height:400px;overflow-y: scroll;">
                <?php 
                    //Complete the table with the database contents
                    getAll("Pics");
            //Drop the database table. Here for testing purposes.
                    //$response = $db->drop();
                ?> 
            <!--</table> -->
        </div>
        <div id="Tab 2" style="DISPLAY: none;margin-top: 10px;">
            <table cellpadding=4 cellspacing=0 border="0" bordercolor="black" width="1000" class="paper"><tr><td width="500">
                <table cellpadding="0" cellspacing="20" border="0">
                <tr>
                    <td>Thumbnails and descriptions. Click any thumbnail to view a larger version.</td><td></td><td></td>
                </tr><tr><td colspan="3"><div style="width:500px;height:400px;overflow-y: scroll;">
                <?php 
                    //Complete the table with the database contents
                    getAll("Images");
            //Drop the database table. Here for testing purposes.
                    //$response = $db->drop();
                ?> 
            <!--</table> -->
        </div>

    <p><hr width=70%></p>
    <p>Enter new Image:</p>
        <form name="add" method="post" action="<?php echo $_SERVER['PHP_SELF'] . "?" . $loc;?>" enctype="multipart/form-data">
        <table cellpadding=4 cellspacing=0 border="1" bordercolor=black>
            <tr><td>Image file: </td><td><input type="file" name="file" id="file" /> </td></tr>
            <tr><td>Enter a title: </td><td><input type="text" name="title" /></td></tr>
            <tr><td>Enter a description: </td><td><input type="text" name="description" /></td></tr>
            <tr><td>Choose a group: </td><td><select name="group" style="width:100px;margin:5px 0 5px 0;"><option value="Pics">Pics</option><option value="Images">Images</option></select></td></tr>
            <tr><td>&nbsp;</td><td><input type="submit" value="Add Image" /></td></tr>
        </table>
        </form>
    </div>
    </body>
</html>

