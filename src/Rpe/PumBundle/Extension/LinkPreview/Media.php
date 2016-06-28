<?php

namespace Rpe\PumBundle\Extension\LinkPreview;

class Media {

    /** Return iframe code for Youtube videos */
    static public function mediaYoutube($url) {
        $media = array();
        if (preg_match("/(.*?)v=(.*?)($|&)/i", $url, $matching)) {
            $vid = $matching[2];
            array_push($media, "http://i2.ytimg.com/vi/$vid/hqdefault.jpg");
            array_push($media, '<iframe id="' . date("YmdHis") . $vid . '" width="%video_width%" height="%video_height%" src="http://www.youtube.com/embed/' . $vid . '%video_autostart%" allowfullscreen></iframe>');
        } else {
            array_push($media, "", "");
        }
        return $media;
    }

    /** Return iframe code for Vimeo videos */
    static public function mediaVimeo($url) {
        $url = str_replace("https://", "", $url);
        $url = str_replace("http://", "", $url);
        $breakUrl = explode("/", $url);
        $media = array();
        if ($breakUrl[1] != "") {
            $imgId = $breakUrl[1];
            $hash = unserialize(file_get_contents("http://vimeo.com/api/v2/video/$imgId.php"));
            array_push($media, $hash[0]['thumbnail_large']);
            array_push($media, '<iframe id="' . date("YmdHis") . $imgId . '" width="%video_width%" height="%video_height%" src="http://player.vimeo.com/video/' . $imgId . '%video_autostart%" webkitAllowFullScreen mozallowfullscreen allowFullScreen ></iframe>');
        } else {
            array_push($media, "", "");
        }
        return $media;
    }

    /** Return iframe code for Metacafe videos */
    static public function mediaMetacafe($url) {
        $media = array();
        preg_match('|metacafe\.com/watch/([\w\-\_]+)(.*)|', $url, $matching);
        if($matching[1]!="") {
            $vid = $matching[1];
            $vtitle=trim($matching[2], "/");
            array_push($media, "http://s4.mcstatic.com/thumb/{$vid}/0/6/videos/0/6/{$vtitle}.jpg");
            array_push($media, '<iframe id="' . date("YmdHis") . $vid . '" width="%video_width%" height="%video_height%" src="http://www.metacafe.com/embed/'.$vid.'%video_autostart%" allowFullScreen></iframe>');
        } else {
            array_push($media, "", "");
        }
        return $media;
    }

    /** Return iframe code for Dailymotion videos */
    static public function mediaDailymotion($url) {
        $media = array();
        $id = strtok(basename($url), '_');
        if($id!="")	{
            //$hash = file_get_contents("http://www.dailymotion.com/services/oembed?format=json&url=http://www.dailymotion.com/embed/video/$id");
            //$hash=json_decode($hash,true);
            //array_push($media, $hash['thumbnail_url']);

            array_push($media, "http://www.dailymotion.com/thumbnail/160x120/video/$id");
            array_push($media, '<iframe id="' . date("YmdHis") . $id . '" style="display: none; margin-bottom: 5px;" width="100%" height="368" src="http://www.dailymotion.com/embed/video/'.$id.'" allowFullScreen></iframe>');
        } else {
            array_push($media, "", "");
        }
        return $media;
    }

}
