<?php

namespace Rpe\PumBundle\DataFixtures\ORM\Object;

use Rpe\PumBundle\DataFixtures\ORM\Fixture;
use Doctrine\Common\Persistence\ObjectManager;

class LoadVarsData extends Fixture
{
    public function getOrder()
    {
        return 3; // depends on project
    }

    public function load(ObjectManager $ma)
    {
        $vars = $this->get('pum.context')->setProjectName('rpe')->getProjectVars();

        $aVar = array('key' => null, 'value' => null, 'type' => 'string', 'description' => null);

        foreach ($this->getVars() as $var) {
            $vars->save(array_merge($aVar, $var));
        }
    }

    public function getVars()
    {
        return array(
            //user type
            array('key' => 'user_type_common',          'value' => 'COMMON'),
            array('key' => 'user_type_privilege',       'value' => 'PRIVILEGE'),
            array('key' => 'user_type_admin',           'value' => 'ADMIN'),
            array('key' => 'dashboard_active',          'value' => 1, 'type' => 'boolean'),
            array('key' => 'add_etherpad_group-module', 'value' => 0, 'type' => 'boolean'),
            array('key' => 'suggested_posts',           'value' => 1, 'type' => 'boolean'),
            array('key' => 'calendar_active',           'value' => 1, 'type' => 'boolean'),
            array('key' => 'active_global_search',      'value' => 1, 'type' => 'boolean'),
            array('key' => 'active_belin',              'value' => 1, 'type' => 'boolean'),
            array('key' => 'active_canope',             'value' => 1, 'type' => 'boolean'),
        );
    }
}
