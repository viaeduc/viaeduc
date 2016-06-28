<?php
namespace Rpe\PumBundle\Command;

use Pum\Bundle\CoreBundle\Console\OutputLogger;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Rpe\PumBundle\Model\Social\Post;


/**
 * Command to update all the post without version
 *
 * php app/console rpe:posts:createversion
 *
 * @method void configure()
 * @method void execute(InputInterface $input, OutputInterface $output)
 * @method Object createVersion($post)
 */
class UpdatePostWithoutVersionCommand extends ContainerAwareCommand
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
            ->setName('rpe:posts:createversion')
            ->setDescription('Create first version for Posts without version')
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
        $posts = $em->getRepository('post')->findAll();

        foreach ($posts as $post) {
            // $email_domain = $academy->getAllowedEmail();
            $nb_version = $post->getVersions()->count();
            if (0 === $nb_version) {
                $em->persist($this->createVersion($post));
                $nb++;
            }
        }
        $em->flush();

        $output->writeln(sprintf('Successfull versions created for posts : %s', $nb));
    }

    /**
     * Create a version for a post
     * 
     * @access  protected
     * @param Post $post    Post to be treated
     * @return Obejct       Return the post version created
     */
    private function createVersion($post)
    {
        $version = $this->getContainer()->get('pum.context')->setProjectName('rpe')->getProjectOEM()->createObject('post_version');
        $version
            ->setAuthor($post->getAuthor())
            ->setStatus($post->getStatus())
            ->setName($post->getName())
            ->setContent($post->getContent())
            ->setCreateDate($post->getCreateDate())
            ->setUpdateDate($post->getUpdateDate())
            ->setPost($post)
        ;

        foreach ($post->getMedias() as $media) {
            $version->addMedia($media);
        }

        return $version;
    }
}
