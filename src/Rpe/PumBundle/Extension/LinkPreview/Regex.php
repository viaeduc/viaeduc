<?php

namespace Rpe\PumBundle\Extension\LinkPreview;

class Regex {

    public static $urlRegex = "/(https?\:\/\/|\s)[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,4})(\/+[a-z0-9_.\:\;-]*)*(\?[\&\%\|\+a-z0-9_=,\.\:\;-]*)?([\&\%\|\+&a-z0-9_=,\:\;\.-]*)([\!\#\/\&\%\|\+a-z0-9_=,\:\;\.-]*)}*/i";
    public static $imageRegex = "/<img(.*?)src=(\"|\')(.+?)(gif|jpg|png|bmp)(\"|\')(.*?)(\/)?>(<\/img>)?/";
    public static $imagePrefixRegex = "/\.(jpg|png|gif|bmp)$/i";
    public static $srcRegex = '/src=(\"|\')(.+?)(\"|\')/i';
    public static $httpRegex = "/https?\:\/\//i";
    public static $contentRegex1 = '/content="(.*?)"/i';
    public static $contentRegex2 = "/content='(.*?)'/i";
    public static $metaRegex= '/<meta(.*?)>/i';
    public static $titleRegex= "/<title(.*?)>(.*?)<\/title>/i";
    public static $scriptRegex= "/<script(.*?)>(.*?)<\/script>/i";

}
