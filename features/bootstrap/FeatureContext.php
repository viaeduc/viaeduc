<?php

use Alex\MailCatcher\Behat\MailCatcherContext;
use Behat\Behat\Context\BehatContext;
use Behat\Behat\Context\Step\When;
use WebDriver\Behat\WebDriverContext;

class FeatureContext extends BehatContext
{
    public function __construct(array $parameters)
    {
        $this->useContext('webdriver', new WebDriverContext());
        $this->useContext('mailcatcher', new MailCatcherContext());
    }

    /**
     * @Given /^I am connected as "([^"]*)" on "([^"]*)"$/
     */
    public function iAmConnectedAsOn($username, $url)
    {
        return array(
            new When('I am on "/logout"'),
            new When('I should see "CONNECTEZ VOUS"'),
            new When('I fill "id=_username" with "'.$username.'"'),
            new When('I fill "id=_password" with "'.$username.'"'),
            new When('I click on "Connexion"'),
            new When('I am on "'.$url.'"'),
        );
    }

    /**
     * @Given /^group "([^"]*)" has no post$/
     */
    public function groupHasNoPost($name)
    {
        $this->run(function ($container) use ($name) {
            $container->get('pum.context')->setProjectName('rpe');
            $oem = $container->get('pum.context')->getProjectOEM();

            $posts = $oem
                ->getRepository('post')
                ->createQueryBuilder('p')
                ->leftJoin('p.publishedGroup', 'g')
                ->where('g.name = :name')
                ->setParameter('name', $name)
                ->getQuery()
                ->execute()
            ;

            foreach ($posts as $post) {
                $oem->remove($post);
            }

            $oem->flush();
        });
    }

    private function run(\Closure $closure)
    {
        require_once __DIR__.'/../../app/AppKernel.php';
        $kernel = new AppKernel('dev', true);
        $kernel->boot();

        try {
            $res = $closure($kernel->getContainer());
        } catch (\Exception $e) {
            $kernel->shutdown();
        }

        if (isset($e) && $e) {
            throw $e;
        }

        return $res;
    }
}
