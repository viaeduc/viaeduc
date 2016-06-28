<?php

namespace Rpe\PumBundle\Extension\Service;

class RssParser {

    protected $items_limit = 3;
    protected $date_format = 'd/m/Y h:i';

    protected $channeltags = array('title', 'link', 'description', 'image', 'language', 'copyright', 'managingEditor', 'webMaster', 'pubDate', 'lastBuildDate', 'rating', 'docs', 'ttl');
    protected $itemtags    = array('title', 'link', 'description', 'author', 'category', 'comments', 'enclosure', 'guid', 'pubDate', 'source');

    protected $cache_dir;
    protected $cache_time;

    public function __construct($cache_dir)
    {
        if (!is_dir($cache_dir)) {
            mkdir($cache_dir, 0777, true);
        }

        $this->cache_dir  = $cache_dir;
        $this->cache_time = 3600;
    }

    public function get($rss_url){
        if ($this->cache_dir) {
            $cache_file = $this->cache_dir . '/' . md5($rss_url);
            $timedif    = @(time() - filemtime($cache_file));

            if ($timedif < $this->cache_time) {
                $result = unserialize(join('', file($cache_file)));
                if ($result) {
                    $result['cached'] = 1;
                }
            } else {
                $result     = $this->parse($rss_url);
                $serialized = serialize($result);

                if ($f = @fopen($cache_file, 'w')) {
                    fwrite ($f, $serialized, strlen($serialized));
                    fclose($f);
                }

                if ($result) {
                    $result['cached'] = 0;
                }
            }
        } else {
            $result = $this->parse($rss_url);
            if ($result) {
                $result['cached'] = 0;
            }
        }

        return $result;
    }

    protected function parse($rss_url) {
        if (false !== $xmlstring = @file_get_contents($rss_url)) {
            $result = array();
            $xml  = @simplexml_load_string(file_get_contents($rss_url));
            if ($xml === false) {
                return false;
            }
            
            $json = json_encode(new \SimpleXMLElement($xml->asXML(), LIBXML_NOCDATA));
            $data = json_decode($json, TRUE);
            $data = $data['channel'];

            foreach ($this->channeltags as $tag) {
                if (isset($data[$tag])) {
                    switch ($tag) {
                        case 'pubDate':
                        case 'lastBuildDate':
                            $result[$tag] = $this->formatDate($data[$tag]);
                            break;

                        default:
                            $result[$tag] = $data[$tag];
                            break;
                    }
                }
            }

            $result['items'] = array();
            if (isset($data['item'])) {
                $count = 1;
                foreach ($data['item'] as $key => $item) {
                    if ($count > $this->items_limit) {
                        break;
                    }
                    foreach ($this->itemtags as $tag) {
                        if (isset($item[$tag])) {
                            switch ($tag) {
                                case 'pubDate':
                                    $result['items'][$key][$tag] = $this->formatDate($item[$tag]);
                                    break;

                                case 'enclosure':
                                    if (isset($item[$tag]['@attributes'])) {
                                        $result['items'][$key]['image'] = $item[$tag]['@attributes'];
                                    }

                                    break;

                                default:
                                    $result['items'][$key][$tag] = strip_tags((string)$item[$tag]);
                                    break;
                            }
                        }
                    }
                    if (!isset($result['items'][$key]['image']) && isset($result['image'])) {
                        $result['items'][$key]['image'] = $result['image'];
                    }
                    $count++;
                }
            }

            return $result;
        }

        return false;
    }

    protected function formatDate($date)
    {
        if ($this->date_format != '' && ($timestamp = strtotime($date)) !==-1) {
            return date($this->date_format, $timestamp);
        }

        return;
    }
}
