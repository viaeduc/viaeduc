<?php
namespace Rpe\PumBundle\Command;

use Pum\Bundle\CoreBundle\Console\OutputLogger;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command to import email domain list for academy
 * 
 * php app/console pum:media:cleanup
 * 
 * @method void configure()
 * @method void execute(InputInterface $input, OutputInterface $output)
 * @method boolean createDomain($domain)
 */
class ImportEmailsFromAcademiesCommand extends ContainerAwareCommand
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
            ->setName('rpe:emails:import')
            ->setDescription('Import emails domain restriction from academies')
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

        $em        = $container->get('em_factory')->getManager($container->get('pum'), 'rpe');
        $academies = $em->getRepository('academy')->findAll();

        foreach ($academies as $academy) {
            $email_domain = $academy->getAllowedEmail();
            if (null === $em->getRepository('email_domain')->findOneByDomain($email_domain)) {
                $em->persist($this->createDomain($email_domain));
                $nb++;
            }
        }
        $em->flush();

        $output->writeln(sprintf('Import success for email from academies : %s', $nb));
    }

    /**
     * Check if domain exist, if not, create it
     * 
     * @param string $domain Domain to check for adding
     * @return  boolean   true if success, false if failed
     * @access  protected
     */
    private function createDomain($domain)
    {
        return $this->getContainer()->get('pum.context')->setProjectName('rpe')->getProjectOEM()->createObject('email_domain')->setDomain($domain);
    }
}
