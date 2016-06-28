<?php
namespace Rpe\PumBundle\Extension\Service;

use Pum\Bundle\CoreBundle\PumContext;

/**
 * @method PumContext   __construct(PumContext $context)
 * @method PumContext   getPumContext()
 * @method Object       getOEM()
 * @method Object       getRepository($objectName)
 * @method Object       createObject($objectName)
 * @method void         persist()
 * @method void         remove()
 * @method void         flush()
 *
 */
class ContextFactory
{
    /**
     * @var PumContext  Pum context object
     */
    private $context;

    /**
     * Construct function
     * 
     * @access public
     * @param PumContext $context
     * 
     * @return void
     */
    public function __construct(PumContext $context)
    {
        $this->context = $context;
    }

    /**
     * Get pum context object
     * 
     * @access public
     * 
     * @return \Pum\Bundle\CoreBundle\PumContext
     */
    public function getPumContext()
    {
        return $this->context;
    }

    /**
     * Get pum context object
     *
     * @access public
     *
     * @return Object
     */
    public function getOEM()
    {
        return $this->context->getProjectOEM();
    }

    /**
     * Get repository object
     * 
     * @param string $objectName Object name
     * @access public
     *
     * @return Object repository object
     */
    public function getRepository($objectName)
    {
        return $this->getOEM()->getRepository($objectName);
    }

    /**
     * Create object
     *
     * @param string $objectName Object name
     * @access public
     *
     * @return Object
     */
    public function createObject($objectName)
    {
        return $this->getOEM()->createObject($objectName);
    }

    /**
     * Persist object
     
     * @access public
     * @return void
     */
    public function persist()
    {
        $objects = func_get_args();
        foreach ($objects as $object) {
            $this->getOEM()->persist($object);
        }

        return $this->getOEM();
    }

    /**
     * Remove object
      
     * @access public
     * @return void
     */
    public function remove()
    {
        $objects = func_get_args();
        foreach ($objects as $object) {
            $this->getOEM()->remove($object);
        }

        return $this->getOEM();
    }

    /**
     * Flush operations
    
     * @access public
     * @return void
     */
    public function flush()
    {
        return $this->getOEM()->flush();
    }
}
