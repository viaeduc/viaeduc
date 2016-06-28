<?php
namespace Rpe\PumBundle\Repository\Social;

use Pum\Core\Object\ObjectRepository;

/**
 * @method array    getGoodAnswers($question)
 * @method int      getAnswersCount($startDate = null, $endDate = null)
 */
class AnswerRepository extends ObjectRepository
{
    /**
     * Get good answers for a question
     *
     * @access public
     * @param Question $question  The question object
     *
     * @return array
     */
    public function getGoodAnswers($question)
    {
        $qb = $this->createQueryBuilder('a');
        $answers = $qb
            ->select('a', 'COUNT(rb) AS HIDDEN rbcounter')
            ->leftJoin('a.recommendby', 'rb')
            ->andWhere($qb->expr()->eq('a.question', ':question'))
            ->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->eq('a.isGood', true),
                    $qb->expr()->andX(
                        $qb->expr()->neq('a.isGood', true),
                        $qb->expr()->isNotNull('rb')
                    )
                )
            )
            ->setParameters(array(
                'question'  => $question
            ))
            ->orderBy('a.isGood', 'DESC')
            ->addOrderBy('rbcounter', 'DESC')
            ->groupBy('a')
            ->setMaxResults(2)
            ->getQuery()
            ->getArrayResult()
        ;

        return $answers;
    }

    /**
     * Get answers count
     *
     * @access public
     * @param string $startDate     Start date of answer
     * @param string $endDate       End date of answer
     *
     * @return int
     */
    public function getAnswersCount($startDate = null, $endDate = null)
    {
        $parameters = array();

        if (null !== $startDate) {
            $parameters['startDate'] = $startDate;
        }

        if (null !== $endDate) {
            $parameters['endDate'] = $endDate;
        }

        $answers = $this
            ->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->setParameters($parameters)
        ;

        if (null !== $startDate && null !== $endDate) {
            $answers->andWhere('a.date >= :startDate');
            $answers->andWhere('a.date <= :endDate');
        } elseif (null !== $startDate) {
            $answers->andWhere('a.date >= :startDate');
        } elseif (null !== $endDate) {
            $answers->andWhere('a.date <= :endDate');
        }

        return $answers
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }
}
