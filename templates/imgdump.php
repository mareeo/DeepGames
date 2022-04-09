<?php
use League\Plates\Extension\RenderContext\RenderContext;

/** @var $this RenderContext */

?>

<!DOCTYPE html>
<html>
<head>
    <title>Deep Games &raquo; imgDump</title>

    <meta name="description" content="The imgDump is a place to quickly and easily upload and share images." />
    <meta name="keywords" content="live video game streaming stream feed Nintendo Wii Sony Playstation 3 PS3 Microsoft Xbox 360 Kinect Move Starcraft 2" />
    <meta http-equiv="content-type" content="text/html" charset="utf-8" />

    <link rel="stylesheet" type="text/css" href="../css/imgdump.css" charset="utf-8" />
    <link rel="stylesheet" type="text/css" href="../css/stuff.css" charset="utf-8" />

    <link rel="shortcut icon" href="../favicon.ico" />

</head>
<body>
<?php $this->insert('header'); ?>
<div class=content>
    <div class=title>imgDump</div><br />
    <div class=sub-title>Quick and easy chat-linkable images</div>

    <h2>Upload an image</h2>
    (.jpg, .png, .gif smaller than 2MB)
    <form name="new" method="post" enctype="multipart/form-data" action="submit.php" >
        <input type="textbox" name="title" maxlength="100" placeholder="Title" class=lightTextBox style="width: 200px;">
        <input type="textbox" name="uploader" maxlength="30" placeholder="Uploader" class=lightTextBox>
        <input type="file" name="image" value="Select Image" class=lightTextBox>
        <input name="Submit" type="submit" value="Upload image" class=darkButton>
    </form>

    <div id=picturesdiv>
        <ul id=pictures>
            <?php

            if (count($images) == 0) {
                echo "No images found";
            }

            /** Print each image cell **/
            foreach($images as $image) {
                ?>
                <li>
                    <a href="<?= $image["image"] ?>"><img src="<?= $image["thumbnail"] ?>" class=imgdump-img></a>
                    <br />
                    <span class=comment><?= stripslashes($image["title"]) ?></span><br />
                    <span class=uploader><?= stripslashes($image["uploader"])?></span>

                    <?php 
                    /** Display the remove link if uploader or admin **/
                    if ($image["uploader_uuid"] === $uploaderUuid || isset($_SESSION["admin"]) ) {
                    ?>
                        <a href="remove.php?id=<?=$image["imgdump_id"]?>" class="darkButton remove">Remove</a>
                    <?php } ?>
                </li>
            <?php }
            echo $pageSelectHtml;
            ?>
        </ul>
    </div>
    <div style="clear:both;"></div>
</div>

</div>
</body>
</html>
