<?php
namespace Rpe\PumBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Achieve suggested post command
 * 
 * php app/console rpe:suggested_post:archive
 * 
 * @method void configure()
 * @method void execute(InputInterface $input, OutputInterface $output)
 * 
 */
class ArchiveSuggestedPostCommand extends ContainerAwareCommand
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
            ->setName('rpe:suggested_post:archive')
            ->setDescription('Archive suggested post when published date ends')
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
        $nb = 0;
        $container = $this->getContainer();

        $em    = $container->get('em_factory')->getManager($container->get('pum'), 'rpe');
        $posts = $em->getRepository('rpe_suggested_post')->getPastPosts();

        foreach ($posts as $post) {
            $post->setStatus('archived');
            $em->persist($post);
            $nb++;
        }
        $em->flush();

        $output->writeln(sprintf('%s suggested post(s) were archived.', $nb));
    }
}
