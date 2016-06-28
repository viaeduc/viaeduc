<?php

namespace Rpe\PumBundle\Extension\LinkPreview;

class LinkPreview
{
    public function getPage($url) {
        $res = array();
        $options = array(CURLOPT_RETURNTRANSFER => true, // return web page
            CURLOPT_HEADER => true, // do not return headers
            CURLOPT_FOLLOWLOCATION => true, // follow redirects
            CURLOPT_USERAGENT => "spider", // who am i
            CURLOPT_AUTOREFERER => true, // set referer on redirect
            CURLOPT_CONNECTTIMEOUT => 10, // timeout on connect
            CURLOPT_TIMEOUT => 10, // timeout on response
            CURLOPT_MAXREDIRS => 3, // stop after 10 redirects
        );
        $ch = curl_init($url);
        curl_setopt_array($ch, $options);
        $content = curl_exec($ch);
        $header = curl_getinfo($ch);
        curl_close($ch);

        if ($header['http_code'] != 200) {
            return null;
        }

        $hrd = $header["content_type"];
        header("Content-Type: ".$hrd, true);

        $res['content'] = $content;
        $res['url'] = $header['url'];
        $res['header'] = $hrd;

        return $res;
    }

    public function getMedia($pageUrl){
        $media = array();

        if (strpos($pageUrl, "youtube.com") !== false) {
            $media = Media::mediaYoutube($pageUrl);
        } else if (strpos($pageUrl, "vimeo.com") !== false) {
            $media = Media::mediaVimeo($pageUrl);
        }
        else if (strpos($pageUrl, "metacafe.com") !== false) {
            $media = Media::mediaMetacafe($pageUrl);
        }
        else if (strpos($pageUrl, "dailymotion.com") !== false) {
            $media = Media::mediaDailymotion($pageUrl);
        }

        return $media;
    }

    public function joinAll($matching, $number, $url, $content) {
        for ($i = 0; $i < count($matching[$number]); $i++) {
            $imgSrc = $matching[$number][$i] . $matching[$number + 1][$i];
            $src = "";
            $pathCounter = substr_count($imgSrc, "../");
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
        return $content;
    }

    public function crawl($text, $imageQuantity, $header){

        if(filter_var($text, FILTER_VALIDATE_URL)){

            $title       = "";
            $description = "";
            $videoIframe = "";
            $video       = false;
            $images      = array();

            $finalUrl = $text;
            $pageUrl  = str_replace("https://", "http://", $finalUrl);

            if (Content::isImage($pageUrl)) {
                $images[] = $pageUrl;
            } else {
                if (null === $urlData = $this->getPage($pageUrl)) {
                    return null;
                }

                if (!$urlData["content"] && strpos($pageUrl, "//www.") === false) {
                    if (strpos($pageUrl, "http://") !== false)
                        $pageUrl = str_replace("http://", "http://www.", $pageUrl);
                    elseif (strpos($pageUrl, "https://") !== false)
                        $pageUrl = str_replace("https://", "https://www.", $pageUrl);

                    if (null === $urlData = $this->getPage($pageUrl)) {
                        return null;
                    }
                }

                $pageUrl = $finalUrl = $urlData["url"];
                $raw = $urlData["content"];
                $header = $urlData["header"];

                $metaTags = Content::getMetaTags($raw);

                $tempTitle = Content::extendedTrim($metaTags["title"]);
                if ($tempTitle != "")
                    $title = $tempTitle;

                if ($title == "") {
                    if (preg_match(Regex::$titleRegex, str_replace("\n", " ", $raw), $matching))
                        $title = $matching[2];
                }

                $tempDescription = Content::extendedTrim($metaTags["description"]);
                if ($tempDescription != "")
                    $description = $tempDescription;
                else
                    $description = Content::crawlCode($raw);

                $descriptionUnderstood = false;
                if ($description != "")
                    $descriptionUnderstood = true;

                if (($descriptionUnderstood == false && strlen($title) > strlen($description) && !preg_match(Regex::$urlRegex, $description) && $description != "" && !preg_match('/[A-Z]/', $description)) || $title == $description) {
                    $title = $description;
                    $description = Content::crawlCode($raw);
                }

                $media       = $this->getMedia($pageUrl);
                $images[]    = count($media) == 0 ? Content::extendedTrim($metaTags["image"]) : $media[0];
                $videoIframe = count($media) == 0 ? '' : $media[1];

                $images = Content::getImages($images, $raw, $pageUrl, $imageQuantity);

                if ($media != null && $media[0] != "" && $media[1] != "") {
                    $video = true;
                }

                $title       = Content::extendedTrim($title);
                $pageUrl     = Content::extendedTrim($pageUrl);
                $description = Content::extendedTrim($description);
                $description = preg_replace(Regex::$scriptRegex, "", $description);
            }

            $answer = array(
                "title"        => $title,
                "url"          => $finalUrl, 
                "canonicalUrl" => Url::canonicalPage($pageUrl), 
                "description"  => strip_tags($description), 
                "images"       => $images, 
                "video"        => $video, 
                "videoIframe"  => $videoIframe
            );

            //return Json::jsonSafe($answer, $header);
            return $answer;
        }

        return null;
    }

}
