<?php
namespace Rpe\PumBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\HttpFoundation\Request;
use Rpe\PumBundle\Model\Rpe\EmailDomain;
use Rpe\PumBundle\Model\Social\User;
use Doctrine\ORM\Query;

class MailUserEnAttenteCommand extends ContainerAwareCommand
{
    /**
     * configurations for command
     *
     * @return  void
     * @see     \Symfony\Component\Console\Command\Command::configure()
     */
    protected function configure()
    {
        $this
            ->setName('rpe:email:userenattente')
            ->setDescription('Envoi message aux utilisateurs en attente')
            ->addArgument('host', InputArgument::REQUIRED, 'Host name of site')
            ->addArgument('schema', InputArgument::OPTIONAL, 'Schema of url', 'http')
        ;
    }

    /**
     * Command execution
     *
     * @param   InputInterface     $input  Input object for command
     * @param   OutputInterface    $output Output object for command
     * @return  void
     * @access  protected
     * @see     \Symfony\Component\Console\Command\Command::execute()
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        gc_enable();
        $timestart  = microtime(true);

        $output->writeln('<info>Start mails</info>');

        $style = new \Symfony\Component\Console\Formatter\OutputFormatterStyle('green', 'black', array('bold'));
        $output->getFormatter()->setStyle('green', $style);
        $style = new \Symfony\Component\Console\Formatter\OutputFormatterStyle('red', 'black', array('bold'));
        $output->getFormatter()->setStyle('red', $style);

        $host_domain = $input->getArgument('host');
        $host_schema = $input->getArgument('schema');
        $base_url = $host_schema . "://" . $host_domain . "/";

        $container              = $this->getContainer();
        $em                     = $container->get('em_factory')->getManager($container->get('pum'), 'rpe');
        
        $repo_user              = $em->getRepository('user');
        $users                  = $this->getUsersEnAttente($repo_user);
        
        $mailer                 = $container->get('rpe.mailer.nolog');
        $context                = $container->get('router')->getContext();

        $em->getConnection()->getConfiguration()->setSQLLogger(null);

        $context->setHost($host_domain);
        $context->setScheme($host_schema);
        $container->get('router')->setContext($context);
        $container->enterScope('request');
        $container->set('request', new Request(), 'request');

        gc_collect_cycles();
        
        $countSent      = 0;
        $nbItemsByCycle = 50;
        $count          = count($users);
        $iteration      = ceil($count/$nbItemsByCycle);

        $output->writeln('<info>Account to be treated : '.$count.'</info>');

        if ($iteration > 0) {
            for ($i = 0; $i < $iteration; $i++) {
                $limit  = $nbItemsByCycle;
                $offset = $i*$nbItemsByCycle;

                foreach ($this->getUsersEnAttente($repo_user, false, $limit, $offset) as $user) {
                    if ($this->authorizedEmailDomain($user['emailPro'])) {
                        $output->writeln('<green>User to send mail: ('. $user['lastname'] .') ' . $user['emailPro'] . '</green>');
                        
                        if(filter_var($user['emailPro'], FILTER_VALIDATE_EMAIL)) {
                            $mailer->send(array(
                                'subject'      => 'Votre inscription à Viaéduc attend votre confirmation',
                                'from'         => 'bienvenue@viaeduc.fr',
                                'to'           => $user['emailPro'],
                                
                                'template' => array(
                                    'name'     => 'pum://emails/user_en_attente.html.twig',
                                    'vars'     => array(
                                        'activeLink' => $container->get('router')->generate('email_confirmation', array('id' => $user['id'], 'key' => $user['validationKey']), UrlGeneratorInterface::ABSOLUTE_URL)
                                    )
                                ),
                                'type'         => 'text/plain'
                            ));
                            $countSent++;
                        }
                    } else {
                        $output->writeln('<red>User domain not authorized: ('. $user['lastname'] .') ' . $user['emailPro'] . '</red>');
                    }   
                }

                $em->clear();
                gc_collect_cycles();
//                 $output->writeln(sprintf(PHP_EOL.'Memory usage : ' . number_format((memory_get_usage() / 1024/1024),3) . ' MO'));
//                 $output->writeln(sprintf("Script duration " . number_format(microtime(true)-$timestart, 3) . " seconds").PHP_EOL);
            
            }

//         $output->writeln(sprintf("Script duration " . number_format(microtime(true)-$timestart, 3) . " seconds"));
//         $output->writeln(sprintf('Memory usage : ' . number_format((memory_get_usage() / 1024/1024),3) . ' MO'));
        $output->writeln(sprintf('Sent emails : "%s"', $countSent));
    }
    }

    private function getUsersEnAttente($repo, $count = false, $maxResults = null, $firstResult = null)
    {
        $qb = $repo->createQueryBuilder('u');
        
        if ($count == true) {
            $qb->select('count(DISTINCT u.id)');
        } else {
            $qb->select('DISTINCT partial u.{id, firstname, lastname, status, type, date, emailPro, validationKey}');
        }
        $qb
            ->addSelect('')
            ->andWhere($qb->expr()->eq('u.status', ':status'))
            ->andWhere($qb->expr()->in('u.type', ':type'))
            ->andWhere($qb->expr()->gte('u.date', ':date'))
            ->setParameters(array(
                'status' => User::STATUS_TYPE_AWAITING_CONFIRMATION,
                'type'   => array(User::TYPE_COMMON, User::TYPE_INVITED),
                'date'   => new \DateTime('2015-01-01 00:00:00')
            ))
            ->orderBy('u.id', 'ASC');
            
//         echo $qb->getQuery()->getSql();
        if (null !== $maxResults) {
            $qb->setMaxResults($maxResults);
        }
        
        if (null !== $firstResult) {
            $qb->setFirstResult($firstResult);
        }
        
        if ($count == true) {
            return $qb->getQuery()->getSingleScalarResult();
        } else {
            return $qb->getQuery()->getResult(Query::HYDRATE_ARRAY);
        }
    }
    
    
    private function authorizedEmailDomain($email)
    {
        $container              = $this->getContainer();
        $em                     = $container->get('em_factory')->getManager($container->get('pum'), 'rpe');
        $domaines = $em->getRepository('email_domain')->findAll();
        // If email_domain is empty we accept all email domain
        if (empty($domaines)) {
            return true;
        }
        foreach ($domaines as $domain) {
            switch ($domain->getType()) {
                case EmailDomain::TYPE_ENTITY:
                    if (trim($email) === $domain->getDomain()) {
                        return true;
                    }
                    break;
                case EmailDomain::TYPE_DOMAIN:
                default:
                    if (false !== strpos($email, $domain->getDomain())) {
                        return true;
                    }
                    break;
            }
        }
        return false;
    }
}
