<?php
namespace Rpe\PumBundle\Extension\Tool;

use Pum\Bundle\TypeExtraBundle\Model\Media;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Pum\Bundle\CoreBundle\PumContext;

/**
 * @method void __construct(PumContext $context)
 * @method string    getInitialsFromString($string = '')
 * @method array|string     getRandomColorFromText($string, $minBrightness = 100, $spec = 10, $HEX = true)
 * @method array|string     getPaletteColorFromText($string, $HEX = true, $excludeColorsKeys = array())
 * @method Media            getAutoAvatar($string, $imageWidth = 300, $imageHeight = 300, $colors = null)
 * @method Media            getMaskedImage($maskKey, $colors, $imageWidth = 250, $imageHeight = 250, $fitBounds = true)
 * @method Media            getCroppedImage(Media $image, $coords, $restrictions = array())
 * @method Media            getCroppedAvatar(Media $image, $coords, $restrictions = array())
 * 
 */
class Avatar
{
    /**
     * @var PumContext
     */
    private $context;

    /**
     * @var ResourcePath
     */
    private $resourcePath;

    /**
     * @var ColorPalette
     */
    private $colorPalette;

    /**
     * @var MaskFiles
     */
    private $maskFiles;

    /**
     * Construct function
     *
     * @access public
     * @param PumContext $pumContext
     *
     * @return void
     */
    public function __construct(PumContext $context)
    {
        $this->context = $context;
        $this->resourcePath = $this->context->getContainer()->get('kernel')->locateResource('@RpePumBundle/Resources/');

        $this->colorPalette = array(
            '#FF8D8D',
            '#FFAF83',
            '#EFC081',
            '#D3BD5E',
            '#B6C65C',
            '#5DC68F',
            '#6EC1CC',
            '#6BB0D6'
        );

        $this->maskFiles = array(
            'user' => 'mask-user.png',
            'users' => 'mask-users.png',
            'books' => 'mask-books.png',
            'apps' => 'mask-apps.png',
            'editor' => 'mask-editor.png'
        );
    }

    /**
     * get initials from string
     * 
     * @access public
     * @param string $string
     * @return string
     */
    public function getInitialsFromString($string = '')
    {
        preg_match_all('/\b\w/u', $string, $matches);

        return implode('', $matches[0]);
    }

    /**
     * Generate color value from string
     * 
     * @access public
     * @param  string  $string        String to use for color
     * @param  integer $minBrightness Minimal brightness for generated color
     * @param  integer $spec          Number to influence the color palette
     * @param  boolean $HEX           Return color as hexadecimal value instead of RGB array
     * @return array|string
     */
    public function getRandomColorFromText($string, $minBrightness = 100, $spec = 10, $HEX = true)
    {
        // Check inputs
        if (!is_int($minBrightness)) {
            throw new Exception("$minBrightness is not an integer");
        }
        if (!is_int($spec)) {
            throw new Exception("$spec is not an integer");
        }
        if ($spec < 2 or $spec > 10) {
            throw new Exception("$spec is out of range");
        }
        if ($minBrightness < 0 or $minBrightness > 255) {
            throw new Exception("$minBrightness is out of range");
        }

        $hash = md5($string);  //Gen hash of text
        $colors = array();
        for ($i=0; $i<3; $i++) {
            //convert hash into 3 decimal values between 0 and 255
            $colors[$i] = max(array(round(((hexdec(substr($hash, $spec*$i, $spec))) / hexdec(str_pad('', $spec, 'F')))*255), $minBrightness));
        }

        if ($minBrightness > 0) { //only check brightness requirements if min_brightness is about 100
            while (array_sum($colors)/3 < $minBrightness) {  //loop until brightness is above or equal to min_brightness
                for ($i=0; $i<3; $i++) {
                    $colors[$i] += 10;  //increase each color by 10
                }
            }
        }

        $output = '';

        for ($i=0; $i<3; $i++) {
            $output .= str_pad(dechex($colors[$i]), 2, 0, STR_PAD_LEFT);  //convert each color to hex and append to output
        }

        if (true !== $HEX) {
            return $colors;
        }

        return '#'.$output;
    }

   /**
    * Get palette's color from text
    * 
    * @access public
    * @param  string        $string             String  to  use     for    color
    * @param  boolean       $HEX                Return  color  as     hexadecimal  value  instead  of  RGB  array
    * @param  array         $excludeColorsKeys  Keys  of  palette  colors  to  exclude
    * 
    * @return array|string
    */
    public function getPaletteColorFromText($string, $HEX = true, $excludeColorsKeys = array())
    {
        $colors = $this->colorPalette;

        if (count($excludeColorsKeys) > 0) {
            foreach ($excludeColorsKeys as $colorIndex) {
                unset($colors[$colorIndex]);
            }
            $colors = array_values($colors);
        }
        $maxValue = count($colors) - 1;

        $c = count_chars($string, 1);
        $colorIndex = (array_sum($c) + array_sum(array_keys($c))) % $maxValue;

        $finalColor = $colors[$colorIndex];

        if (true !== $HEX) {
            $finalColor = array(
                hexdec(substr($finalColor, 1, 2)),
                hexdec(substr($finalColor, 3, 2)),
                hexdec(substr($finalColor, 5, 2))
            );
        }

        return $finalColor;
    }

    /**
     * Generate avatar with initials from string
     * 
     * @access public
     * @param  string  $string
     * @param  integer $imageWidth
     * @param  integer $imageHeight
     * @param  array   $colors
     * @return Media
     */
    public function getAutoAvatar($string, $imageWidth = 300, $imageHeight = 300, $colors = null)
    {
        $initials = strtoupper($this->getInitialsFromString($string));
        if (null === $colors) {
            $colors = $this->getPaletteColorFromText($initials, false);
        }

        $font = $this->resourcePath . '/public/fonts/ubuntu-light.ttf';
        $fontSize = $imageWidth/3;

        if (($initialsLength = strlen($initials)) >= 4) {
            $fontSize = round($fontSize - ($fontSize * $initialsLength * 10 / 100));
        }

        $im = imagecreatetruecolor($imageWidth, $imageHeight);
        $bg = imagecolorallocate($im, $colors[0], $colors[1], $colors[2]);
        $textColor = imagecolorallocate($im, 255, 255, 255);

        imagefilledrectangle($im, 0, 0, $imageWidth, $imageHeight, $bg);
        $textBox = imagettfbbox($fontSize, 0, $font, $initials);

        // Get your Text Width and Height
        $textWidth = $textBox[2]-$textBox[0];
        $textHeight = $textBox[7]-$textBox[1];

        // Calculate coordinates of the text
        $x = ($imageWidth/2) - ($textWidth/2);
        $y = ($imageHeight/2) - ($textHeight/2);

        // imagefilledrectangle($im, $x, $y, $x+$textWidth, $y+$textHeight, imagecolorallocate($im, 128, 128, 128)); // font area debugger
        imagettftext($im, $fontSize, 0, $x-($imageWidth/37), $y, $textColor, $font, $initials);

        $fileName = md5(mt_rand().uniqid().microtime().$initials) . '.png';
        $src = sys_get_temp_dir().DIRECTORY_SEPARATOR.$fileName;

        imagepng($im, $src, 9);
        @imagedestroy($im);

        $avatar = new Media();
        $avatar->setFile(new \Symfony\Component\HttpFoundation\File\File($src));
        $avatar->setName($fileName);
        register_shutdown_function(function () use ($src) {
            @unlink($src);
        });
        return $avatar;
    }

    /**
     * Generate image (or avatar) with image overlay
     * 
     * @access public
     * @param  string  $maskKey
     * @param  array   $colors
     * @param  integer $imageWidth
     * @param  integer $imageHeight
     * @param  boolean $fitBounds
     * 
     * @return Media
     */
    public function getMaskedImage($maskKey, $colors, $imageWidth = 250, $imageHeight = 250, $fitBounds = true)
    {
        $mask = imagecreatefrompng($this->resourcePath . '/public/images/masks/' . $this->maskFiles[$maskKey]);
        imagealphablending($mask, true);
        imagesavealpha($mask, true);

        // Create an image
        $im = imagecreatetruecolor($imageWidth, $imageHeight);
        $bg = imagecolorallocate($im, $colors[0], $colors[1], $colors[2]);

        // Draw a white rectangle
        imagefilledrectangle($im, 0, 0, $imageWidth, $imageHeight, $bg);


        $maskWidth = imagesx($mask);
        $maskHeight = imagesy($mask);

        if ($maskWidth < $imageWidth && !$fitBounds) {
            $finalMaskHeight = $maskHeight;
            $finalMaskWidth = $maskWidth;

            $maskPosY = ceil(($imageHeight - $finalMaskHeight) / 2);
        } else {
            $finalMaskWidth = $imageWidth;
            $finalMaskHeight = ceil($maskHeight*($finalMaskWidth/$maskWidth));

            $maskPosY = ceil($imageHeight - $finalMaskHeight);
        }
        $maskPosX = ceil(($imageWidth - $finalMaskWidth) / 2);

        imagecopyresized($im, $mask, $maskPosX, $maskPosY, 0, 0, $finalMaskWidth, $finalMaskHeight, $maskWidth, $maskHeight);

        $fileName = md5(mt_rand().uniqid().microtime()) . '.png';
        $src = sys_get_temp_dir().DIRECTORY_SEPARATOR.$fileName;

        // Save the image
        imagepng($im, $src, 9);
        @imagedestroy($im);

        $maskedImage = new Media();
        $maskedImage->setFile(new \Symfony\Component\HttpFoundation\File\File($src));
        $maskedImage->setName($fileName);
        register_shutdown_function(function () use ($src) {
            @unlink($src);
        });
        return $maskedImage;
    }

    /**
     * Crop an image
     * 
     * @access public
     * @param  Media  $image        The image to crop
     * @param  array  $coords       An array with "w" key for width and "h" key for "height"
     * @param  array  $restrictions
     * @return Media
     */
    public function getCroppedImage(Media $image, $coords, $restrictions = array())
    {
        if (null !== $file = $image->getFile()) {
            $fileName = md5(mt_rand().uniqid().microtime());
            $extension = $file->guessExtension();
            if (!$extension) {
                $extension = 'bin';
            } elseif ($extension == 'jpeg') {
                 $extension = 'jpg';
            }

            $src = sys_get_temp_dir().DIRECTORY_SEPARATOR.$fileName.'.'.$extension;
            if (false === @copy($file, $src)) {
                throw new FileException(sprintf('Unable to copy image "%s"', $src));
            }

            $size      = getimagesize($src);
            $dst_image = ImageCreateTrueColor($coords['w'], $coords['h']);

            switch (strtolower($extension)) {
                case 'png':
                    $src_image = imagecreatefrompng($src);
                    $copyImageFunction = 'imagepng';

                    imagealphablending($dst_image, false);
                    imagesavealpha($dst_image, true);
                    $transparent = imagecolorallocatealpha($dst_image, 255, 255, 255, 127);
                    imagefilledrectangle($dst_image, 0, 0, $coords['w'], $coords['h'], $transparent);
                    break;

                case 'gif':
                    $src_image = imagecreatefromgif($src);
                    $copyImageFunction = 'imagegif';
                    break;

                default:
                    $src_image = imagecreatefromjpeg($src);
                    $copyImageFunction = 'imagejpeg';
                    break;
            }

            // TODO Verify image property here

            //$width = 300;
            //$ratio = $size[0] / $width; old case;
            $ratio = 1;

            $dst_x = 0;
            $dst_y = 0;
            $src_x = $coords['x'] * $ratio;
            $src_y = $coords['y'] * $ratio;
            $dst_w = $coords['w'];
            $dst_h = $coords['h'];
            $src_w = $coords['w'] * $ratio;
            $src_h = $coords['h'] * $ratio;

            imagecopyresampled($dst_image, $src_image, $dst_x, $dst_y, $src_x, $src_y, $dst_w, $dst_h, $src_w, $src_h);
            $copyImageFunction($dst_image, $src);

            //header('Content-Type: image/jpeg');imagejpeg($dst_image, null, 100);exit;

            //  Kill the file handles
            @imagedestroy($dst_image);
            @imagedestroy($src_image);

            $image->setFile(new \Symfony\Component\HttpFoundation\File\File($src));

            register_shutdown_function(function () use ($src) {
                // Skip windows message error on unwrittable file
                @unlink($src);
            });
        }

        return $image;
    }

    /**
     * Crop an avatar
     * 
     * @param  Media  $image        The avatar to crop
     * @param  array  $coords       An array with "w" key for width and "h" key for "height"
     * @param  array  $restrictions
     * @return Media
     */
    public function getCroppedAvatar(Media $image, $coords, $restrictions = array())
    {
        if (null !== $file = $image->getFile()) {
            $fileName = md5(mt_rand().uniqid().microtime());
            $extension = $file->guessExtension();
            if (!$extension) {
                $extension = 'bin';
            } elseif ($extension == 'jpeg') {
                 $extension = 'jpg';
            }

            $src = sys_get_temp_dir().DIRECTORY_SEPARATOR.$fileName.'.'.$extension;
            if (false === @copy($file, $src)) {
                throw new FileException(sprintf('Unable to copy image "%s"', $src));
            }

            $size      = getimagesize($src);
            $dst_image = ImageCreateTrueColor($coords['w'], $coords['h']);

            switch (strtolower($extension)) {
                case 'png':
                    $src_image = imagecreatefrompng($src);
                    $copyImageFunction = 'imagepng';

                    imagealphablending($dst_image, false);
                    imagesavealpha($dst_image, true);
                    $transparent = imagecolorallocatealpha($dst_image, 255, 255, 255, 127);
                    imagefilledrectangle($dst_image, 0, 0, $coords['w'], $coords['h'], $transparent);
                    break;

                case 'gif':
                    $src_image = imagecreatefromgif($src);
                    $copyImageFunction = 'imagegif';
                    break;

                default:
                    $src_image = imagecreatefromjpeg($src);
                    $copyImageFunction = 'imagejpeg';
                    break;
            }

            // TODO Verify image property here

            $width = 300;
            $ratio = $size[0] / $width;

            $dst_x = 0;
            $dst_y = 0;
            $src_x = $coords['x'] * $ratio;
            $src_y = $coords['y'] * $ratio;
            $dst_w = $coords['w'];
            $dst_h = $coords['h'];
            $src_w = $coords['w'] * $ratio;
            $src_h = $coords['h'] * $ratio;

            imagecopyresampled($dst_image, $src_image, $dst_x, $dst_y, $src_x, $src_y, $dst_w, $dst_h, $src_w, $src_h);
            $copyImageFunction($dst_image, $src);

            //header('Content-Type: image/jpeg');imagejpeg($dst_image, null, 100);exit;

            //  Kill the file handles
            @imagedestroy($dst_image);
            @imagedestroy($src_image);

            $image->setFile(new \Symfony\Component\HttpFoundation\File\File($src));

            register_shutdown_function(function () use ($src) {
                // Skip windows message error on unwrittable file
                @unlink($src);
            });
        }

        return $image;
    }
}
