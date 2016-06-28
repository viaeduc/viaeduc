<?php
namespace Rpe\PumBundle\Command;

use Pum\Bundle\CoreBundle\Console\OutputLogger;
use Pum\Bundle\TypeExtraBundle\Model\Price;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Routing\Exception\InvalidParameterException;

/**
 * Command for import external resources and index them in elastic search
 *
 * php app/console rpe:extressources:batch
 *
 * @method void configure()
 * @method void execute(InputInterface $input, OutputInterface $output)
 * @method void addNotice($output, $notice, $source)
 * @method void updateNotice($output, $newNotice, $source)
 *
 */
class ExtRessourcesCommand extends ContainerAwareCommand
{
    /**
     * @var array $levels Array to save all teaching levels for associate with external resource
     * @access protected
     */
    protected $levels = array(
        'Maternelle'  => array('école maternelle'),
        'Élémentaire' => array(
            'école élémentaire',
            'école primaire'
        ),
        'cycle 2'     => array('école élémentaire'),
        'cycle 3'     => array('école élémentaire'),
        'Secondaire' => array(
            'collège professionnel',
            'collège',
            'lycée général et technologique',
            'lycée professionnel',
            'adaptation scolaire et scolarisation des élèves handicapés',
            'enseignement supérieur en lycée',
            'formation professionnelle'
        ),
        'enseignement secondaire' => array(
            'collège professionnel',
            'collège',
            'lycée général et technologique',
            'lycée professionnel',
            'adaptation scolaire et scolarisation des élèves handicapés',
            'enseignement supérieur en lycée',
            'formation professionnelle'
        ),
        'enseignement secondaire - voie générale et technologique' => array(
            'collège professionnel',
            'collège',
            'lycée général et technologique',
            'lycée professionnel',
            'adaptation scolaire et scolarisation des élèves handicapés',
            'enseignement supérieur en lycée',
            'formation professionnelle'
        ),
        'enseignement secondaire - voie générale' => array(
            'collège professionnel',
            'collège',
            'lycée général et technologique',
            'lycée professionnel',
            'adaptation scolaire et scolarisation des élèves handicapés',
            'enseignement supérieur en lycée',
            'formation professionnelle'
        ),
        'enseignement secondaire - voie technologique' => array(
            'collège professionnel',
            'collège',
            'lycée général et technologique',
            'lycée professionnel',
            'adaptation scolaire et scolarisation des élèves handicapés',
            'enseignement supérieur en lycée',
            'formation professionnelle'
        ),
        'lycée général'       => array('lycée général et technologique'),
        'lycée technologique' => array('lycée général et technologique'),
        '1re ES'                    => array('1re générale et technologique'),
        '1re L'                     => array('1re générale et technologique'),
        '1re S'                     => array('1re générale et technologique'),
        '1re technologique'         => array('1re générale et technologique'),
        '1re générale'              => array('1re générale et technologique'),
        '1re techno ST2S'           => array('1re générale et technologique'),
        '1re techno STAV'           => array('1re générale et technologique'),
        '1re techno ST2A'           => array('1re générale et technologique'),
        '1re techno ST2D'           => array('1re générale et technologique'),
        '1re techno STI2D'          => array('1re générale et technologique'),
        '1re techno STL'            => array('1re générale et technologique'),
        '1re techno STMG'           => array('1re générale et technologique'),
        '1re techno TMD'            => array('1re générale et technologique'),
        '1re techno hôtellerie'     => array('1re générale et technologique'),

        '2de générale'              => array('2de générale et technologique'),

        'terminale générale'       => array('terminale générale et technologique'),
        'terminale ES'             => array('terminale générale et technologique'),
        'terminale L'              => array('terminale générale et technologique'),
        'terminale S'              => array('terminale générale et technologique'),
        'terminale technologique'           => array('terminale générale et technologique'),
        'TSG terminale techno ST2S'         => array('terminale générale et technologique'),
        'TSG terminale techno STAV'         => array('terminale générale et technologique'),
        'TSG terminale techno STD2A'        => array('terminale générale et technologique'),
        'TSG terminale techno STI2D'        => array('terminale générale et technologique'),
        'TSG terminale techno STL'          => array('terminale générale et technologique'),
        'TSG terminale techno STMG'         => array(
            'terminale générale et technologique',
            'terminale techno STMG'
        ),
        'TSG terminale techno TMD'          => array('terminale générale et technologique'),
        'TSG terminale techno hôtellerie'   => array('terminale générale et technologique'),

        'enseignement secondaire - voie professionnelle' => array('lycée professionnel'),

        'enseignement supérieur' => array('enseignement supérieur en lycée'),

        'formation professionnelle des adultes'             => array('formation professionnelle'),
        "formation des personnels de l'éducation nationale" => array('formation professionnelle'),
        'préparation aux concours de la fonction publique'  => array('formation professionnelle')
    );

    /**
     * @var array $disciplines  Array to save all disciplines
     * @access  protected
     */
    protected $disciplines = array(
        'Français, Lettres, Langues et cultures de l’antiquité' => array(
            'Français, Lettres',
            'Langues et cultures de l’antiquité'
        ),
        'Sciences et techniques industrielles' => array('Enseignement technologique')
    );

    /**
     * @var array $existDiscipline  Array to save existed disciplines
     * @access  protected
     */
    private $existDiscipline = array();

    /**
     * @var array $logsOutput Store logs to show at the end of the script
     * @access private
     */
    private $logsOutput = array();

    /**
     * configurations for command
     *
     * @return  void
     * @see     \Symfony\Component\Console\Command\Command::configure()
     */
    protected function configure()
    {
        $this
            ->setName('rpe:extressources:batch')
            ->setDescription('Get notices')
            ->addArgument('mode', InputArgument::REQUIRED, 'Mode "daily", "weekly" or "all"')
            ->addOption('source', null, InputOption::VALUE_REQUIRED, 'Source of the notices')
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
        $environment = $this->getContainer()->get('kernel')->getEnvironment();
        if ($environment == "dev") {
            $this->getContainer()->get('profiler')->disable();
        }

        gc_enable();
        $mode      = $input->getArgument('mode');
        $source    = $input->getOption('source');
        $container = $this->getContainer();
        $em        = $container->get('em_factory')->getManager($container->get('pum'), 'rpe');

        $output->writeln('<info>Start import</info>');
        $em->getConnection()->getConfiguration()->setSQLLogger(null);
        gc_collect_cycles();

        /* * * * * * * * * * * * /
         *       NOTICIA         /
         * * * * * * * * * * * **/
        if ($source == 'noticia') {
            $Noticia                  = $container->get('rpe.noticia');
            $newNoticeNoticiaCount    = 0;
            $updateNoticeNoticiaCount = 0;
            $deleteNoticeNoticiaCount = 0;
            $needFlush                = 0;
            $recoveryToken            = 0;

            $output->writeln('<info>Source Noticia</info>');

            // -----
            // Daily
            // -----
            if ($mode == 'daily') {
                /*
                 * Get daily notices and add if not exist
                 */
                $startDate = new \Datetime('yesterday midnight');

                do {
                    $notices = $Noticia->getNotices($recoveryToken, $startDate->format('d-m-Y 00:00:00'), null);
                    if ($recoveryToken == 0) {
                        $output->writeln('Get ' . $notices['nbTotalNotices'] . ' notices');

                        $noticeRepo = $em->getRepository('external_notice');

                        $progress = new ProgressBar($output, $notices['nbTotalNotices']);
                        $progress->setFormat('[%bar%] %percent:3s%% | time: %elapsed:6s% | memory: %memory:6s%');
                        $progress->start();
                    }

                    foreach ((array)$notices['donnees'] as $notice) {
                        // Add notice if not exist
                        $oldNotice = $noticeRepo->findOneBy(array('source' => "noticia", 'idNoticia' => $notice['identifiant'])); 
                        if ($oldNotice === null) {
                            $this->addNotice($output, $notice, $source);
                            $newNoticeNoticiaCount++;
                        } elseif ($notice['dateModification'] != $oldNotice->getUpdateDate()->format('d/m/Y')) {
                            $this->updateNotice($output, $notice, $source);
                            $updateNoticeNoticiaCount++;
                        }
                        
                        $needFlush++;
                        if ($needFlush == 50) {
                            $em->flush();
                            $needFlush = 0;
                        }
                        if ($progress->getProgressPercent() < 1) {
                            $progress->advance();
                        }
                    }
                    $em->flush();
                    $em->clear();
                    $recoveryToken = $notices['jetonDeReprise'];
                } while ($recoveryToken > 0);


            // -----
            // Weekly
            // -----
            } elseif ($mode == 'weekly') {
                /*
                 * Get all notices and add if not exist
                 * Update old notices
                 * Delete notices
                 */
                
                $noticeRepo = $em->getRepository('external_notice');
                $countOldNotice = $noticeRepo->getNoticesBySource('noticia', null, null, 'count');
                
                // loop through all items of webservice
                do {
                    $notices    = $Noticia->getNotices($recoveryToken, null, null);
                    if ($recoveryToken == 0) {
                        $output->writeln('Get ' . $notices['nbTotalNotices'] . ' notices');

                        $progress = new ProgressBar($output, $notices['nbTotalNotices'] + $countOldNotice);
                        $progress->setFormat('[%bar%] %percent:3s%% | time: %elapsed:6s% | memory: %memory:6s%');
                        $progress->start();
                        $progress->setRedrawFrequency(50);
                    }
                    
                    $idNoticiaArray = array();
                    foreach ((array)$notices['donnees'] as $notice) {
                        $idNoticiaArray[] = $notice['identifiant'];
                        $oldNotice = $noticeRepo->findNoticesBySourceIdentifier('noticia', $notice['identifiant']);

                        if ($oldNotice === null) {
                            $this->addNotice($output, $notice, $source);
                            $newNoticeNoticiaCount++;
                        } elseif ($notice['dateModification'] != $oldNotice['updateDate']->format('d/m/Y')) {
                            $this->updateNotice($output, $notice, $source);
                            $updateNoticeNoticiaCount++;
                        }
                        if ($progress->getProgressPercent() < 1) {
                            $progress->advance();
                        }
                        $needFlush++;
                        if ($needFlush == 50) {
                            $em->flush();
                            $em->clear();
                            gc_collect_cycles();
                            $needFlush = 0;
                        }
                    }
                    $em->flush();
                    $em->clear();
                    gc_collect_cycles();
                    $recoveryToken = $notices['jetonDeReprise'];
                } while ($recoveryToken > 0);

               /** delete existing notice if it exist no longer in webservice
                *  loop from all notices externe repository 
                */
                $nbByIteration = 200;
                $iteration = ceil($countOldNotice/$nbByIteration);
                
                if ($iteration > 0) {
                    for ($i = 0; $i < $iteration; $i++) {
                        $limit  = $nbByIteration;
                        $offset = $i*$nbByIteration - $deleteNoticeNoticiaCount;
                        
                        $oldNotices = $noticeRepo->getNoticesBySource('noticia', $limit, $offset);
                        // flush and clear em every 100 notice
                        foreach ($oldNotices as $oldNotice) {
                            if (!in_array($oldNotice['idNoticia'], $idNoticiaArray)) {
                                if (null !== $oldNotice = $noticeRepo->findNoticesBySourceIdentifier('noticia', $oldNotice['idNoticia'])) {
                                    $em->remove($oldNotice);
                                    $deleteNoticeNoticiaCount++;
                                }
                            }
                            if ($progress->getProgressPercent() < 1) {
                                $progress->advance();
                            }
                        }
                        $em->flush();
                        $em->clear();
                        gc_collect_cycles();
                    }    
                }
                
            // -----
            // All
            // -----
            } elseif ($mode == 'all') {
                /*
                 * Get all notices and add if not exist
                 */
                $notices = $Noticia->getNotices(null, null, null);
                $output->writeln('Get ' . $notices['nbTotalNotices'] . ' notices');

                $progress = new ProgressBar($output, $notices['nbTotalNotices']);
                $progress->setFormat('%current%/%max% [%bar%] %percent:3s%% | time: %elapsed:6s% | memory: %memory:6s%');
                $progress->start();
                $progress->setRedrawFrequency(30);

                $noticeRepo = $em->getRepository('external_notice');
                foreach ((array)$notices['donnees'] as $notice) {
                    // Add notice if not exist
                    if (!$noticeRepo->findOneBy(array('idNoticia' => $notice['identifiant']))) {
                        $this->addNotice($output, $notice, $source);
                        $newNoticeNoticiaCount++;
                    }

                    $needFlush++;
                    if ($needFlush == 50) {
                        $em->flush();
                        $em->clear();
                        gc_collect_cycles();
                        $needFlush = 0;
                    }

                    if ($progress->getProgressPercent() < 1) {
                        $progress->advance();
                    }
                }
                $em->flush();
                $em->clear();
            } else {
                throw new InvalidParameterException("Select correct mode : daily, weekly, all");
            }
            $progress->finish();
            $output->writeln('');
            $output->writeln('<info>'.$newNoticeNoticiaCount.' new Noticia entry</info>');
            $output->writeln('<info>'.$updateNoticeNoticiaCount.' update Noticia entry</info>');
            $output->writeln('<info>'.$deleteNoticeNoticiaCount.' delete Noticia entry</info>');
            foreach ($this->logsOutput as $log) {
                $output->writeln($log);
            }
            $this->logsOutput = array();
            // $output->writeln('<info>Regenerate Index</info>');
            // $command = $this->getApplication()->find('pum:search:regenerateindex');

            // $arguments = array(
            //     'command' => 'pum:search:regenerateindex',
            //     '--env' => 'dev'
            // );

            // $newInput = new ArrayInput($arguments);
            // $returnCode = $command->run($newInput, $output);
        }
        /* * * * * * * * * * * /
         *       BEEBAC        /
         * * * * * * * * * * **/
        elseif ($source == 'beebac') {
            $Beebac                  = $container->get('rpe.beebac');
            $newNoticeBeebacCount    = 0;
            $updateNoticeBeebacCount = 0;
            $deleteNoticeBeebacCount = 0;
            $needFlush               = 0;
            $offset                  = 0;
            $count                   = 0;

            $noticeRepo = $em->getRepository('external_notice');


            $output->writeln('<info>Source Beebac</info>');

            // -----
            // Daily
            // -----
            if ($mode == 'daily') {
                // -----
                // DAILY
                // -----
                $startDate = new \Datetime('-1 day midnight');

                do {
                    $notices = $Beebac->getNotices($offset, $startDate->getTimestamp(), null);
                    if ($offset == 0) {
                        $count = $notices['result']['count'];
                        $output->writeln('Get ' . $notices['result']['count'] . ' notices');

                        $progress = new ProgressBar($output, $notices['result']['count']);
                        $progress->setFormat('[%bar%] %percent:3s%% | time: %elapsed:6s% | memory: %memory:6s%');
                        $progress->start();
                    }

                    if (!empty($notices['result']['ressources'])) {
                        foreach ($notices['result']['ressources'] as $notice) {
                            $oldNotice = $noticeRepo->findOneBy(array('idBeebac' => $notice['id']));
                            if (!$oldNotice) {
                                $this->addNotice($output, $notice, $source);
                                $newNoticeBeebacCount++;
                            } elseif ($notice['date_updated'] != $oldNotice->getUpdateDate()->format('d/m/Y')) {
                                $this->updateNotice($output, $notice, $source);
                                $updateNoticeBeebacCount++;
                            }
                            if ($progress->getProgressPercent() < 1) {
                                $progress->advance();
                            }
                            $needFlush++;
                            if ($needFlush == 50) {
                                $em->flush();
                                $needFlush = 0;
                            }
                        }
                    }

                    $em->flush();
                    $em->clear();

                    $offset = $notices['result']['offset'];
                } while ($offset < $count);

            }
            // -----
            // All
            // -----
            elseif ($mode == 'all') {
                do {
                    $notices = $Beebac->getNotices($offset, 0, null);

                    if ($offset == 0) {
                        $count = $notices['result']['count'];
                        $output->writeln('Get ' . $notices['result']['count'] . ' notices');
                        $countOldNotice = $noticeRepo->getNoticesBySource('beebac', null, null, 'count');

                        $progress = new ProgressBar($output, $notices['result']['count'] + $countOldNotice);
                        $progress->setFormat('[%bar%] %percent:3s%% | time: %elapsed:6s% | memory: %memory:6s%');
                        $progress->start();
                    }

                    foreach ($notices['result']['ressources'] as $notice) {
                        $idBeebacArray[] = $notice['id'];
                        $output->writeln('.....'.$notice['id']);
                        $oldNotice = $noticeRepo->findOneBy(array('idBeebac' => $notice['id']));
                        if (!$oldNotice) {
                            $this->addNotice($output, $notice, $source);
                            $newNoticeBeebacCount++;
                        } elseif ($notice['date_updated'] != $oldNotice->getUpdateDate()->format('d/m/Y')) {
                            $this->updateNotice($output, $notice, $source);
                            $updateNoticeBeebacCount++;
                        }
                        if ($progress->getProgressPercent() < 1) {
                            // $output->writeln($progress->getStep() . ' / ' . $progress->getMaxSteps() . '  |  ' . $progress->getProgressPercent());
                            $progress->advance();
                        }
                        $needFlush++;
                        if ($needFlush == 50) {
                            $em->flush();
                            $needFlush = 0;
                        }
                    }
                    unset($oldNotice);

                    $offset = $notices['result']['offset'];
                } while ($offset < $count);
                $output->writeln('Finished add/update');
                
                $em->flush();
                $em->clear();
                gc_collect_cycles();

                /** delete existing notice if it exist no longer in webservice
                 *  loop from all notices externe repository
                 */
                $output->writeln('Proceed to delete');
                $nbByIteration = 200;
                $iteration = ceil($countOldNotice/$nbByIteration);
                
                if ($iteration > 0) {
                    for ($i = 0; $i < $iteration; $i++) {
                        $limit  = $nbByIteration;
                        $offset = $i*$nbByIteration - $deleteNoticeBeebacCount;
                
                        $oldNotices = $noticeRepo->getNoticesBySource('beebac', $limit, $offset);
                        // flush and clear em every 100 notice
                        foreach ($oldNotices as $oldNotice) {
                            if (!in_array($oldNotice['idBeebac'], $idBeebacArray)) {
                                if (null !== $oldNotice = $noticeRepo->findNoticesBySourceIdentifier('beebac', $oldNotice['idBeebac'])) {
                                    $em->remove($oldNotice);
                                    $deleteNoticeBeebacCount++;
                                }
                            }
                            if ($progress->getProgressPercent() < 1) {
                                $progress->advance();
                            }
                        }
                        $em->flush();
                        $em->clear();
                        gc_collect_cycles();
                    }
                }
                
            } else {
                throw new InvalidParameterException("Select correct mode : daily, all");
            }
            $progress->finish();
            $output->writeln('');
            $output->writeln('<info>'.$newNoticeBeebacCount.' new Beebac entry</info>');
            $output->writeln('<info>'.$updateNoticeBeebacCount.' update Beebac entry</info>');
            $output->writeln('<info>'.$deleteNoticeBeebacCount.' delete Beebac entry</info>');
            foreach ($this->logsOutput as $log) {
                $output->writeln($log);
            }
            $this->logsOutput = array();

            // $output->writeln('<info>Regenerate Index</info>');
            // $command = $this->getApplication()->find('pum:search:regenerateindex');

            // $arguments = array(
            //     'command' => 'pum:search:regenerateindex',
            //     '--env' => 'dev'
            // );

            // $newInput = new ArrayInput($arguments);
            // $returnCode = $command->run($newInput, $output);

        } else {
            throw new InvalidParameterException("Select correct source : beebac, noticia");
        }
    }

    /**
     * Add a notice item to database
     *
     * @param Object $output    Output object for command
     * @param Object $notice    Notice object to add
     * @param string $source    Source of string, noticia|beebac
     * @return void
     * @access protected
     */
    private function addNotice($output, $notice, $source)
    {
        if (isset($notice['id'])) {
            $id_notice = $notice['id'];
        }
        if (isset($notice['identifiant'])) {
            $id_notice = $notice['identifiant'];
        }
        
        $output->writeln('<info>'. $id_notice .'</info>');
        $container = $this->getContainer();
        $em        = $container->get('em_factory')->getManager($container->get('pum'), 'rpe');

        switch ($source) {
            case 'noticia':
                $category  = $em->getRepository('external_notice_category')->findOneBy(array('name' => $notice['categorie']));

                if (!$category) {
                    $category = $em->createObject('external_notice_category')->setName($notice['categorie']);
                    $em->persist($category);
                    $em->flush();
                }

                $newNotice = $em->createObject('external_notice')
                        ->setSource('noticia')
                        ->setIdNoticia($notice['identifiant'])
                        ->setTitle($notice['titre'])
                        ->setSubtitle( isset($notice['sous-titre']) ? $notice['sous-titre'] : null )
                        ->setDescription( isset($notice['description']) ? $notice['description'] : null )
                        ->setCreateDate(\Datetime::createFromFormat("d/m/Y H:i:s", $notice['dateCreation']." 00:00:00"))
                        ->setUpdateDate(\Datetime::createFromFormat("d/m/Y H:i:s", $notice['dateModification']." 00:00:00"))
                        ->setIsPublishable($notice['publiable'])
                        ->setIssn($notice['issn'])
                        ->setCategory($category)
                        ->setUrl( isset($notice['url']) ? $notice['url'] : null)
                        ->setCommercialCatches( isset($notice['accrocheCommerciale']) ? $notice['accrocheCommerciale'] : null)
                ;

                if (isset($notice['visuel'])) {
                    $newNotice->setPicture($notice['visuel']);
                }

                if (isset($notice['niveaux'])) {
                    foreach ((array)$notice['niveaux'] as $niveau) {
                        if (!empty($niveau)) {
                            if (array_key_exists($niveau, $this->levels)) {
                                $niveaux = $this->levels[$niveau];
                                foreach ($niveaux as $value) {
                                    $niveauObject = $em->getRepository('teaching_level')->findOneBy(array('name' => $value));
                                    if ($niveauObject != null) {
                                        $newNotice->addLevel($niveauObject);
                                        $niveauObject->addNotice($newNotice);
                                    } else {
                                        $this->logsOutput[] = '<comment>Le niveaux "'.$value.'" n\'existe pas</comment>';
                                    }
                                }
                            } else {
                                $niveauObject = $em->getRepository('teaching_level')->findOneBy(array('name' => $niveau));
                                if ($niveauObject != null) {
                                    $newNotice->addLevel($niveauObject);
                                    $niveauObject->addNotice($newNotice);
                                } else {
                                    $this->logsOutput[] = '<comment>Le niveaux "'.$niveau.'" n\'existe pas</comment>';
                                }
                            }
                        }
                    }
                }
                
                if (isset($notice['disciplines'])) {
                    foreach ((array)$notice['disciplines'] as $discipline) {
                        $disciplineObject = $em->getRepository('instructed_discipline')->findOneBy(array('name' => $discipline));
                        if ($disciplineObject != null) {
                            $newNotice->addDiscipline($disciplineObject);
                            $disciplineObject->addNotice($newNotice);
                        } else {
                            $this->logsOutput[] = '<comment>[' . $notice['identifiant'] . '] La discipline "'.$discipline.'" n\'existe pas</comment>';
                        }
                    }
                }
                break;
                
            case 'beebac' :
                $category  = $em->getRepository('external_notice_category')->findOneBy(array('name' => $notice['categories']));
                if (!$category) {
                    $category = $em->createObject('external_notice_category')->setName($notice['categories']);
                    $em->persist($category);
                    $em->flush();
                }

                $newNotice = $em->createObject('external_notice')
                                ->setSource('beebac')
                                ->setIdBeebac($notice['id'])
                                ->setTitle($notice['title'])
                                ->setDescription($notice['description'])
                                ->setCreateDate(\Datetime::createFromFormat("d/m/Y H:i:s", $notice['date_created']." 00:00:00"))
                                ->setUpdateDate(\Datetime::createFromFormat("d/m/Y H:i:s", $notice['date_updated']." 00:00:00"))
                                ->setCategory($category)
                                ->setIsPublishable($notice['publiable'])
                                ->setUrl($notice['url'])
                                ->setPicture($notice['thumbnail'])
                                ->setLanguage($notice['language'])
                ;

                foreach ((array)$notice['levels'] as $levelId) {
                    $level = $em->getRepository('teaching_level')->find($levelId);
                    if ($level) {
                        $newNotice->addLevel($level);
                        $level->addNotice($newNotice);
                    } else {
                        $this->logsOutput[] = '<comment>Le niveau id:"'.$levelId.'" n\'existe pas</comment>';
                    }
                }

                foreach ((array)$notice['subjects'] as $disciplineId) {
                    $discipline = $em->getRepository('instructed_discipline')->find($disciplineId);
                    if ($discipline) {
                        $newNotice->addDiscipline($discipline);
                        $discipline->addNotice($newNotice);
                    } else {
                        $this->logsOutput[] = '<comment>La discipline id:"'.$disciplineId.'" n\'existe pas</comment>';
                    }
                }
        }

        $em->persist($newNotice);
    }


    /**
     * Update a notice if it already exist
     *
     * @param Object $output        Output object for command
     * @param Object $newNotice     Notice object to update
     * @param string $source        Source of string, noticia|beebac
     * @return  void
     * @access  protected
     */
    private function updateNotice($output, $newNotice, $source)
    {
        $container = $this->getContainer();
        $em        = $container->get('em_factory')->getManager($container->get('pum'), 'rpe');

        switch ($source) {
            case 'noticia':
                if (null !== $oldNotice = $em->getRepository('external_notice')->findOneBy(array(
                    'idNoticia' => $newNotice['identifiant']
                ))) {
                    // $output->writeln('debug: ' . gettype($oldNotice) . ' [' . $newNotice['identifiant'] . ']');

                    $category  = $em->getRepository('external_notice_category')->findOneBy(array('name' => $newNotice['categorie']));
                    if (!$category) {
                        $category = $em->createObject('external_notice_category')->setName($newNotice['categorie']);
                        $em->persist($category);
                        $em->flush();
                    }
                    $oldNotice->setTitle($newNotice['titre'])
                              ->setSubtitle( isset($newNotice['sous-titre']) ? $newNotice['sous-titre'] : null )
                              ->setDescription( isset($newNotice['description']) ? $newNotice['description'] : null )
                              ->setUpdateDate(\Datetime::createFromFormat("d/m/Y H:i:s", $newNotice['dateModification']." 00:00:00"))
                              ->setIsPublishable($newNotice['publiable'])
                              ->setIssn($newNotice['issn'])
                              ->setCategory($category)
                              ->setUrl( isset($newNotice['url']) ? $newNotice['url'] : null)
                              ->setCommercialCatches( isset($newNotice['accrocheCommerciale']) ? $newNotice['accrocheCommerciale'] : null)
                    ;

                    if (isset($newNotice['visuel'])) {
                        $oldNotice->setPicture($newNotice['visuel']);
                    }

                    if (isset($newNotice['niveaux'])) {
                        foreach ((array)$newNotice['niveaux'] as $niveau) {
                            if (!empty($niveau)) {
                                if (array_key_exists($niveau, $this->levels)) {
                                    $niveaux = $this->levels[$niveau];
                                    foreach ($niveaux as $value) {
                                        $niveauObject = $em->getRepository('teaching_level')->findOneBy(array('name' => $value));
                                        if ($niveauObject !== null) {
                                            $oldNotice->addLevel($niveauObject);
                                            $niveauObject->addNotice($oldNotice);;
                                        }
                                    }
                                } else {
                                    $niveauObject = $em->getRepository('teaching_level')->findOneBy(array('name' => $niveau));
                                    if ($niveauObject != null) {
                                        $oldNotice->addLevel($niveauObject);
                                        $niveauObject->addNotice($oldNotice);
                                    } else {
                                        $this->logsOutput[] = 'comment>Le niveaux "'.$niveau.'" n\'existe pas</comment>';
                                    }
                                }
                            }   
                        }
                    }

                    if (isset($newNotice['disciplines'])) {
                        foreach ((array)$newNotice['disciplines'] as $discipline) {   
                            $disciplineObject = $em->getRepository('instructed_discipline')->findOneBy(array('name' => $discipline));
                            if ($disciplineObject != null) {
                                $oldNotice->addDiscipline($disciplineObject);
                                $disciplineObject->addNotice($oldNotice);
                            } else {
                                $this->logsOutput[] = '<comment>[' . $newNotice['identifiant'] . '] La discipline "'.$discipline.'" n\'existe pas</comment>';
                            }
                        }
                    }
                    
                    $em->persist($oldNotice);
                    $em->flush();
                } else {
                    $this->logsOutput[] = '<error>Notice seems in db but wrong : [id notice: ' . $newNotice['identifiant'] . ']</error>';
                }
                break;
            case 'beebac' :
                $oldNotice = $em->getRepository('external_notice')->findOneBy(array(
                    'idBeebac' => $newNotice['id']
                ));
                $category  = $em->getRepository('external_notice_category')->findOneBy(array('name' => $newNotice['categories']));
                if (!$category) {
                    $category = $em->createObject('external_notice_category')->setName($newNotice['categories']);
                    $em->persist($category);
                    $em->flush();
                }

                $oldNotice->setTitle($newNotice['title'])
                          ->setDescription($newNotice['description'])
                          ->setUpdateDate(\Datetime::createFromFormat("d/m/Y H:i:s", $newNotice['date_updated']." 00:00:00"))
                          ->setIsPublishable($newNotice['publiable'])
                          ->setUrl($newNotice['url'])
                          ->setPicture($newNotice['thumbnail'])
                          ->setCategory($category)
                          ->setLanguage($newNotice['language'])
                ;

                foreach ((array)$newNotice['levels'] as $levelId) {
                    $level = $em->getRepository('teaching_level')->find($levelId);
                    if ($level) {
                        $oldNotice->addLevel($level);
                        $level->addNotice($oldNotice);
                    } else {
                        $this->logsOutput[] = '<comment>[' . $newNotice['id'] . '] La discipline id:"'.$levelId.'" n\'existe pas</comment>';
                    }
                }

                foreach ((array)$newNotice['subjects'] as $disciplineId) {
                    $discipline = $em->getRepository('instructed_discipline')->find($disciplineId);
                    if ($discipline) {
                        $oldNotice->addDiscipline($discipline);
                        $discipline->addNotice($oldNotice);
                    } else {
                        $this->logsOutput[] = '<comment>[' . $newNotice['id'] . '] La discipline id:"'.$disciplineId.'" n\'existe pas</comment>';
                    }
                }

                $em->persist($oldNotice);
                $em->flush();
        }
    }
}