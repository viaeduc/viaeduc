<?php
namespace Rpe\PumBundle\Model\Social;

abstract class GroupWidget
{
    /**
     * Widget type
     */
    const TYPE_RSS      = 'rss';
    const TYPE_FACEBOOK = 'facebook';

    /**
     * Maximum widgets to create on a group by default
     * set in group meta
     */ 
    const DEFAULT_NB_MAX = 3;

    /**
     * Meta key
     */ 
    const META_DEFAULT_NB_MAX = 'META_DEFAULT_NB_MAX_';

}
