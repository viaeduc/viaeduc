<?php
namespace Rpe\PumBundle\Command;

use Pum\Bundle\TypeExtraBundle\Model\Media;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

/**
 * Set default media for objects
 * php app/console rpe:media:default
 * 
 * @method void configure()
 * @method void execute(InputInterface $input, OutputInterface $output)
 */
class SetDefaultMediaCommand extends ContainerAwareCommand
{
    
    /**
     * @var const MAX_ITEMS  Maximum items 
     */
    const MAX_ITEMS = 100;

    /**
     * configurations for command
     *
     * @return  void
     * @see     \Symfony\Component\Console\Command\Command::configure()
     */
    protected function configure()
    {
        $this
            ->setName('rpe:media:default')
            ->setDescription('Set default media')
            ->addArgument('type', InputArgument::OPTIONAL, 'type', null)
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
        $environment = $this->getContainer()->get('kernel')->getEnvironment();
        if ($environment == "dev") {
            $this->getContainer()->get('profiler')->disable();
        }

        $output->writeln('<info>Start Import</info>');

        $style = new \Symfony\Component\Console\Formatter\OutputFormatterStyle('green', 'black', array('bold'));
        $output->getFormatter()->setStyle('updated', $style);
        $style = new \Symfony\Component\Console\Formatter\OutputFormatterStyle('blue', 'black', array('bold'));
        $output->getFormatter()->setStyle('inserted', $style);
        $style = new \Symfony\Component\Console\Formatter\OutputFormatterStyle('magenta', 'black', array('bold'));
        $output->getFormatter()->setStyle('deleted', $style);
        $style = new \Symfony\Component\Console\Formatter\OutputFormatterStyle('red', 'black', array('bold'));
        $output->getFormatter()->setStyle('error', $style);

        $container = $this->getContainer();
        $storage   = $container->get('type_extra.media.storage.driver');
        $em        = $container->get('em_factory')->getManager($container->get('pum'), 'rpe');
        $tool      = $container->get('tool.avatar');

        $em->getConnection()->getConfiguration()->setSQLLogger(null);

        $reflection = new \ReflectionClass('Rpe\PumBundle\RpePumBundle');
        $imagePath  = dirname($reflection->getFilename()).'/Resources/fixtures';

        if (!is_dir($imagePath)) {
            throw new \RuntimeException('No image path!?');
        }

        $type  = $input->getArgument('type');
        $types = array('user', 'group', 'post');
        if (null !== $type) {
            $types = array($type);
        }

        foreach ($types as $type) {
            $output->writeln('<info>Set default media for : '.$type.'</info>');
            $repo = $em->getRepository($type);

            switch ($type) {
                case 'user':

                    $criterias = array(
                        'cover'  => array('cover_id' => null),
                        'avatar' =>array('avatar_id' => null),
                    );

                    foreach ($criterias as $key => $criteria) {
                        $output->writeln('<info>Updating media for '.$type.'::'.$key.'</info>');

                        $counter   = 0;
                        $count     = $repo->countBy($criteria);
                        $iteration = ceil($count/self::MAX_ITEMS);

                        for ($i = 0; $i < $iteration; $i++) {
                            foreach ($repo->getObjectsBy($criteria, 'id', $limit=self::MAX_ITEMS, $offset=0) as $user) {

                                $output->writeln('<info>Currently set media for '.$type.'::'.$user->getId().' '.$key.'</info>');

                                $counter++;
                                $colors = $tool->getPaletteColorFromText($user->getFirstname() . ' ' . $user->getLastname(), false, array(5,6));

                                switch ($key) {
                                    case 'avatar':
                                        $user->setAvatar($tool->getAutoAvatar($user->getFirstname() . ' ' . $user->getLastname(), 300, 300, $colors));
                                    break;

                                    case 'cover':
                                        $user->setCover($tool->getMaskedImage('user', $colors, 837, 400, false));
                                    break;
                                }
                            }

                            $em->flush();
                            $em->clear();
                            gc_collect_cycles();
                            $output->writeln('<info>Memory usage : ' . (memory_get_usage() / 1024) . ' KB</info>');
                        }

                        $output->writeln('<info>Count : '.$counter.' users '.$key.' updated </info>');
                    }

                    $em->flush();
                    $em->clear();
                    gc_collect_cycles();

                break;

                case 'group':
                    $criterias = array(
                        'cover'  => array('cover_id' => null),
                        'picture' =>array('picture_id' => null),
                    );

                    foreach ($criterias as $key => $criteria) {
                        $output->writeln('<info>Updating media for '.$type.'::'.$key.'</info>');

                        $counter   = 0;
                        $count     = $repo->countBy($criteria);
                        $iteration = ceil($count/self::MAX_ITEMS);

                        for ($i = 0; $i < $iteration; $i++) {
                            foreach ($repo->getObjectsBy($criteria, 'id', $limit=self::MAX_ITEMS, $offset=0) as $group) {

                                $output->writeln('<info>Currently set media for '.$type.'::'.$group->getId().' '.$key.'</info>');

                                $counter++;
                                $colors = $tool->getPaletteColorFromText($group->getName(), false, array(5,6));

                                switch ($key) {
                                    case 'picture':
                                        $group->setPicture($tool->getMaskedImage('users', $colors));
                                    break;

                                    case 'cover':
                                        $group->setCover($tool->getMaskedImage('users', $colors, 837, 400, false));
                                    break;
                                }
                            }

                            $em->flush();
                            $em->clear();
                            gc_collect_cycles();
                            $output->writeln('<info>Memory usage : ' . (memory_get_usage() / 1024) . ' KB</info>');
                        }

                        $output->writeln('<info>Count : '.$counter.' users '.$key.' updated </info>');
                    }

                    $em->flush();
                    $em->clear();
                    gc_collect_cycles();

                break;

                case 'post':
                    $criterias = array(
                        'file'  => array('file_id' => null),
                    );

                    foreach ($criterias as $key => $criteria) {
                        $output->writeln('<info>Updating media for '.$type.'::'.$key.'</info>');

                        $counter   = 0;
                        $count     = $repo->countBy($criteria);
                        $iteration = ceil($count/self::MAX_ITEMS);

                        for ($i = 0; $i < $iteration; $i++) {
                            foreach ($repo->getObjectsBy($criteria, 'id', $limit=self::MAX_ITEMS, $offset=0) as $post) {

                                $output->writeln('<info>Currently set media for '.$type.'::'.$post->getId().' '.$key.'</info>');

                                $counter++;
                                $color = $tool->getPaletteColorFromText($post->getName(), false);

                                $post->setFile($tool->getMaskedImage('books', $color));
                            }

                            $em->flush();
                            $em->clear();
                            gc_collect_cycles();
                            $output->writeln('<info>Memory usage : ' . (memory_get_usage() / 1024) . ' KB</info>');
                        }

                        $output->writeln('<info>Count : '.$counter.' users '.$key.' updated </info>');
                    }

                    $em->flush();
                    $em->clear();
                    gc_collect_cycles();

                break;
            }
        }

    }

}
