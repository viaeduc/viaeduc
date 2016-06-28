<?php
namespace Rpe\PumBundle\Extension\Twig;

use Rpe\PumBundle\Extension\Object\ObjectFetcher;
use Rpe\PumBundle\Extension\Service\RpeUtilities;
use Rpe\PumBundle\Extension\Service\RpeHashId;
use Rpe\PumBundle\Model\Social\User;

/**
 * @method void __construct(ObjectFetcher $objectFetcher, RpeUtilities $rpeUtils)
 * @method string getName()
 * @method array  getFunctions()
 *
 */
class RpeFunctions extends \Twig_Extension
{
    /**
     * @var Object $objectFetcher
     * @var Object $rpeUtils
     */
    protected $objectFetcher;
    protected $rpeUtils;
    protected $rpeHash;


    /**
     * Construct function
     *
     * @access public
     * @param ObjectFetcher     $objectFetcher
     * @param RpeUtilities      $rpeUtils
     *
     * @return void
     */
    public function __construct(ObjectFetcher $objectFetcher, RpeUtilities $rpeUtils, RpeHashId $rpeHash)
    {
        $this->objectFetcher = $objectFetcher;
        $this->rpeUtils      = $rpeUtils;
        $this->rpeHash       = $rpeHash;
    }

    /**
     * Get name of function
     *
     * @return string
     * @see Twig_ExtensionInterface::getName()
     */
    public function getName()
    {
        return 'rpe_twig_functions';
    }

    /**
     * Get list of functions
     *
     * @see Twig_Extension::getFunctions()
     */
    public function getFunctions()
    {
        return array(
            new \Twig_SimpleFunction('isImage', function ($mime) {
                return in_array(strtolower($mime), array('image/gif','image/png','image/jpg','image/jpeg'));
            }),
            new \Twig_SimpleFunction('toSlug', function ($str) {
                return $str;
            }),
            new \Twig_SimpleFunction('relationCount', function ($objectName, $relationName, $id) {
                return (int)$this->objectFetcher->countRelation($objectName, $relationName, $id);
            }),
            new \Twig_SimpleFunction('isLike', function ($user, $objectName, $id) {
                return (boolean)$this->objectFetcher->isLike($user->getId(), $objectName, $id);
            }),
            new \Twig_SimpleFunction('isBookmark', function ($user, $objectName, $id) {
                return (boolean)$this->objectFetcher->isBookmark($user->getId(), $objectName, $id);
            }),
            new \Twig_SimpleFunction('getRelation', function ($objectName, $relationName, $id, $select = null) {
                return $this->objectFetcher->getRelation($objectName, $relationName, $id, $select);
            }),
            new \Twig_SimpleFunction('getEntity', function ($objectName, $id, $select = null) {
                return $this->objectFetcher->getEntity($objectName, $id, $select);
            }),
            new \Twig_SimpleFunction('getAppParameter', function ($paramName) {
                return $this->rpeUtils->getParameter($paramName);
            }),
            new \Twig_SimpleFunction('hashEncode', function ($id) {
                return $this->rpeHash->encode($id);
            })
        );
    }
}
