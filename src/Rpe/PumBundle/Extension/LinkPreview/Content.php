<?php

namespace Rpe\PumBundle\Extension\LinkPreview;

class Content {

    static public function crawlCode($text) {
        $contentSpan = Content::getTagContent("span", $text);
        $contentParagraph = Content::getTagContent("p", $text);
        $contentDiv = Content::getTagContent("div", $text);
        if (strlen($contentParagraph) > strlen($contentSpan) && strlen($contentParagraph) >= strlen($contentDiv))
            $content = $contentParagraph;
        else if (strlen($contentParagraph) > strlen($contentSpan) && strlen($contentParagraph) < strlen($contentDiv))
            $content = $contentDiv;
        else
            $content = $contentParagraph;
        return $content;
    }

    static public function isImage($url) {
        if (preg_match(Regex::$imagePrefixRegex, $url))
            return true;
        else
            return false;
    }

    static public function getTagContent($tag, $string) {
        $pattern = "/<$tag(.*?)>(.*?)<\/$tag>/i";

        preg_match_all($pattern, $string, $matches);
        $content = "";
        for ($i = 0; $i < count($matches[0]); $i++) {
            $currentMatch = strip_tags($matches[0][$i]);
            if (strlen($currentMatch) >= 120) {
                $content = $currentMatch;
                break;
            }
        }
        if ($content == "") {
            preg_match($pattern, $string, $matches);
            if (!empty($matches[0])) {
                $content = $matches[0];
            }
        }
        return str_replace("&nbsp;", "", $content);
    }

    static public function getImages($images, $text, $url, $imageQuantity) {
        $images = Content::validImageUrl($images, $url);

        if (count($images) >= $imageQuantity) {
            return $images;
        }

        $content = array();
        if (preg_match_all(Regex::$imageRegex, $text, $matching)) {

            for ($i = 0; $i < count($matching[0]); $i++) {
                $src = "";
                $pathCounter = substr_count($matching[0][$i], "../");
                preg_match(Regex::$srcRegex, $matching[0][$i], $imgSrc);
                $imgSrc = Url::canonicalImgSrc($imgSrc[2]);
                if (!preg_match(Regex::$httpRegex, $imgSrc)) {
                    $src = Url::getImageUrl($pathCounter, Url::canonicalLink($imgSrc, $url));
                }
                if ($src . $imgSrc != $url) {
                    if ($src == "")
                        array_push($content, $src . $imgSrc);
                    else
                        array_push($content, $src);
                }
            }
        }

        $content = array_unique($content);
        $content = array_values($content);

        $maxImages = $imageQuantity != -1 && $imageQuantity < count($content) ? $imageQuantity : count($content);

        for ($i = 0; $i < count($content); $i++) {
            if (false !== $size = @getimagesize($content[$i])) {
                if ($size[0] > 100 && $size[1] > 15 && $size[0] < 1500 && $size[1] < 1300) {// avoids getting very small images and very big images
                    $images[] = $content[$i];
                    $maxImages--;
                    if ($maxImages == 0)
                        break;
                }
            }
        }

        return Content::validImageUrl($images, $url);
    }

    static public function validImageUrl($images, $url) {
        $validImages = array();
        $parseUrl    = parse_url($url);

        foreach ($images as $image) {
            if (!empty($image)) {
                if (false === filter_var($image, FILTER_VALIDATE_URL)) {
                    if (0 == strpos($image, '/')) {
                        $image = $parseUrl['scheme'].'://'.$parseUrl['host'].$image;
                        if (filter_var($image, FILTER_VALIDATE_URL)) {
                            $validImages[]= $image;
                        }
                    } else {
                        $image = $parseUrl['scheme'].'://'.$parseUrl['host'].'/'.$image;
                        if (filter_var($image, FILTER_VALIDATE_URL)) {
                            $validImages[]= $image;
                        }
                    }
                    
                } else {
                    $validImages[]= $image;
                }
            }
        }

        return $validImages;
    }

    static public function separeMetaTagsContent($raw) {
        preg_match(Regex::$contentRegex1, $raw, $match);
        if(count($match) == 0){
            preg_match(Regex::$contentRegex2, $raw, $match);
        }
        return $match[1];
    }

    static public function getMetaTags($contents) {
        $result = false;
        $metaTags = array("url" => "", "title" => "", "description" => "", "image" => "");

        if (isset($contents)) {

            preg_match_all(Regex::$metaRegex, $contents, $match);

            foreach ($match[1] as $value) {

                if ((strpos($value, 'property="og:url"') !== false || strpos($value, "property='og:url'") !== false) || (strpos($value, 'name="url"') !== false || strpos($value, "name='url'") !== false))
                    $metaTags["url"] = Content::separeMetaTagsContent($value);
                else if ((strpos($value, 'property="og:title"') !== false || strpos($value, "property='og:title'") !== false) || (strpos($value, 'name="title"') !== false || strpos($value, "name='title'") !== false))
                    $metaTags["title"] = Content::separeMetaTagsContent($value);
                else if ((strpos($value, 'property="og:description"') !== false || strpos($value, "property='og:description'") !== false) || (strpos($value, 'name="description"') !== false || strpos($value, "name='description'") !== false))
                    $metaTags["description"] = Content::separeMetaTagsContent($value);
                else if ((strpos($value, 'property="og:image"') !== false || strpos($value, "property='og:image'") !== false) || (strpos($value, 'name="image"') !== false || strpos($value, "name='image'") !== false))
                    $metaTags["image"] =  Content::separeMetaTagsContent($value);
            }

            $result = $metaTags;
        }
        return $result;
    }

    static public function extendedTrim($content) {
        return trim(str_replace("\n", " ", str_replace("\t", " ", preg_replace("/\s+/", " ", $content))));
    }
}
