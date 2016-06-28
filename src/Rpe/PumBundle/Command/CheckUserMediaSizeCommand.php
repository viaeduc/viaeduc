<?php
namespace Rpe\PumBundle\Command;

use Pum\Bundle\CoreBundle\Console\OutputLogger;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\HttpFoundation\Request;
use Rpe\PumBundle\Model\Social\User;
use Rpe\PumBundle\Model\Social\UserBadge;


/** 
 * Check user media size commande
 * 
 * php app/console rpe:media:usersize
 * 
 * @method void configure()
 * @method void execute(InputInterface $input, OutputInterface $output)
 * 
 */
class CheckUserMediaSizeCommand extends ContainerAwareCommand
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
            ->setName('rpe:media:usersize')
            ->setDescription('Set meta for user media space')
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

        $output->writeln('<info>Start calculation</info>');

        $style = new \Symfony\Component\Console\Formatter\OutputFormatterStyle('green', 'black', array('bold'));
        $output->getFormatter()->setStyle('mail_sent', $style);
        $style = new \Symfony\Component\Console\Formatter\OutputFormatterStyle('red', 'black', array('bold'));
        $output->getFormatter()->setStyle('not_found', $style);

        $container  = $this->getContainer();
        $em         = $container->get('em_factory')->getManager($container->get('pum'), 'rpe');
        $storage    = $container->get('type_extra.media.storage.driver');
        $directory  = $this->getContainer()->getParameter('pum_type_extra.media.storage.filesystem.directory');
        
        $repo_user  = $em->getRepository('user');

        $em->getConnection()->getConfiguration()->setSQLLogger(null);
        gc_collect_cycles();

        $countSent      = 0;
        $nbItemsByCycle = 100;
        $count          = $repo_user->countBy();
        $iteration      = ceil($count/$nbItemsByCycle);

        $output->writeln('<info>Users to calculate : '.$count.'</info>');
        if ($iteration > 0) {
            for ($i = 0; $i < $iteration; $i++) {
                $limit  = $nbItemsByCycle;
                $offset = $i*$nbItemsByCycle;

                foreach ($repo_user->getRest($limit, $offset) as $user) {

                    $email  = $user->getEmailPro();
                    $medias = $user->getMedias();
                    $quota = 0;
                    foreach ($medias as $media){
                        $pum_media = $media->getMedia();
                        if($pum_media && $pum_media->exists()){   
                            $media_url = $pum_media->getMediaUrl($storage);
                            $file = $directory . $media_url;
                            if(file_exists($file)){
                                $size = filesize($file);
                                $quota += $size;
                            }
                        }
                    }
                    $this->setUserMeta($em, $user, User::META_MEDIA_DISK_QUOTA, $quota);
                    $output->writeln('<info>User#' . $user->getId() . ' - <' . $email . '> quota set to ' . number_format($quota) . '</info>');
                }

                $em->clear();
                gc_collect_cycles();
                $output->writeln(sprintf(PHP_EOL.'Memory usage : ' . number_format((memory_get_usage() / 1024/1024),3) . ' MO'));
                $output->writeln(sprintf("Script duration " . number_format(microtime(true)-$timestart, 3) . " seconds").PHP_EOL);
            }
        }

        $output->writeln(sprintf("Script duration " . number_format(microtime(true)-$timestart, 3) . " seconds"));
        $output->writeln(sprintf('Memory usage : ' . number_format((memory_get_usage() / 1024/1024),3) . ' MO'));
    }

    /**
     * Set user meta for media quota
     *  
     * @param object  $em        Instance of entity manager
     * @param User    $user      Instance of user to set
     * @param string  $metaKey   Meta key of meta to set
     * @param string  $metaValue Meta value
     * @param string  $metaType  Meta type
     * @return void   
     * @access public 
     */
    public function setUserMeta($em, $user, $metaKey, $metaValue, $metaType = 'default')
    {
        $user_meta = $user->getMeta($metaKey);
        if (null === $user_meta) {
            $user_meta = $em->createObject('user_meta')
                ->setUser($user);
            $user->addUserMeta($user_meta);
        }
    
        $user_meta
            ->setType($metaType)
            ->setMetaKey($metaKey)
            ->setValue($metaValue);
    
        $em->persist($user, $user_meta);
        $em->flush();
    }
}
