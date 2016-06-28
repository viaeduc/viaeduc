<?php
namespace Rpe\PumBundle\Repository\Social;

use Pum\Core\Object\ObjectRepository;
use Rpe\PumBundle\Model\Social\Question;
use Rpe\PumBundle\Model\Social\Friend;

/**
 * @method  \Doctrine\ORM\mixed      getSingleQuestion($id, $user, $returnQuery = false)
 * @method  \Doctrine\ORM\Query|multitype:      getSuggestedQuestions($user, $returnQuery = false, $maxResults = null, $firstResult = null)
 * @method  \Doctrine\ORM\Query|multitype:      getAllQuestionsForGroup($group, $returnQuery = false, $maxResults = null, $firstResult = null)
 * @method  \Doctrine\ORM\Query|multitype:      getAllOtherQuestionsForUser($user, $excludeQuestion, $returnQuery = false, $maxResults = null, $firstResult = null)
 * @method  \Doctrine\ORM\Query|multitype:      getAllQuestionsForUser($user, $returnQuery = false, $maxResults = null, $firstResult = null)   
 * @method  \Doctrine\ORM\mixed                 getLinkedRest($link_type, $main_object_id, $limit = 10, $offset = 0, $keyword = null, $restParameters = array())       
 * @method  Question|null                       getOneRest($id)
 * @method  array                               getRest($limit = 10, $offset = 0, $keyword = null, $restParameters = array())   
 * @method  \Doctrine\ORM\mixed                 getLastResponseQuestion($maxresult = NULL)
 * @method  \Doctrine\ORM\mixed                 getQuestionsCount($startDate = null, $endDate = null)
 * 
 */
class QuestionRepository extends ObjectRepository
{
    
    /**
     * get single question for user
     * 
     * @param string    $id
     * @param User      $user   User object
     * @param boolean   $returnQuery    Return query or not
     * 
     * @return \Doctrine\ORM\mixed
     */
    public function getSingleQuestion($id, $user, $returnQuery = false)
    {
        // Get the correct Resource
        $qb = $this->createQueryBuilder('q');
        $question = $qb
            ->andWhere($qb->expr()->eq('q.id', ':id'))
            ->andWhere($qb->expr()->eq('q.author', ':user'))
            ->setParameters(array(
                'id'                => $id,
                'user'              => $user
            ))
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        return $question;
    }

    
    /**
     * get all questions for an user
     * 
     * @param User      $user   User object
     * @param boolean   $returnQuery    Return query or not
     * @param int       $maxResults     Maximum return result
     * @param int       $firstResult    Get first result or not
     * 
     * @return \Doctrine\ORM\Query|multitype:
     */
    public function getAllQuestionsForUser($user, $returnQuery = false, $maxResults = null, $firstResult = null)
    {
        $qb = $this->createQueryBuilder('q');
        $questions = $qb
            ->andWhere(
                $qb->expr()->andX(
                    $qb->expr()->orX(
                        $qb->expr()->eq('q.accesstype', ':acces_public'),
                        $qb->expr()->eq('q.author', ':user')
                    )
                )
            )
            ->setParameters(array(
                'user'         => $user,
                'acces_public' => Question::ACCESS_PUBLIC,
            ))
            ->orderBy('q.id', 'DESC')
        ;
        
        if (null !== $maxResults) {
            $questions->setMaxResults($maxResults);
        }

        if (null !== $firstResult) {
            $questions->setFirstResult($firstResult);
        }

        if ($returnQuery) {
            return $questions->getQuery();
        }

        return $questions->getQuery()->getResult();
    }

    /**
     * get all other questions for an user
     *
     * @param User      $user   User object
     * @param array     $excludeQuestion   Array of questions
     * @param boolean   $returnQuery    Return query or not
     * @param int       $maxResults     Maximum return result
     * @param int       $firstResult    Get first result or not
     *
     * @return \Doctrine\ORM\Query|multitype:
     */
    public function getAllOtherQuestionsForUser($user, $excludeQuestion, $returnQuery = false, $maxResults = null, $firstResult = null)
    {
        $qb = $this->createQueryBuilder('q');
        $questions = $qb
            ->andWhere(
                $qb->expr()->andX(
                    $qb->expr()->neq('q', ':question'),
                    $qb->expr()->orX(
                        $qb->expr()->eq('q.accesstype', ':acces_public'),
                        $qb->expr()->eq('q.author', ':user')
                    )
                )
            )
            ->setParameters(array(
                'user'         => $user,
                'acces_public' => Question::ACCESS_PUBLIC,
                'question'     => $excludeQuestion
            ))
            ->orderBy('q.id', 'DESC')
        ;

        if (null !== $maxResults) {
            $questions->setMaxResults($maxResults);
        }

        if (null !== $firstResult) {
            $questions->setFirstResult($firstResult);
        }

        if ($returnQuery) {
            return $questions->getQuery();
        }

        return $questions->getQuery()->getResult();
    }

    /**
     * get all questions for a group
     *
     * @param Group     $group          Group object
     * @param boolean   $returnQuery    Return query or not
     * @param int       $maxResults     Maximum return result
     * @param int       $firstResult    Get first result or not
     *
     * @return \Doctrine\ORM\Query|multitype:
     */
    public function getAllQuestionsForGroup($group, $returnQuery = false, $maxResults = null, $firstResult = null)
    {
        $qb = $this->createQueryBuilder('q');

        $questions = $qb
            ->andWhere('q.publishedGroup = :group')
            ->setParameters(array(
                'group'         => $group
            ))
            ->orderBy('q.id', 'DESC')
        ;

        if (null !== $maxResults) {
            $questions->setMaxResults($maxResults);
        }

        if (null !== $firstResult) {
            $questions->setFirstResult($firstResult);
        }

        if ($returnQuery) {
            return $questions->getQuery();
        }

        return $questions->getQuery()->getResult();
    }

    /**
     * get suggested questions
     *
     * @param User      $user   User object
     * @param boolean   $returnQuery    Return query or not
     * @param int       $maxResults     Maximum return result
     * @param int       $firstResult    Get first result or not
     *
     * @return \Doctrine\ORM\Query|multitype:
     */
    public function getSuggestedQuestions($user, $returnQuery = false, $maxResults = null, $firstResult = null)
    {
        $qb = $this->createQueryBuilder('q');

        $questions = $qb
            ->leftJoin('q.publishedGroup', 'g')
            ->leftJoin('g.users', 'uig', 'WITH', 'uig.user = :user')
            ->leftJoin('q.answers', 'a', 'WITH', 'a.author <> :user')
            ->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->andX(
                        $qb->expr()->eq('q.accesstype', ':access_public'),
                        $qb->expr()->neq('q.author', ':user'),
                        $qb->expr()->isNull('a')
                    ),
                    $qb->expr()->andX(
                        $qb->expr()->eq('q.accesstype', ':access_group'),
                        $qb->expr()->isNotNull('uig')
                    )
                )
            )
            ->setParameters(array(
                'user'          =>  $user,
                'access_public'  =>  Question::ACCESS_PUBLIC,
                'access_group'  =>  Question::ACCESS_GROUP
            ))
        ;

        if (null !== $maxResults) {
            $questions->setMaxResults($maxResults);
        }

        if (null !== $firstResult) {
            $questions->setFirstResult($firstResult);
        }

        if ($returnQuery) {
            return $questions->getQuery();
        }

        return $questions->getQuery()->getResult();
    }

    /**
     * Count number of questions
     * 
     * @access public
     * @param string    $startDate      Start date of creation
     * @param string    $endDate        End date of creation
     * 
     * @return \Doctrine\ORM\mixed
     */
    public function getQuestionsCount($startDate = null, $endDate = null)
    {
        $parameters = array();

        if (null !== $startDate) {
            $parameters['startDate'] = $startDate;
        }

        if (null !== $endDate) {
            $parameters['endDate'] = $endDate;
        }

        $users = $this
            ->createQueryBuilder('q')
            ->select('COUNT(q.id)')
            ->setParameters($parameters)
        ;

        if (null !== $startDate && null !== $endDate) {
            $users->andWhere('q.date >= :startDate');
            $users->andWhere('q.date <= :endDate');
        } elseif (null !== $startDate) {
            $users->andWhere('q.date >= :startDate');
        } elseif (null !== $endDate) {
            $users->andWhere('q.date <= :endDate');
        }

        return $users
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }
    
    /**
     * get the last response of question
     *
     * @access public
     * @param int       $maxResults     Maximum return result
     *
     * @return \Doctrine\ORM\mixed
     */
    public function getLastResponseQuestion($maxresult = NULL)
	{
       $last = $this->createQueryBuilder('q')
           ->select('q')
           ->leftJoin('q.answers', 'a')
           ->orderBy('a.date', "DESC")
           ->setMaxResults($maxresult)
           ->getQuery()
           ->getResult()
       ;

       return $last;
	}
    
    /**
     * Get rest questions
     *
     * @access public
     * @param number $limit    Limit of items
     * @param number $offset   Start offset of items
     * @param string $keyword  Keyword for search if exist
     * @param array  $restParameters  Other params
     * 
     * @return array
     */
    public function getRest($limit = 10, $offset = 0, $keyword = null, $restParameters = array())
    {
        $parameters = array();
        
        if (null !== $keyword) {
            $parameters['keyword'] = '%'.$keyword.'%';
        }
        
        $qb = $this->createQueryBuilder('q')
            ->leftJoin('q.author', 'u')
        ;
        
        if ($restParameters['format'] == 'rss') {
            $qb->select('q.id AS id');
            $qb->addSelect('q.name AS title');
            $qb->addSelect('q.description AS description');
            $qb->addSelect('q.date AS pubDate');
            $qb->addSelect('CONCAT(u.firstname, \' \', u.lastname) AS author');
        }
        
        if (null !== $keyword) {
            $qb->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->like('u.firstname', ':keyword'),
                    $qb->expr()->like('u.lastname', ':keyword')
                )
            );
        }
        
        $qb = $qb
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->setParameters($parameters)
            ->getQuery()
            ->getResult()
        ;
        
        return $qb;
    }
    
    /**
     * Get the one rest question by id
     * 
     * @access public
     * @param string    $id     Question id
     * 
     * @return Question|null 
     */
    public function getOneRest($id)
    {
        $user = $this
            ->createQueryBuilder('u')
            ->andWhere('u.id = :id')
            ->setParameters(array('id' => $id))
            ->getQuery()
            ->getOneOrNullResult()
        ;
        
        return $user;
    }
    
    /**
     * get all, linked objects
     *
     * @access public
     * @param string $link_type         Link type
     * @param string $main_object_id    Name of the object
     * @param string $limit             Limit of result
     * @param string $offset            Start offset for search
     * @param string $keyword           Search keywork if exist
     * @param array  $restParameters  Other parameters
     *
     * @return \Doctrine\ORM\mixed
     */
    public function getLinkedRest($link_type, $main_object_id, $limit = 10, $offset = 0, $keyword = null, $restParameters = array())
    {
        if($link_type == 'user_questions') {
            $parameters = array();
            $parameters['user'] = $main_object_id;
            
            if (null !== $keyword) {
                $parameters['keyword'] = '%'.$keyword.'%';
            }
            
            $qb = $this->createQueryBuilder('q')
                ->leftJoin('q.author', 'u')
                ->andWhere('q.author = :user')
            ;
            
            if ($restParameters['format'] == 'rss') {
                $qb->select('q.id AS id');
                $qb->addSelect('q.name AS title');
                $qb->addSelect('q.description AS description');
                $qb->addSelect('q.date AS pubDate');
                $qb->addSelect('CONCAT(u.firstname, \' \', u.lastname) AS author');
            }
            
            if (null !== $keyword) {
                $qb->andWhere(
                    $qb->expr()->orX(
                        $qb->expr()->like('u.firstname', ':keyword'),
                        $qb->expr()->like('u.lastname', ':keyword')
                    )
                );
            }
            
            $qb = $qb
                ->setFirstResult($offset)
                ->setMaxResults($limit)
                ->setParameters($parameters)
                ->getQuery()
                ->getResult()
            ;
            
            return $qb;
        } elseif($link_type == 'user_friends_questions') {
            $parameters = array();
            $parameters['user'] = $main_object_id;
            $parameters['friend_status'] = Friend::STATUS_ACCEPTED;
            
            if (null !== $keyword) {
                $parameters['keyword'] = '%'.$keyword.'%';
            }
            
            $qb = $this->createQueryBuilder('q')
                ->leftJoin('q.author', 'u')
                ->leftJoin('u.friends', 'f')
                ->andWhere('f.friend = :user')
                ->andWhere('f.status = :friend_status')
            ;
            
            if ($restParameters['format'] == 'rss') {
                $qb->select('q.id AS id');
                $qb->addSelect('q.name AS title');
                $qb->addSelect('q.description AS description');
                $qb->addSelect('q.date AS pubDate');
                $qb->addSelect('CONCAT(u.firstname, \' \', u.lastname) AS author');
            }
            
            if (null !== $keyword) {
                $qb->andWhere(
                    $qb->expr()->orX(
                        $qb->expr()->like('u.firstname', ':keyword'),
                        $qb->expr()->like('u.lastname', ':keyword')
                    )
                );
            }
            
            $qb = $qb
                ->setFirstResult($offset)
                ->setMaxResults($limit)
                ->setParameters($parameters)
                ->getQuery()
                ->getResult()
            ;
            
            return $qb;
        } elseif($link_type == 'group_questions') {
            $parameters = array();
            $parameters['group'] = $main_object_id;
            
            if (null !== $keyword) {
                $parameters['keyword'] = '%'.$keyword.'%';
            }
            
            $qb = $this->createQueryBuilder('q')
                ->andWhere('q.publishedGroup = :group')
                ->leftJoin('q.author', 'u')
            ;
            
            if ($restParameters['format'] == 'rss') {
                $qb->select('q.id AS id');
                $qb->addSelect('q.name AS title');
                $qb->addSelect('q.description AS description');
                $qb->addSelect('q.date AS pubDate');
                $qb->addSelect('CONCAT(u.firstname, \' \', u.lastname) AS author');
            }
            
            /*if (null !== $keyword) {
                $qb->andWhere(
                    $qb->expr()->orX(
                        $qb->expr()->like('u.firstname', ':keyword'),
                        $qb->expr()->like('u.lastname', ':keyword')
                    )
                );
            }*/
            
            $qb = $qb
                ->setFirstResult($offset)
                ->setMaxResults($limit)
                ->setParameters($parameters)
                ->getQuery()
                ->getResult()
            ;
            
            return $qb;
        } elseif($link_type == 'page_questions') {
            $parameters = array();
            $parameters['page'] = $main_object_id;
            
            if (null !== $keyword) {
                $parameters['keyword'] = '%'.$keyword.'%';
            }
            
            $qb = $this->createQueryBuilder('q')
                ->andWhere('q.publishedEditor = :page')
                ->andWhere('q.status = :status')
                ->leftJoin('q.author', 'u')
            ;
            
            if ($restParameters['format'] == 'rss') {
                $qb->select('q.id AS id');
                $qb->addSelect('q.name AS title');
                $qb->addSelect('q.description AS description');
                $qb->addSelect('q.date AS pubDate');
                $qb->addSelect('CONCAT(u.firstname, \' \', u.lastname) AS author');
            }
            
            /*if (null !== $keyword) {
                $qb->andWhere(
                    $qb->expr()->orX(
                        $qb->expr()->like('u.firstname', ':keyword'),
                        $qb->expr()->like('u.lastname', ':keyword')
                    )
                );
            }*/
            
            $qb = $qb
                ->setFirstResult($offset)
                ->setMaxResults($limit)
                ->setParameters($parameters)
                ->getQuery()
                ->getResult()
            ;
            
            return $qb;
        }
    }
}
