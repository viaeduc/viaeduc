<?php
namespace Rpe\PumBundle\Model\Social;

use Doctrine\Common\Collections\Criteria;

/**
 * @method int      getUserAnswerCount($surveyAnswer)
 * @method boolean  userHasAnswered($user, $survey)
 * @method Criteria handleCriteria(Criteria $criteria, array $orderBy, $limite, $offset)
 * @method boolean  isActive()
 * 
 */
abstract class Survey
{
    /**
     * Get answers count for a survey
     * 
     * @param SurveyAnswer $surveyAnswer  The answer object
     * @return int
     * @access public 
     */
    public function getUserAnswerCount($surveyAnswer)
    {
        $criteria = Criteria::create();
        $criteria->andWhere(Criteria::expr()->eq('surveyAnswer', $surveyAnswer));
        $criteria = $this->handleCriteria($criteria, array(), null, null);
        
        return $this->usersAnswers->matching($criteria)->count();
    }

    /**
     * Check if a user has answered the survey
     * 
     * @param User         $user          The user object
     * @param Survey       $survey        The survey object
     * @return boolean 
     * 
     * @access public 
     */
    public function userHasAnswered($user, $survey)
    {
        $criteria = Criteria::create();
        $criteria->andWhere(Criteria::expr()->eq('user', $user));
        $criteria->andWhere(Criteria::expr()->eq('survey', $survey));
        $criteria = $this->handleCriteria($criteria, array(), null, null);
        
        return (bool)$this->usersAnswers->matching($criteria)->count();
    }
    
    /**
     * handleCriteria
     *
     * @access public
     * @param Criteria  $criteria  Criteria object
     * @param array     $orderBy   Array containing order 
     * @param int       $limit     Number of limit 
     * @param int       $offset    Offset start
     * 
     * @return Criteria 
     */
    private function handleCriteria(Criteria $criteria, array $orderBy, $limite, $offset)
    {
        if (null !== $limite) {
            $criteria->setMaxResults($limite);
        }

        if (null !== $offset) {
            $criteria->setFirstResult($offset);
        }

        if (null === $orderBy || empty($orderBy)) {
            $criteria->orderBy(array('id' => Criteria::DESC));
        } else {
            $criteria->orderBy($orderBy);
        }

        return $criteria;
    }
    
    /**
     * Check if a survey is active
     * 
     * @return boolean
     * @access public 
     */
    public function isActive()
    {
        if($this->getStartDate() <= new \DateTime() && $this->getEndDate() > new \DateTime()) {
            return true;
        }
        
        return false;
    }
}
