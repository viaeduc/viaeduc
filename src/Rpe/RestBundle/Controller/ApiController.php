<?php

namespace Rpe\RestBundle\Controller;

use Rpe\PumBundle\Controller\Controller as RpeController;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use FOS\RestBundle\View\View;

class ApiController extends RpeController
{
    protected function getView($result, $statusCode = 200)
    {
        $view = View::create()
            ->setStatusCode($statusCode)
            ->setData($result)
        ;
        
        return $this->get('fos_rest.view_handler')->handle($view);
    }
    
    protected function getObjectsAction(Request $request, $object_type, $parameters = array())
    {
        $parameters['format'] = $request->attributes->get('_format');
        
        $count = 10;
        
        if(null !== $request->get('count')) {
            $count = $request->get('count');
        }
        
        $offset = 0;
        
        if(null !== $request->get('offset')) {
            $offset = $request->get('offset');
        }
        
        $objects = $this->getRepository($object_type)->getRest($count, $offset, $request->get('query'), $parameters);
        
        if ($parameters['format'] == 'rss') {
            $outObjects = $objects;
        } else {
            $outObjects = array();
            
            foreach($objects as $object) {
                $outObjects[] = $object->serializeList($this->get('type_extra.media.storage.driver'), $this->getRequest()->getSchemeAndHttpHost(), $parameters);
            }
        }
        
        $results = array();
        $results[$object_type] = $outObjects;
        $results['total_count'] = count($outObjects);
        $results['parameters'] = $parameters;
        
        return $this->getView($results);
    }
    
    protected function getObjectAction(Request $request, $object_type, $object_id, $parameters = array())
    {
        $object = $this->getRepository($object_type)->getOneRest($object_id);
        
        if (null === $object) {
            return $this->getNotFoundError();
        } else {
            return $this->getView($object->serialize($this->get('type_extra.media.storage.driver'), $this->getRequest()->getSchemeAndHttpHost(), $parameters));
        }
    }
    
    protected function getObjectLinkedObjectsAction(Request $request, $object_type, $link_type, $main_object_id, $parameters = array())
    {
        $parameters['format'] = $request->attributes->get('_format');
        
        $count = 10;
        
        if(null !== $request->get('count')) {
            $count = $request->get('count');
        }
        
        $offset = 0;
        
        if(null !== $request->get('offset')) {
            $offset = $request->get('offset');
        }
        
        $objects = $this->getRepository($object_type)->getLinkedRest($link_type, $main_object_id, $count, $offset, $request->get('query'), $parameters);
        
        if ($parameters['format'] == 'rss') {
            $outObjects = $objects;
        } else {
            $outObjects = array();
            
            foreach($objects as $object) {
                $outObjects[] = $object->serializeList($this->get('type_extra.media.storage.driver'), $this->getRequest()->getSchemeAndHttpHost());
            }
        }
        
        $results = array();
        $results[$object_type] = $outObjects;
        $results['total_count'] = count($outObjects);
        $results['parameters'] = $parameters;
        
        return $this->getView($results);
    }
    
    protected function initPostFields(Request $request, $fields)
    {
        foreach ($fields as $name => $params) {
            if (null !== ($get = $request->request->get($name))) {
                $fields[$name]['value'] = $get;
            } elseif (null !== ($file = $request->files->get($name))) {
                $fields[$name]['value'] = $file;
            } else {
                $fields[$name]['value'] = null;
            }
        }
        
        return $fields;
    }
    
    protected function checkRequiredPostFields($fields)
    {
        foreach ($fields as $name => $params) {
            if (isset($params['required']) && $params['required']) {
                if (!isset($params['value']) || null === $params['value']) {
                    return $this->getEmptyFieldError($name);
                }
            }
        }

        return true;
    }
}
