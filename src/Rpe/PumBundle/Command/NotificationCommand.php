<?php
namespace Rpe\PumBundle\Command;

use Pum\Bundle\CoreBundle\Console\OutputLogger;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\HttpFoundation\Request;
use Rpe\PumBundle\Model\Social\Notification;
use Rpe\PumBundle\Model\Social\User;
use Rpe\PumBundle\Model\Social\Post;
use Rpe\PumBundle\Model\Social\Comment;
use Symfony\Component\Debug\Exception\FatalErrorException;
use Symfony\Component\Console\Input\InputOption;

/**
 * Treat all the fake notifications and create the real ones
 * 
 * php app/console rpe:notification:treat rpe-pum.loc.argosit.net
 *
 * @method void configure()
 * @method void execute(InputInterface $input, OutputInterface $output)
 * @method void treatWaitNotifs($em, $container, $wait)
 */
class NotificationCommand extends ContainerAwareCommand
{   
    // all the notification types
    private $typs = array(
        "heavy" => array(
            Notification::TYPE_PUBLICATION,
            Notification::TYPE_RESOURCE,
            Notification::TYPE_RESOURCE_EDIT
        )
    );
    
    /**
     * configurations for command
     *
     * @return  void
     * @see     \Symfony\Component\Console\Command\Command::configure()
     */
    protected function configure()
    {
        $this
            ->setName('rpe:notification:treat')
            ->setDescription('Treat notifications')
            ->addArgument('host', InputArgument::REQUIRED, 'Host name of site')
            ->addArgument('schema', InputArgument::OPTIONAL, 'Schema of url', 'http')
            ->addOption('mode', null, InputOption::VALUE_REQUIRED, 'Types of notification to treat')
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
        \Symfony\Component\Debug\ErrorHandler::register();
        gc_enable();
        $timestart  = microtime(true);
        
        $environment = $this->getContainer()->get('kernel')->getEnvironment();
        if ($environment == "dev") {
            $this->getContainer()->get('profiler')->disable();
        }

        $output->writeln('<info>Start treatment:
    --mode=light    => Treate light notifications.
    --mode=heavy    => Treate heavy notifications (Notifs may sending to all group members).</info>');

        $style = new \Symfony\Component\Console\Formatter\OutputFormatterStyle('green', 'black', array('bold'));
        $output->getFormatter()->setStyle('treated', $style);
        $style = new \Symfony\Component\Console\Formatter\OutputFormatterStyle('red', 'black', array('bold'));
        $output->getFormatter()->setStyle('not found', $style);

        $host_domain = $input->getArgument('host');
        $host_schema = $input->getArgument('schema');
        $base_url = $host_schema . "://" . $host_domain;

        $container              = $this->getContainer();
        $container->get('pum.context')->setProjectName('rpe');
        $container->get('twig')->addGlobal('schemeAndHttpHost', $base_url);
        
        $em                     = $container->get('em_factory')->getManager($container->get('pum'), 'rpe');
        $context                = $container->get('router')->getContext();
        $context->setHost($host_domain);
        $context->setScheme($host_schema);
        
        $em->getConnection()->getConfiguration()->setSQLLogger(null);
        $container->get('router')->setContext($context);
        $container->enterScope('request');
        $container->set('request', new Request(), 'request');

        gc_collect_cycles();
        $nbItemsByCycle     = 20;
        $nb_deleted         = 0;
        $repo_wait_notifs   = $em->getRepository('social_notification_wait');
        
        $mode = $input->getOption('mode');    
        switch ($mode) {
            case 'light':
                $operator = 'notin';
                $types = $this->typs['heavy'];
                break;
            case 'heavy':
                $operator = 'in';
                $types = $this->typs['heavy'];
                break;
            default:
                $operator = null;
                $types = null;
                break;
        };
        $notif_total = $repo_wait_notifs->getWaitingNotifs(false, null, null, $types, $operator);
        $count              = count($notif_total);
        $iteration          = ceil($count/$nbItemsByCycle);
        $output->writeln('<info>Notifs to be treated : '.$count.'</info>');
        
        
        if ($iteration > 0) {
            for ($i = 0; $i < $iteration; $i++) {
                $limit  = $nbItemsByCycle;
                $offset = $i*$nbItemsByCycle - $nb_deleted;
                
                foreach ($repo_wait_notifs->getWaitingNotifs(false, $limit, $offset, $types, $operator) as $wait_notif) {
                    $output->writeln('Start treating wait notif: <' . $wait_notif->getId() . '>');
                    try {
                        $this->treatWaitNotifs($em, $container, $wait_notif);
                        $container->get('pum.context')->getProjectOEM()->remove($wait_notif);
                        $nb_deleted++;
                    } catch (FatalErrorException $e) {
                        $output->writeln('<info>Error: ' . $e->getMessage() . '</info>');
                    } catch (\ErrorException $e) {
                        $output->writeln('<info>Error: ' . $e->getMessage() . '</info>');
                    } catch (\Exception $e) {
                        $output->writeln('<info>Error: ' . $e->getMessage() . '</info>');
                    }
                }
                $em->flush();
                $em->clear();
                gc_collect_cycles();
                $output->writeln(sprintf(PHP_EOL.'Memory usage : ' . number_format((memory_get_usage() / 1024/1024),3) . ' MO'));
                $output->writeln(sprintf("Script duration " . number_format(microtime(true)-$timestart, 3) . " seconds").PHP_EOL);
            }
        }
        $output->writeln('-----------------------------------');
        $output->writeln(sprintf("Script duration " . number_format(microtime(true)-$timestart, 3) . " seconds"));
        $output->writeln(sprintf('Memory usage : ' . number_format((memory_get_usage() / 1024/1024),3) . ' MO'));
    }
    
    /**
     * Treat the fake notifications according to type
     * 
     * @param Object $em            Entity manager instance
     * @param Object $container     Container instance
     * @param Object $wait          Fake notification instance
     * @return void
     */
    private function treatWaitNotifs($em, $container, $wait)
    { 
        $type       = $wait->getType();
        $actor      = $em->getRepository($wait->getActorType())->find($wait->getActorId());
        $actor_id   = isset($actor) ? $actor->getId() : null;
        
        $target     = $em->getRepository($wait->getTargetType())->find($wait->getTargetId());
        if ($wait->getReceiverId()) {
            $receiver   = $em->getRepository('user')->find($wait->getReceiverId());
        }
        if ($target == null || $actor == null) {
            return false;
        }
        
        switch ($type) {
            case Notification::TYPE_SHARE_RESOURCE:
                if (($author = $target->getAuthor()) !== $actor) {
                    $container->get('rpe.notifications')->create($author, $type, $actor, $target);
                }
            break;
            
            case Notification::TYPE_SHARE_PUBLICATION:
                if (($author = $target->getAuthor()) !== $actor) {
                    $container->get('rpe.notifications')->create($author, $type, $actor, $target);
                }
            break;
            
            case Notification::TYPE_COAUTHOR:
                $coAuthors = $target->getCoAuthors()->toArray();
                
                if (count($coAuthors)) {
                    $container->get('rpe.notifications')->create($coAuthors, $type, $actor, $target);
                }
            break;
            
            case Notification::TYPE_RESOURCE:
            case Notification::TYPE_RESOURCE_EDIT:                
                $notified = array($actor_id);
                $group = $target->getPublishedGroup();
                if ($group != null) {
                    foreach ($group->getMembers() as $uig) {
                        if ($member = $uig->getUser()) {
                            if (!in_array($member->getId(), $notified)) {
                                $container->get('rpe.notifications')->create($member, $type, $actor, $target);
                                $notified[] = $member->getId();
                            }
                        }
                    }
                }
                
                $coAuthors = $target->getCoAuthors()->toArray();
                if (count($coAuthors)) {
                    foreach ($coAuthors as $member) {
                        if (!in_array($member->getId(), $notified)) {
                            $container->get('rpe.notifications')->create($member, $type, $actor, $target);
                            $notified[] = $member->getId();
                        }
                    }
                }
            break;
            
            case Notification::TYPE_PUBLICATION:
                $group = $target->getPublishedGroup();
                $notified = array($actor_id);
                if ($group) {
                    $members = $group->getMembers();
                    foreach ($members as $uig) {
                        if ($member = $uig->getUser()) {
                            if (!in_array($member->getId(), $notified)) {
                                $container->get('rpe.notifications')->create($member, $type, $actor, $target);
                                $notified[] = $member->getId();
                            }
                        }
                    }   
                } else {
                    if (isset($receiver) && $receiver !== null) {
                        $container->get('rpe.notifications')->create($receiver, $type, $actor, $target);
                    }
                }
            break;
            
            case Notification::TYPE_EVENT_INVITATION:
                $uies = $target->getUsersInvited();
                foreach ($uies as $uie) {
                    if ($u = $uie->getUser()) {
                        $container->get('rpe.notifications')->create($u, $type, $actor, $target);
                    }
                }
            break;
            
            case Notification::TYPE_COMMENT:
                $u_to_notify = array();
                $notified = array($actor_id);
                $post = $target->getPost();
                
                if ($author = $post->getAuthor()) {
                    $u_to_notify[] = $author;
                }
                if ($target_user = $post->getTargetUser()) {
                    $u_to_notify[] = $target_user;
                }
                
                $recommends_post = $post->getRecommendBy();
                foreach ($recommends_post as $recommend) {
                    if ($user = $recommend->getUser()) {
                        $u_to_notify[] = $user;
                    }
                }
                
                $comments = $post->getComments();
                foreach ($comments as $comment) {
                    if ($user = $comment->getUser()) {
                        $u_to_notify[] = $user;
                    }
                }
                
                foreach ($u_to_notify as $u) {
                    if (!in_array($u->getId(), $notified)) {
                        $container->get('rpe.notifications')->create($u, $type, $actor, $target);
                        $notified[] = $u->getId();
                    }
                }
            break;
            
            case Notification::TYPE_RECOMMEND:
                $notified = array($actor_id);
                $u_to_notify = array();
                if ($target instanceof Post) {
                    $u_to_notify[] = $target->getAuthor();
                    $u_to_notify[] = $target->getTargetUser();
                }
                if ($target instanceof Comment) {
                    $u_to_notify[] = $target->getUser();
                    if ($post = $target->getPost()) {
                        $u_to_notify[] = $post->getTargetUser();
                    }
                }
                
                foreach ($u_to_notify as $u) {
                    if ($u instanceof User && !in_array($u->getId(), $notified)) {
                        $container->get('rpe.notifications')->create($u, $type, $actor, $target);
                        $notified[] = $u->getId();
                    };
                }
                
            break;
            
            case Notification::TYPE_JOIN_INVITE:
            case Notification::TYPE_JOIN_REQUEST:
            case Notification::TYPE_JOIN_USER_ACCEPT:
            case Notification::TYPE_JOIN_GROUP_ACCEPT:
            case Notification::TYPE_BECAME_ADMIN:
            case Notification::TYPE_RELATION_REQUEST:
            case Notification::TYPE_RELATION_REQUEST:
            case Notification::TYPE_RELATION_ACCEPT:
            case Notification::TYPE_JOIN_GROUP_ACCEPT:
                if (isset($receiver) && $receiver !== null) {
                    $container->get('rpe.notifications')->create($receiver, $type, $actor, $target);
                }
            break;
        }
    }
}
