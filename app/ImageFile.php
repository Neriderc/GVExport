<?php
/* ImageFile class
 * Represents an image to be included in a diagram (e.g. photo)
 */

namespace vendor\WebtreesModules\gvexport;

use Fisharebest\Webtrees\Auth;
use Fisharebest\Webtrees\Site;
use Fisharebest\Webtrees\Tree;
use GdImage;

/**
 * An image file, representing a hard drive image file
 */
class ImageFile
{
    public int $resolution;
    private object $mediaFile;
    private object $tree;
    private int $type;

    function __construct(object $media_file, Tree $tree, int $resolution)
    {
        $this->mediaFile = $media_file;
        $this->tree = $tree;
        $this->resolution = $resolution;
    }


    /**
     *  Return the image path for including in diagram
     *
     * @param int $quality      JPEG quality setting
     * @param boolean $convert      whether to convert non-JPEGs to JPEG
     * @return string
     */
    public function getImageLocation(int $quality, bool $convert): string
    {
        $filename = $this->mediaFile->filename();
        $full_media_path = Site::getPreference('INDEX_DIRECTORY') . $this->tree->getPreference('MEDIA_DIRECTORY') . $filename;
        // If SVG then scale image and provide location of temp file
        if ($_REQUEST["vars"]["output_type"] == "svg" || $_REQUEST["vars"]["output_type"] == "pdf") {
            $temp_dir = new File()->sys_get_temp_dir_my();
            if ($temp_dir === "") return "";
            $temp_dir = $temp_dir . "/" . md5((string) Auth::id());
            $temp_image_file = $temp_dir . "/" . $filename;

            $image = $this->loadImage($full_media_path);
            if ($image) {
                $img_resized = imagescale($image, $this->resolution, -1);
                if ($this->saveImage($img_resized, $temp_image_file, $full_media_path, $quality, $convert)) {
                    return $temp_dir . "/" . $filename;
                } else {
                    // Resize failed for some reason, despite being supported format
                    // (e.g. invalid file). Return path to original file instead, as most
                    // of the time the file will work even if PHP couldn't load it.
                    return $full_media_path;
                }
            } else {
                // Don't scale as not one of our recognised formats, just return original image path
                return $full_media_path;
            }
        } else {
            return $full_media_path;
        }
    }

    /** Load the image into PHP
     *
     * @param $filepath
     * @return false|\GdImage
     */
    private function loadImage(string $filepath)
    {
        $this->type = exif_imagetype($filepath);
        switch ($this->type) {
            case IMAGETYPE_GIF:
                $image = @imageCreateFromGif($filepath);
                break;
            case IMAGETYPE_JPEG:
                ini_set('memory_limit', '8192M');
                $image = @imageCreateFromJpeg($filepath);
                break;
            case IMAGETYPE_PNG:
                $image = @imageCreateFromPng($filepath);
                break;
            case IMAGETYPE_BMP:
                $image = @imageCreateFromBmp($filepath);
                break;
            default:
                return false;
        }
        if (!$image) {
            return false;
        }
        return $image;
    }

    /** Take PHP image (GdImage) and save it as a
     *  file on the hard drive in the provided place
     *
     * @param $image
     * @param $temp_image_file_path
     * @param $full_media_path
     * @param $quality
     * @param $convert
     * @return bool
     */
    private function saveImage(GdImage $image, string $temp_image_file_path, string $full_media_path, int $quality, bool $convert): bool
    {
        $dir = dirname($temp_image_file_path);

        // We definitely do not want to overwrite any data - make sure our temp
        // directory is not in the webtrees directory to reduce risk
        $webtrees_dir = explode("webtrees", Site::getPreference('INDEX_DIRECTORY'))[0] . "webtrees";
        if (substr($temp_image_file_path, 0, strlen($webtrees_dir)) == $webtrees_dir) {
            die("Error: Temp directory cannot be within webtrees directory");
        }
        // Also check our temp file path is not the same as our media path
        if (realpath($temp_image_file_path) == realpath($full_media_path)) {
            die("Error: Temp file path cannot be the same as the media path");
        }

        if (!is_dir($dir)) {
            mkdir($dir, 0700, true);
        }
        if ($convert) {
            $this->type = IMAGETYPE_JPEG;
        }
        switch ($this->type) {
            case IMAGETYPE_GIF:
                imagegif($image, $temp_image_file_path);
                break;
            case IMAGETYPE_JPEG:
                imagejpeg($image, $temp_image_file_path, $quality);
                break;
            case IMAGETYPE_PNG:
                imagepng($image, $temp_image_file_path);
                break;
            case IMAGETYPE_BMP:
                imagewbmp($image, $temp_image_file_path);
                break;
            default:
                return false;
        }
        imagedestroy($image);
        return true;
    }
}
