<?php

namespace Rpe\RestBundle\Extension\RestViewHandler;

use FOS\RestBundle\View\View;
use FOS\RestBundle\View\ViewHandler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class RssViewHandler
{
    public function createResponse(ViewHandler $handler, View $view, Request $request, $format)
    {
        $host = $request->getScheme().'://'.$request->getHost();
        $url = $host.$request->getPathInfo();

        $data = $view->getData();
        $parameters = $data['parameters'];
        $items = reset($data);
        
        $out = '<?xml version="1.0" encoding="ISO-8859-1"?>';
        $out .= '<rss version="2.0">';
        $out .= '<channel>';
        $out .= '<title>'.$parameters['title'].'</title>';
        $out .= '<description>'.$parameters['description'].'</description>';
        $out .= '<link>'.$url.'</link>';
        $out .= '<language>fr-FR</language>';
        
        if ($items) {
            foreach ($items  as $index => $item) {
                $out .= '<item>';
                $out .= '<title>'.$item['title'].'</title>';
                $out .= '<guid>'.$host.'/'.$parameters['pathname'].'/'.$item['id'].'</guid>';
                $out .= '<link>'.$host.'/'.$parameters['pathname'].'/'.$item['id'].'</link>';
                
                if (isset($item['description'])) {
                    $out .= '<description>'.$item['description'].'</description>';
                }
                
                if (null !== $item['pubDate']) {
                    $out .= '<pubDate>'.$item['pubDate']->format(\DateTime::RSS).'</pubDate>';
                }
                
                if (isset($item['author'])) {
                    $out .= '<author>'.$item['author'].'</author>';
                }
                
                if (isset($item['comments'])) {
                    $out .= '<comments>'.$item['comments'].'</comments>';
                }

                if (isset($item['category'])) {
                    $out .= '<category>'.$item['category'].'</category>';
                }
                
                $out .= '</item>';
            }
        }
        
        $out .= '</channel>';
        $out .= '</rss>';
        
        $view->setHeader('Content-type', 'application/rss+xml; charset=utf-8');
        
        return new Response($out, $view->getStatusCode(), $view->getHeaders());
    }
}
