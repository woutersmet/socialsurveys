<?php
        
class Image
{
    const MAX_IMAGE_SIZE = 500000; //uploaded images no bigger than 500kb
    
    const DEFAULT_IMAGE_PATH = 'images/blank.jpg';
    
    const THUMB_WIDTH = 100;
    const THUMB_HEIGHT = 100;
    const LARGE_WIDTH = 450;
    const LARGE_HEIGHT = 450;
    
    const BANNER_WIDTH = 510;
    const BANNER_HEIGHT = 127;
    
    const BANNER_RESIZER_RATIO =  4; //8 / 2

    //inspired by http://en.gravatar.com/site/implement/images/php/
    public static function getGravatarUrl($email)
    {
       Debug::log('Getting gravatar url for email: ' . $email);

       if (isset($_SESSION['gravatarurls'][$email])){ //'cache'
            $url = $_SESSION['gravatarurls'][$email];
       }
       else {
       $emailhash = md5( strtolower( trim( $email ) ) );
       $default = 'http://www.murketing.com/journal/wp-content/uploads/2009/04/8tracks.jpg';
       $size = 100;
       $url = "http://www.gravatar.com/avatar/" . $emailhash . "?d=" . $default . "&s=" . $size;

        $_SESSION['gravatarurls'][$email] = $url;
       
       /*
       //fall back on local when nothing found
       if (!($connected && $connected[0] == 'HTTP/1.0 200 OK'))
       {
           Debug::log("Working locally so gravatar wont help us!...");
           $url = '/images/defaultavatar.jpg';
       }
        * *
        */

       } 
       return $url;
    }
    
    public static function getFolderPath($shopid)
    {
       // die(__SITE_PATH);
        return Configuration::get('locations', 'productimages') . "/shop_" . $shopid .'/';
    }
    
    /*
     * return just the path (used for storing)
     * if productid is 'TOPBANNER' it's not a product image but the top banner 
     */
    public static function getFullPath($shopid, $productid, $isLargeVersion = false)
    {
        $folder = self::getFolderPath($shopid);
        $shopPart = 'shop_' . $shopid;
        
        if ($productid == 'TOPBANNER')
        {
            $specificPart = '_topbanner';
        }
        else
        {
            $largeSuffix = $isLargeVersion ? '_large_' : '';
            $specificPart = '_product_' . $largeSuffix . $productid;
        }
        
        $extension = '.png'; 
        
        $full = $folder . $shopPart . $specificPart. $extension;
        
        return $full;
    }
    
    public static function getImagePath($shopid, $productid)
    {
        $fullpath = self::getFullPath ($shopid, $productid);
        
        if (!is_file($fullpath))
        {
            return false;
        }
        
        $startingSlash = '/'; //Util::isFacebookStorefront() ? '' : '/';
        
        $filemtime = filemtime($fullpath); //make filename depend from so that when old image is changed browser doesn't show old version from cache anymore
        
        return $startingSlash . $fullpath  . '?'. $filemtime;
    }
    
    
    
    /*
     * PRODUCTID may also be 'TOPBANNER'
     */
    public static function removeImage($shopid, $productid)
    {
        Debug::log("Removing image for shop $shopid product: $productid ...");
        
        $path = Image::getFullPath($shopid, $productid); 

        //if there's already an image at that path delete it first
        if (file_exists($path))
        {
            Debug::log("$path is already a file! Removing it...");
            return unlink($path);
        }
        else
        {
            Debug::log("No file found at $path! All ok then I guess");
            return true;
        }
    }
    
    //if scale to width it will be squezed / stretched after cropping
    public static function cropImage($path, $x1, $y1, $x2, $y2, $scaleToWidth = false)
    {
        Debug::log($path, "Cropping this image to only retain rectangle with coords $x1, $y1, $x2, $y2 (and then resizing to our ratio) ...");
        
        if (!file_exists($path))
        {
            Debug::log("No image found at $path!");
            return false;
        }
        
        list($src_width, $src_height, $image_type) = @getimagesize($path);
        
        switch($image_type)
        {
            case 1: $src_img = @imagecreatefromgif($path);    break;
            case 2: $src_img = @imagecreatefromjpeg($path);   break;
            case 3: $src_img = @imagecreatefrompng($path);    break;
        } 
        
        $dest_width = ($x2 - $x1);
        $dest_height = ($y2 - $y1);
        
        if ($scaleToWidth)
        {
            Debug::log("Also rescaling crop to width: " . $scaleToWidth . 'px');
            $dest_width = $scaleToWidth;
            $dest_height = $scaleToWidth * $dest_height / ($x2 - $x1);
        }
        
        $dst_img = @imagecreatetruecolor($dest_width, $dest_height);
        
        $result = @imagecopyresampled($dst_img, $src_img, 0, 0, $x1, $y1, $dest_width, $dest_height, ($x2 - $x1), ($y2 - $y1));
        
        if ($result)
        {
            Debug::log("Crop success! Storing at location...");
            if (imagepng($dst_img,$path))
            {
                Debug::log("Saved!");
                return true;
            }
            else
            {
                return false;
            }
        }
        else
        {
            Debug::log("Cropping failed..");
            return false;
        }
    }
    /*
     * PRODUCTID may also be 'TOPBANNER'
     */
    public static function storeImage($shopid, $productid, $filedata, $index = false)
    {
        Debug::log($filedata, "Storing image: $productid for shop $shopid ....");
        
        $folder = Image::getFolderPath($shopid);

        Debug::log("Folder path: $folder");
        if (!is_dir($folder))
        {
            mkdir($folder);
            chmod($folder, 777);
        }

        //standard naming
        $target_path = Image::getFullPath($shopid, $productid); 

        //if there's already an image at that path delete it first
        if (file_exists($target_path))
        {
            Debug::log("$target_path is already a file! Removing that first...");
            unlink($target_path); 
        }
        
        if ($index !== false)
        {
            Debug::log("We have an index: $index! This means image data is about multiple images but we need that one");
            $tmpName = $_FILES['image']['tmp_name'][$index];
            $originalName = basename( $_FILES['image']['name'][$index]);
        }
        else
        {
            $tmpName = $_FILES['image']['tmp_name'];
            $originalName = basename( $_FILES['image']['name']);
        }
        
        $width = $productid == 'TOPBANNER' ? self::BANNER_WIDTH : self::THUMB_WIDTH;
        $height = $productid == 'TOPBANNER' ? self::BANNER_HEIGHT : self::THUMB_HEIGHT;
        $dimensionsAreMaxima = ($productid == 'TOPBANNER');
        
        $processResult = self::storeAsSizedPng($tmpName, $target_path, $width, $height, $dimensionsAreMaxima);
        
        Debug::log("Result of processing: " . $processResult);
        
        if ($productid != 'TOPBANNER')
        {
            Debug::log("It's not a top banner we're storing here! Also storing large version");
            
            $largePath = self::getFullPath($shopid, $productid, true);
            $processResult = self::storeAsSizedPng($tmpName, $largePath, self::LARGE_WIDTH, self::LARGE_HEIGHT, self::LARGE_WIDTH);
        }
        
        return $processResult;
        
        //end resizing
        
        /*
        if(move_uploaded_file($tmpName, $target_path)) 
        {
            Debug::log("The file ". $originalName . " has been uploaded");
            return true;
        } 
        else
        {
            Debug::error("There was an error uploading the file!");
            return false;
        }
         * 
         */
    }
    
    //source http://stackoverflow.com/questions/3786968/resize-image-before-uploading-php
    //also, source for transparent png: http://www.bl0g.co.uk/creating-transparent-png-images-in-gd.html
    //if dimensionsAreMaxima is true we will see them as maxima, else resulting image will have them EXACTLY (fit in a transparent png)
    private static function storeAsSizedPng($srcPath, $destPath, $dest_width, $dest_height, $dimensionsAreMaxima = true)
    {
        Debug::log("Processing the image at $srcPath: resizing and storing as png file at destination: $destPath ...");
        list($width_orig, $height_orig, $image_type) = @getimagesize($srcPath);
        
        switch($image_type)
        {
            case 1: $src_img = @imagecreatefromgif($srcPath);    break;
            case 2: $src_img = @imagecreatefromjpeg($srcPath);   break;
            case 3: $src_img = @imagecreatefrompng($srcPath);    break;
        }        

        /*
         * dimensions to resize to
         */
        $aspect_ratio = (float) $height_orig / $width_orig;

        $thumb_height = $dest_height;
        $thumb_width = round($dest_height / $aspect_ratio);
        if($thumb_width > $dest_width)
        {
            $thumb_width    = $dest_width;
            $thumb_height   = round($dest_width * $aspect_ratio);
        }

        /*
         * how to paste resized source image on destination image
         */
        if ($dimensionsAreMaxima)
        {
            $width = $thumb_width;
            $height = $thumb_height;
            
            $dst_x = $dst_y = 0; //entire base image will be filled
        }
        else
        {
            $width = $dest_width;
            $height = $dest_height;
            
            //not entire base image will be filled
            $dst_x = $dest_width > $thumb_width ? ($dest_width - $thumb_width) / 2 : 0;
            $dst_y = $dest_height > $thumb_height ? ($dest_height - $thumb_height) / 2 : 0;
        }
        
        Debug::log("Resulting image will have width $width and height $height ....");
        $dst_img = @imagecreatetruecolor($width, $height);
        
        if(!$dst_img)
        {
            Debug::error("failed to create base image for thumb");
        }
        else
        {
            Debug::log("Ok we got a 'template' image created to make into our resulting image!");
        }
        
        /*
         * If dimensions are not maxima make sure background 'edges' are transparent
         * source: http://www.bl0g.co.uk/creating-transparent-png-images-in-gd.html
         */
         if (!$dimensionsAreMaxima)
         {
             imagealphablending($dst_img,false);
             $col=imagecolorallocatealpha($dst_img,255,255,255,127);

             imagefilledrectangle($dst_img,0,0,$width,$height,$col);
             imagealphablending($dst_img,true);
         }
        
        $width = $thumb_width;
        $height = $thumb_height;
        
        $success = @imagecopyresampled($dst_img,$src_img,$dst_x,$dst_y,0,0,$width,$height,$width_orig,$height_orig);
        
        if (!$success)
        {
            Debug::error("Error copying original image on thumb canvas");
        }
        
        $storeresult = @imagepng($dst_img, $destPath);
        if (!$storeresult)
        {
            Debug::error("Storing went wrong!");
        }
        
        return $storeresult;
    }
    
    public static function whatIsWrongWithImage($filedata, $index = false)
    {
        Debug::log($filedata, "Checking what's wrong with data for uploaded image file... Index:" . $index);
        
        $name = $index !== false ? $filedata['name'][$index] : $filedata['name'];

        $nameparts = explode('.', $name);
            
        //not good, name has no dot!
        if (count($nameparts) < 2)
        {
            Debug::log("Name has no dot!");
            return 'NAME_MISSES_DOT';
        }

        //extension correct?
        $extension = $nameparts[count($nameparts) - 1];
        if (!in_array($extension, array('jpg', 'jpeg', 'JPG', 'gif', 'GIF', 'png', 'PNG')))
        {
            Debug::log("Extension $extension invalid!");
            return 'EXTENSION_INVALID';
        }

        //file not too big?
        $size = $index !== false ? $filedata['size'][$index] : $filedata['size'];
        if ($size > self::MAX_IMAGE_SIZE)
        {
            Debug::log("File size larger than " . self::MAX_IMAGE_SIZE . " bytes!");
            return 'FILE_TOO_LARGE';
        }
        
        return false;
    }
}
 
?>