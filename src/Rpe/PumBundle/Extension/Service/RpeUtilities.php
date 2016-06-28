<?php
namespace Rpe\PumBundle\Extension\Service;

use Pum\Bundle\CoreBundle\PumContext;

/**
 * @method void         __construct(PumContext $context)
 * @method PumContext   getContext()
 * @method string       getParameter($var)
 *
 */
class RpeUtilities
{
    /**
     * @param PumContext $pumContext
     */
    private $context;

    /**
     * Construct function
     *
     * @access public
     * @param PumContext $pumContext
     *
     * @return void
     */
    public function __construct(PumContext $context)
    {
        $this->context = $context;
    }

    /**
     * get context object
     *
     * @access public
     *
     * @return PumContext Return pum context
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * get parameter value from config files
     * ie: to get etherpad base_url value => pass args as this, getParameter('etherpad.base_url')
     * 
     * @access public
     * @param string $var 
     * 
     * @return string
     */ 
    public function getParameter($var)
    {
        try {
            // default container getParameter
            $value = $this->context->getContainer()->getParameter($var);
        } catch (\InvalidArgumentException $e) {
            // try getting param in parameters.yml using "." separator
            $vars  = explode('.', $var);
            $value = $this->context->getContainer()->getParameter(array_shift($vars));
            if (is_array($value)) {
                foreach ($vars as $key) {
                    if (isset($value[$key])) {
                        $value = $value[$key];
                    } else {
                        throw new \InvalidArgumentException(sprintf("The parameter \"%s\" is not defined.", $var));
                    }
                }
            }
        }
        return $value;
    }
}
