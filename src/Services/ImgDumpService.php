<?php


namespace App\Services;


use Nyholm\Psr7\Response;
use Nyholm\Psr7\ServerRequest;
use Nyholm\Psr7\UploadedFile;

class ImgDump
{
    const MAX_IMG_SIZE = 4096;
    const IMG_DIR      = "//www.deepgamers.com/userimages/";
    const MAX_HEIGHT   = 150;
    const MAX_WIDTH    = 150;
    const IMG_PER_PAGE = 30;
    const allowedMimeTypes = ["image/jpeg","image/png","image/gif"];
    const UPLOAD_DIRECTORY = __DIR__ . '/../public/userimages';
    const THUMB_DIR = 'thumb';

    /** @var \PDO */
    private $dbh;

    function __construct(\PDO $dbh) {
        $this->dbh = $dbh;
    }


    /**
     * Gets images' information from the database
     *
     * Get images' information from the database and stores
     * all this data in the $images array.  Will look at the
     * page number specified in GET.
     */
    public function getImages() {

        /** Determine the offset from the page number **/
        if( isset($_GET["p"]) && $_GET["p"] > 0) {
            $start = self::IMG_PER_PAGE * $_GET["p"] - 1;
        } else {
            $start = 0;
        }


        /** Query the database **/
        $sql = "SELECT id, image, title, uploader, pin FROM imgdump ORDER BY id DESC LIMIT :start,:end";
        $query = $this->dbh->prepare($sql);
        $query->bindValue(':start', $start, \PDO::PARAM_INT);
        $query->bindValue(':end', self::IMG_PER_PAGE, \PDO::PARAM_INT);
        $query->execute();

        $images = [];


        /** Parse the result and add it to the array **/
        while ($row = $query->fetch(\PDO::FETCH_ASSOC)) {

            $image = array();

            list($filename) = explode(".", $row["image"]);

            $thumbnailPath = self::UPLOAD_DIRECTORY . '/' . self::THUMB_DIR . '/' . $filename . '.jpg';

            if (file_exists($thumbnailPath)) {
                $image["thumbnail"] = self::IMG_DIR . self::THUMB_DIR . '/' . urlencode($filename) . ".jpg";
            } else {
                $image["thumbnail"] = "/images/na.png";
            }

            $image["image"] = self::IMG_DIR . urlencode($row["image"]);
            $image["title"] = htmlspecialchars($row["title"]);
            $image["uploader"] = htmlspecialchars($row["uploader"]);
            $image["id"] = $row["id"];
            $image["pin"] = $row["pin"];

            $images[] = $image;
        }

        return $images;
    }

    /**
     * Displays the images
     *
     * Displays the images stored in $images array.  Should
     * be called after getImages() or getFavoriteImages().
     */
    public function displayImages($images) {

        if (count($images) == 0)
            echo "No images found";


        /** Print each image cell **/
        foreach($images as $image) {

            echo "<li>";

            echo "<a href=" . $image["image"] . ">
				<img src=" . $image["thumbnail"] . ' class=imgdump-img>
				</a><br />
				<span class=comment>' . stripslashes($image["title"]) . "</span><br />
				<span class=uploader>" . stripslashes($image["uploader"]) . '</span>';


            /** Display the remove link if uploader or admin **/
            if ($image["pin"] == @$_SESSION["pin"] || isset($_SESSION["admin"]) ) {
                echo '<a href=remove.php?id=' . $image["id"] . ' class="darkButton remove">Remove</a>';
            }

            echo "</li>";
        }
    }

    /**
     * Gets a user's favorite list as an array
     *
     * Retrives a user's fravorite list from the database
     * and parses it into an array.  Returns false if user
     * has no favorites.
     *
     * @return int[] Array of favorite image ids
     */
    public function displayPageSelect($images) {


        if (isset($_GET['p']))
            $page = $_GET['p'];
        else
            $page = 0;

        echo '<div style="clear:both; width: 100%; text-align: center; padding: 5px 0px;">';
        if($page > 0)
            echo '<a href="?p=' . ($page-1) .'" style="float:left; font-size:18px;"><--Previous</a>';

        if(count($images) == self::IMG_PER_PAGE)
            echo '<a href="?p=' . ($page+1) . '" style="float:right; font-size:18px;">Next--></a>';
        echo '</div>';
    }

    public function submitImage(ServerRequest $request, Response $response)
    {
        $uploadedFiles = $request->getUploadedFiles();

        if (!isset($uploadedFiles['image'])) {
            $response->getBody()->write(json_encode(['error' => 'No file uploaded']));
            return $response->withStatus(400);
        }

        /** @var UploadedFile $uploadedFile */
        $uploadedFile = $uploadedFiles['image'];

        if ($uploadedFile->getError() !== UPLOAD_ERR_OK) {
            $response->getBody()->write(json_encode(['error' => 'Error uploading file']));
            return $response->withStatus(400);
        }

        if (!in_array($uploadedFile->getClientMediaType(), self::allowedMimeTypes)) {
            $response->getBody()->write(json_encode(['error' => 'Unsupported image format']));
            return $response->withStatus(400);
        }

        if ($uploadedFile->getSize() > self::MAX_IMG_SIZE * 1024 || $uploadedFile->getSize() === 0) {
            $response->getBody()->write(json_encode(['error' => 'Invalid image size']));
            return $response->withStatus(400);
        }

        $params = $request->getParsedBody();
        $title = $params['title'] ?? 'Untitled';
        $uploader = $params['uploader'] ?? 'Anonymous';

        $title = substr(trim($title), 0, 100);
        $uploader = substr(trim($uploader), 0, 30);

        if($title === '') {
            $title = 'Untitled';
        }

        if ($uploader === '') {
            $uploader = 'Anonymous';
        }

        $query = $this->dbh->prepare('SELECT MAX(ID) FROM imgdump');
        $query->execute();
        $id = $query->fetchColumn();

        if ($id == null) {
            $id = 1;
        } else {
            ++$id;
        }

        $slug = preg_replace('/[^a-zA-Z0-9\s]/', "", $title);
        $slug = str_replace(" ", "-", $slug);
        $slug = strtolower($slug);

        $extension = explode('/', $uploadedFile->getClientMediaType())[1];

        // Prepare the destination filename
        $destFileName = "$id-$slug.$extension";

        if (!isset($_SESSION['pin'])) {
            $pin = rand(1000, 9999);
            $_SESSION['pin'] = $pin;
            setcookie("User", ":$pin", time() + 60*60*24*365, '/');
        } else {
            $pin = $_SESSION['pin'];
        }

        $insertQuery = $this->dbh->prepare("INSERT INTO imgdump (image, title, uploader, pin, submitted) VALUES (:image, :title, :uploader, :pin, :submitted)");
        $insertQuery->bindValue(':image', $destFileName);
        $insertQuery->bindValue(':title', $title);
        $insertQuery->bindValue(':uploader', $uploader);
        $insertQuery->bindValue(':pin', $pin);
        $insertQuery->bindValue(':submitted', date("Y-m-d"));

        if(!$insertQuery->execute()) {
            return $response->withStatus(400)->withJson(['error' => 'Database error']);
        }

        $targetPath = self::UPLOAD_DIRECTORY . '/' . $destFileName;

        $uploadedFile->moveTo($targetPath);

        $size = getimagesize($targetPath);

        // If the image is larger than 2500*2500 we can't generate a thumbnail
        if (($size[0] * $size[1] < 6250000)) {
            $this->makeThumb($destFileName, $extension);
        }

        $response->getBody()->write('<META HTTP-EQUIV="Refresh" CONTENT="1; URL=/imgdump/"><h1>File Uploaded Successfully!</h1><br><a href="../../public/index.php">Back to Gallery</a>');

        return $response;
    }

    public function removeImage(ServerRequest $request, Response $response)
    {
        $queryParams = $request->getQueryParams();

        $id = intval($queryParams['id'] ?? 0);

        if ($id === 0) {
            $response->getBody()->write(json_encode(['error' => 'No id provided']));
            return $response->withStatus(400);
        }

        $query = $this->dbh->prepare('SELECT pin, image FROM imgdump WHERE id=:id');
        $query->bindValue(':id', $id);
        $query->execute();

        $row = $query->fetch(\PDO::FETCH_ASSOC);

        if (!is_array($row)) {
            $response->getBody()->write(json_encode(['error' => 'Image not found']));
            return $response->withStatus(404);
        }

        $pin = (int)$row['pin'];
        $filename = $row['image'];

        if (!isset($_SESSION['pin']) || $_SESSION['pin'] !== $pin) {
            $response->getBody()->write(json_encode(['error' => 'Not authorized to remove image']));
            return $response->withStatus(401);
        }

        $deleteQuery = $this->dbh->prepare("DELETE FROM imgdump WHERE id=:id");
        $deleteQuery->bindValue(':id', $id);

        if (!$deleteQuery->execute()) {
            $response->getBody()->write(json_encode(['error' => 'Database error']));
            return $response->withStatus(500);
        }

        $i = strrpos($filename,".");
        $thumb = substr($filename,0,$i) . ".jpg";

        $fullImagePath = self::UPLOAD_DIRECTORY . '/' . $filename;
        $thumbnailPath = self::UPLOAD_DIRECTORY . '/' . self::THUMB_DIR . '/' . $thumb;

        unlink($fullImagePath);
        unlink($thumbnailPath);

        $response->getBody()->write('<META HTTP-EQUIV="Refresh" CONTENT="1; URL=/imgdump/"><h1>File Deleted Successfully!</h1><br><a href="/imgdump/">Back to Gallery</a>');

        return $response;
    }

    private function makeThumb($file, $type)
    {
        // Make a .jpg with the new image
        list($filename, $extension) = explode(".", $file);

        $fullImagePath = self::UPLOAD_DIRECTORY . '/' . $file;
        $thumbnailPath = self::UPLOAD_DIRECTORY . '/' . self::THUMB_DIR . "/$filename.jpg";
        // Make a new image depending upon file type
        if ($type == 'jpeg') {
            $src = imagecreatefromjpeg($fullImagePath);
        } else if ( $type == 'png' ) {
            $src = imagecreatefrompng($fullImagePath);
        } else if ( $type == 'gif' ) {
            $src = imagecreatefromgif($fullImagePath);
        }

        // Get image dimensions
        $width = imagesx($src);
        $height = imagesy($src);

        // Calculate new height and width
        if ( $width < $height ) {
            $newWidth = $width * (self::MAX_WIDTH / $height);
            $newHeight = self::MAX_HEIGHT;
        } else {
            $newWidth = self::MAX_WIDTH;
            $newHeight = $height * (self::MAX_HEIGHT / $width);
        }

        // Copy the image to the new canvas
        $new = imagecreatetruecolor($newWidth, $newHeight);
        imagecopyresampled($new, $src, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);


        imagejpeg($new, $thumbnailPath, 90);

        // Clean up
        imagedestroy($new);
        imagedestroy($src);
    }

}