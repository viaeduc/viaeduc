<?php
namespace Rpe\PumBundle\Extension\ElasticSearch;

use Symfony\Component\EventDispatcher\EventDispatcher;
use Pum\Core\Events;
use Pum\Core\ObjectFactory;
use Pum\Core\Event\ObjectEvent;
use Rpe\PumBundle\Model\Social\User;
use Rpe\PumBundle\Model\Social\Post;
use Rpe\PumBundle\Model\Social\Group;
use Rpe\PumBundle\Model\Social\Question;
use Rpe\PumBundle\Model\External\Notice;

/**
 * Index elastic search
 * 
 * @method void __construct(EventDispatcher $eventDispatcher, ObjectFactory $factory)
 * @method void update($obj)
 * @method void put($obj)
 * @method void delete($obj)
 *
 */
class IndexFactory
{
    /**
     * @var EventDispatcher $ObjectFactory
     */
    protected $ObjectFactory;

    /**
     * @var ObjectFactory   $factory
     */
    protected $factory;

    
    /**
     * construct class
     *  
     * @access public
     * @param EventDispatcher $eventDispatcher
     * @param ObjectFactory $factory
     * 
     * @return void
     */
    public function __construct(EventDispatcher $eventDispatcher, ObjectFactory $factory)
    {
        $this->eventDispatcher = $eventDispatcher;
        $this->factory         = $factory;
    }

    /**
     * update object
     *  
     * @access public
     * @param Object $obj   Object to update
     * 
     * @return void
     */
    public function update($obj)
    {
        switch (true) {
            case $obj instanceof User:
                // On user informations change updates all posts author/group/questions
                break;

            case $obj instanceof Post:
                break;

            case $obj instanceof Group:
                // On group access change updates all posts
                // On group access change updates all questions

                // On group name change updates all posts group
                // On group name change updates all questions group
                break;

            case $obj instanceof Question:
                break;
            case $obj instanceof Notice:
                break;
        }

        $this->put($obj);
    }

    /**
     * add object
     *
     * @access public
     * @param Object $obj   Object to add
     *
     * @return void
     */
    public function put($obj)
    {
        $this->eventDispatcher->dispatch(Events::OBJECT_UPDATE, new ObjectEvent($obj, $this->factory));
    }

    /**
     * delete object
     *
     * @access public
     * @param Object $obj   Object to delete
     *
     * @return void
     */
    public function delete($obj)
    {
        $this->eventDispatcher->dispatch(Events::OBJECT_DELETE, new ObjectEvent($obj, $this->factory));
    }
}
