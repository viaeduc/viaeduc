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
 * Delete the notifications before a number of months
 * 
 *  app/console rpe:notification:clear 4
 *  app/console rpe:notification:clear 4 --dry-run
 * 
 * @method void configure()
 * @method void execute(InputInterface $input, OutputInterface $output)
 * 
 */
class ClearNotificationCommand extends ContainerAwareCommand
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
            ->setName('rpe:notification:clear')
            ->setDescription('Clear notifications')
            ->addArgument('period', InputArgument::REQUIRED, 'Nombre de mois')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Simuler suppression')
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

        $period = (int) $input->getArgument('period');
        $dry_run = $input->getOption('dry-run');        
        if ($period < 3 ) {
            $output->writeln('<info>Le nombre de mois passés devrait être égale où supérieur à 3 mois.</info>');
            die;
        }
        $date = date('Y-m-d H:i:s', strtotime("-$period months"));
        
        $container              = $this->getContainer();
        $container->get('pum.context')->setProjectName('rpe');
        $em                     = $container->get('em_factory')->getManager($container->get('pum'), 'rpe');
        $context                = $container->get('router')->getContext();

        
        $repo_notif = $em->getRepository('notification');
        $countNotifsBefore   = $repo_notif->getNotificationsBefore($date, null, null, true);
        
        
        
        gc_collect_cycles();
        $nbItemsByCycle     = 300;
        $iteration          = ceil($countNotifsBefore/$nbItemsByCycle);
        $output->writeln('<info>Notifs to be deleted : '. $countNotifsBefore .'</info>');
        if ($dry_run === true) {
            $first_notif = $repo_notif->getNotificationsBefore($date, 1, 0);
            if (count($first_notif) == 1) {
                $output->writeln('<info>Notif le plus récente à supprimer : '. $first_notif[0]->getId() . ' , crée le ' . $first_notif[0]->getDate()->format('Y-m-d H:i:s') . '</info>');
            }
            
            if ($countNotifsBefore - 1 >= 1) {
                $last_notif =  $repo_notif->getNotificationsBefore($date, 1, $countNotifsBefore - 1);
                if (count($last_notif) == 1) {
                    $output->writeln('<info>Notif le plus ancienne à supprimer : '. $last_notif[0]->getId() . ' , crée le ' . $last_notif[0]->getDate()->format('Y-m-d H:i:s') . '</info>');
                }
            }
            die;
        }
        
        if ($iteration > 0) {
            for ($i = 0; $i < $iteration; $i++) {
                $limit  = $nbItemsByCycle;
                $offset = $i*$nbItemsByCycle;
                
                foreach ($repo_notif->getNotificationsBefore($date, $limit, $offset) as $notif) {
                    $em->remove($notif);
                    $output->writeln('Notification deleted: <' . $notif->getId() . '>' . ' , created: ' . $notif->getDate()->format('Y-m-d H:i:s'));
                    $em->flush();
                }
                $em->clear();
                gc_collect_cycles();
            }
        }
        $output->writeln('-----------------------------------');
        $output->writeln(sprintf("Script duration " . number_format(microtime(true)-$timestart, 3) . " seconds"));
        $output->writeln(sprintf('Memory usage : ' . number_format((memory_get_usage() / 1024/1024),3) . ' MO'));
    }
}
