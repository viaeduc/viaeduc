<?php

namespace Rpe\QA\Context;

use Behat\Behat\Context\BehatContext;
use Behat\Gherkin\Node\TableNode;
use Pum\Bundle\AppBundle\Entity\User;
use Pum\Core\Definition\Beam;
use Pum\Core\Definition\FieldDefinition;
use Pum\Core\Definition\ObjectDefinition;
use Pum\Core\Definition\Project;
use Pum\Core\Exception\DefinitionNotFoundException;
use Pum\Core\Extension\EmFactory\EmFactoryExtension;
use Rpe\QA\Initializer\AppAwareInterface;

class ApiContext extends BehatContext implements AppAwareInterface
{
    protected $runCallback;

    public function setRunCallback($runCallback)
    {
        $this->runCallback = $runCallback;
    }

    private function run($callback)
    {
        if (null === $this->runCallback) {
            throw new \RuntimeException('Run callback missing');
        }
        return call_user_func($this->runCallback, $callback);
    }
}
