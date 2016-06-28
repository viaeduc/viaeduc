<?php

namespace Rpe\RestBundle\Extension\RestViewHandler;

use FOS\RestBundle\View\View;
use FOS\RestBundle\View\ViewHandler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class IcsViewHandler
{
    public function createResponse(ViewHandler $handler, View $view, Request $request, $format)
    {
        $data = $view->getData();
        $items = reset($data);

        $out = "BEGIN:VCALENDAR\n";
        $out .= "VERSION:2.0\n";
        $out .= "PRODID:-//hacksw/handcal//NONSGML v1.0//EN\n";

        foreach ($items as $index => $item) {
            foreach ($item as $key => $value) {
                if (is_string($value)) {
                    $value = str_replace(';', '\;', $value);
                    $value = str_replace(':', '\:', $value);
                    $item[$key] = str_replace(',', '\,', $value);
                }
            }

            $out .= "BEGIN:VEVENT\n";
            $out .= "UID:" . md5(uniqid(mt_rand(), true)).$item['event_id']."@viaeduc.fr\n";
            $out .= "DTSTAMP:".gmdate('Ymd').'T'.gmdate('His')."Z\n";
            $out .= "DTSTART:".$item['start']->format('Ymd\THis')."\n";
            $out .= "DTEND:".$item['end']->format('Ymd\THis')."\n";
            $out .= "SUMMARY:".$item['name']."\n";
            $out .= "DESCRIPTION:".$item['description']."\n";
            $out .= "LOCATION:".$item['location']."\n";
            $out .= "END:VEVENT\n";
        }

        $out .= "END:VCALENDAR";
        
        $view->setHeader('Content-type', 'text/calendar; charset=utf-8');

        return new Response($out, $view->getStatusCode(), $view->getHeaders());
    }
}
