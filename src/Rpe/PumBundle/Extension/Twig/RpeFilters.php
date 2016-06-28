<?php
namespace Rpe\PumBundle\Extension\Twig;

use Pum\Core\Extension\Util\Namer;

/**
 * @method string getName()
 * @method array  getFilters() 
 *
 */
class RpeFilters extends \Twig_Extension
{
    

    /**
     * Get name of filter
     * 
     * @return string
     * @see Twig_ExtensionInterface::getName()
     */
    public function getName()
    {
        return 'rpe_twig_filters';
    }

    /**
     * Get filters
     * 
     * @return array
     * @see Twig_Extension::getFilters()
     */
    public function getFilters()
    {
        return array(
            new \Twig_SimpleFilter('isImage', function ($mime) {
                if (in_array(strtolower($mime), array('image/gif','image/png','image/jpg','image/jpeg'))) {
                    return true;
                }

                $data = explode(".", $mime);
                if (2 == count($data) && in_array(strtolower($data[1]), array('gif','png','jpg','jpeg'))) {
                    return true;
                }

                return false;
            }),
            new \Twig_SimpleFilter('initials', function ($str) {
                preg_match_all('/\b\w/u', $str, $matches);

                return implode('', $matches[0]);
            }),
            new \Twig_SimpleFilter('slug', function ($str) {
                return Namer::toSlug($str);
            }),
            new \Twig_SimpleFilter('age', function ($date) {
                if ($date instanceof \DateTime || $date instanceof \Date) {
                    return (int) ((time() - $date->getTimestamp()) / 3600 / 24 / 365);
                }

                return null;
            }),
            new \Twig_SimpleFilter('strToDate', function ($string, $format = 'd/m/Y') {
                if (null === $string || !$string) {
                    return null;
                }

                return date($format, strtotime($string));
            }),
            new \Twig_SimpleFilter('removeFromArray', function (array $array, $value, $empty = array()) {
                if (($key = array_search($value, $array)) !== false) {
                    unset($array[$key]);
                }

                if (empty($array)) {
                    return $empty;
                }

                return $array;
            }),
            new \Twig_SimpleFilter('jsTimeToPhpTime', function ($time) {
                return round($time/1000);
            }),
            new \Twig_SimpleFilter('elasticScore', function ($score) {
                $score = floatval($score);

                switch (true) {
                    case $score > 0.6:
                        return 5;
                        break;

                    case $score > 0.45:
                        return 4;
                        break;

                    case $score > 0.3:
                        return 3;
                        break;

                    case $score > 0.01:
                        return 2;
                        break;

                    default:
                        return 1;
                        break;
                }
            }),
            new \Twig_SimpleFilter('hostFromUrl', function ($url) {
                $data = parse_url(strtolower(trim($url)));

                return isset($data['host']) ? $data['host'] : '';
            }),
            new \Twig_SimpleFilter('fileToken', function ($id) {
                if (!$id) {
                    return null;
                }

                return md5('pum_media_' . $id . '_media_pum');
            }),
            new \Twig_SimpleFilter('detecturls', function ($text) {
                $reg_exUrl = "/(http|https|ftp|ftps)\:\/\/[a-zA-Z0-9\-\.]+\.[a-zA-Z]{2,3}(\/\S[^<>]*)?/";
                $matches = array();
                // Check if there is a url in the text
                preg_match_all($reg_exUrl, $text, $matches);
                $usedPatterns = array();
                foreach($matches[0] as $pattern){
                    if(!array_key_exists($pattern, $usedPatterns)){
                        $usedPatterns[$pattern]=true;
                        $text = str_replace($pattern, 
                            ("<a target='_blank' href=" . $pattern . ">" . $pattern . "</a>"), $text);
                    }
                }
                return $text;
            })
        );
    }
}
