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
use Symfony\Component\Console\Input\InputOption;

/**
 * Clear notifications of users, keep 20 last notifs for each user
 * 
 * php app/console rpe:notification:clearbyuser
 *
 * @method void configure()
 * @method void execute(InputInterface $input, OutputInterface $output)
 * 
 */
class ClearNotificationUserCommand extends ContainerAwareCommand
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
            ->setName('rpe:notification:clearbyuser')
            ->setDescription('Clear notifications, keep 20 notifications for each user')
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
        $environment = $this->getContainer()->get('kernel')->getEnvironment();
        if ($environment == "dev") {
            $this->getContainer()->get('profiler')->disable();
        }

        $style = new \Symfony\Component\Console\Formatter\OutputFormatterStyle('green', 'black', array('bold'));
        $output->getFormatter()->setStyle('info', $style);
        $style = new \Symfony\Component\Console\Formatter\OutputFormatterStyle('red', 'black', array('bold'));
        $output->getFormatter()->setStyle('error', $style);

        $container              = $this->getContainer();
        $container->get('pum.context')->setProjectName('rpe');
        $em                     = $container->get('em_factory')->getManager($container->get('pum'), 'rpe');
        $context                = $container->get('router')->getContext();

        $repo_notif = $em->getRepository('notification');
        $countNotifsBefore   = $repo_notif->getNotificationsBefore(null, null, null, true);
        
        $em->getConnection()->getConfiguration()->setSQLLogger(null);
        
        gc_collect_cycles();
        $nbItemsByCycle     = 100;
        $iteration          = ceil($countNotifsBefore/$nbItemsByCycle);
        $output->writeln('<info>Notifs to be counted : '. $countNotifsBefore .'</info>');
        
        $currentUser = null;
        $tempUser = null;
        $currentCount = 0;
        $nb_deleted = 0;
        
        if ($iteration > 0) {
            for ($i = 0; $i < $iteration; $i++) {
                $limit  = $nbItemsByCycle;
                $offset = $i*$nbItemsByCycle - $nb_deleted;
                
                foreach ($repo_notif->getNotificationsBefore(null, $limit, $offset) as $notif) {
                    $tempUser = $notif->getUser();
                    if ($tempUser === null) {
                        $em->remove($notif);
                        $em->flush();
                        $nb_deleted++;
                        $output->writeln('######## NO OWNER ##### Notification DELETED: <' . $notif->getId() . '>');
                    } else {
                        $currentCount++;
                        if ($currentUser === null) {
                            $currentUser = $tempUser;
                        }
                        
                        $tempId = $tempUser->getId();
                        $currentId = $currentUser->getId();
                        if ($tempUser !== null && $tempId != $currentId) {
                            $currentUser = $tempUser;
                            $currentCount = 0;
                        }
//                         $output->writeln('Notification : <' . $notif->getId() . '> ' . $currentCount . ' , user : ' . $tempUser->getFullname() . ' (' . $tempUser->getId() . ') currentid: ' . $currentId);
                        
                        if ($currentCount > 20) {
                            $output->writeln('DELETE notification : <' . $notif->getId() . '> ' . $currentCount . ' , user : ' . $tempUser->getFullname() . ' (' . $tempUser->getId() . ') currentid: ' . $currentId);
                            $em->remove($notif);
                            $em->flush();
                            $nb_deleted++;
                        } else { // do nothing if less than 20 notifications
                            $output->writeln('KEEP notification : <' . $notif->getId() . '> ' . $currentCount . ' , user : ' . $tempUser->getFullname() . ' (' . $tempUser->getId() . ') ' . $currentCount . " current user: " . $currentUser->getId());
                        }
                    }
                }
                $em->clear();
                gc_collect_cycles();
//                 $output->writeln('#######################################');
//                 $output->writeln(sprintf(PHP_EOL.'Memory usage : ' . number_format((memory_get_usage() / 1024/1024),3) . ' MO'));
//                 $output->writeln(sprintf("Script duration " . number_format(microtime(true)-$timestart, 3) . " seconds").PHP_EOL);
            }
        }
        $output->writeln('-----------------------------------');
        $output->writeln(sprintf("Script duration " . number_format(microtime(true)-$timestart, 3) . " seconds"));
        $output->writeln(sprintf('Memory usage : ' . number_format((memory_get_usage() / 1024/1024),3) . ' MO'));
    }
}
