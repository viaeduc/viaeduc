<?php
namespace Rpe\PumBundle\Model\Social;

/**
 * @method boolean canDelete()
 *
 */
abstract class UserWidget
{
    /**
     * Widget type
     */
    const TYPE_RSS      = 'rss';
    const TYPE_FACEBOOK = 'facebook';
    const TYPE_TWITTER  = 'twitter';

    /**
     * @var array $defaultWidgets Defautl user widgets
     */
    public static $defaultWidgets = array(
        "last_groups",
        "suggesting_friendship",
        "discussion_favorite",
        "popular_posts",
        "posts_bookmark",
        "groups_bookmark",
        "blogs_bookmark",
        "agenda",
        "awaiting_frienship",
        "my_groups",
        "news",
        "drafts",
        "suggested_posts"
    );
    
    public static $permanentWidgets = array(
      "suggested_posts"  
    );

    /**
     * canDelete
     * 
     * @access public
     * @return boolean 
     */
    public function canDelete()
    {
        return !(in_array($this->getType(), self::$defaultWidgets));
    }
    
    /**
     * CanDisable
     *
     * @access public
     * @return boolean
     */
    public function CanDisable()
    {
        return !(in_array($this->getType(), self::$permanentWidgets));
    }    
}
