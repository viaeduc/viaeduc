<?php
namespace Rpe\PumBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;

/**
 * Check many to many objects relations and repair missed
 * 
 * php app/console rpe:m2m_relation:check
 * 
 * @method void configure()
 * @method void execute(InputInterface $input, OutputInterface $output)
 */
class CheckM2mRelationsCommand extends ContainerAwareCommand
{

    /**
     * @var     object  $pdo      Pdo object to connect to database current
     */
    protected $pdo          = null;
    
    /**
     * @var $pdo_basic  Pdo object to connect to basic db for comparing
     */
    protected $pdo_basic    = null;

    /**
     * @var string   $query Query string for select or update
     */
    protected $query        = null;

    /**
     * @var object  $output  Output object for command
     */
    protected $output       = null;
    
    
    // array to which contains mapping between the newly created disciplines and old ones 
    protected $new_object_mapping = array();
    protected $parent_object_stack = array();
    
    /**
     * configurations for command
     * 
     * @return  void
     * @see     \Symfony\Component\Console\Command\Command::configure()
     */
    protected function configure()
    {
        $this
            ->setName('rpe:m2m_relation:check')
            ->setDescription('Check many to many relations')
            ->addArgument('db_basic', InputArgument::REQUIRED, 'Database name to compare against')
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
        $this->output = $output;
        $environment = $this->getContainer()->get('kernel')->getEnvironment();
        if ($environment == "dev") {
            $this->getContainer()->get('profiler')->disable();
        }

        $style = new \Symfony\Component\Console\Formatter\OutputFormatterStyle('green', 'black', array('bold'));
        $this->output->getFormatter()->setStyle('updated', $style);
        $style = new \Symfony\Component\Console\Formatter\OutputFormatterStyle('blue', 'black', array('bold'));
        $this->output->getFormatter()->setStyle('inserted', $style);
        $style = new \Symfony\Component\Console\Formatter\OutputFormatterStyle('magenta', 'black', array('bold'));
        $this->output->getFormatter()->setStyle('deleted', $style);
        $style = new \Symfony\Component\Console\Formatter\OutputFormatterStyle('red', 'black', array('bold'));
        $this->output->getFormatter()->setStyle('error', $style);

        $dialog = $this->getHelper('dialog');
        $question = "<updated>Ensure to run this command only one time, type 'Y' to continue, others to abort. Continue:</updated>";
        if (!$dialog->askConfirmation($output, $question, false)) {
            return;
        }
        
        $this->output->writeln('<info>Start check</info>');
        $db                 = $this->getContainer()->getParameter('database_name');
        $db_basic           = $input->getArgument('db_basic');
        $host               = $this->getContainer()->getParameter('database_host');
        $user               = $this->getContainer()->getParameter('database_user');
        $pwd                = $this->getContainer()->getParameter('database_password');
        
        $this->pdo          = new \PDO('mysql:host=' . $host . ';dbname=' . $db, $user, $pwd);
        $this->pdo_basic    = new \PDO('mysql:host=' . $host . ';dbname=' . $db_basic, $user, $pwd);
        $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $this->pdo_basic->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        
        
        // treat the relation object table
        $relation_objects = array('instructed_discipline' ,'teaching_level');
        
        foreach ($relation_objects as $relation_object) {
            
            $relation_obj_table_name        = 'obj__rpe__'.$relation_object;
            $query = 'SELECT * FROM '. $relation_obj_table_name . ' ORDER BY id';
            
            try {
                $statement = $this->pdo_basic->prepare($query);
                $statement->execute();
                while ($row = $statement->fetch(\PDO::FETCH_ASSOC)) {
                    
                    // check if this relation exist in current db
                    if ($this->checkIfExist($this->pdo, $relation_obj_table_name, array('id' => $row['id']))) {
                        
                        // if relation exist in current db, do nothing
                    } else {
                        // create the relation object in current db
                        
                        if ($row['parent_id']) {
                            
                        }
                        
                        $this->createRelationObject($this->pdo,  $relation_obj_table_name, $row);
            
            
                    }
                }
            } catch (\PDOException $e) {
                $this->output->writeln('<error>' . $e->getMessage() . '</error>');
            }
        }
        
//         var_dump($this->parent_object_stack, $this->new_object_mapping);

        // treat the association relation table
        $map_relations = array(
            'group' => array('instructed_discipline', 'teaching_level'),
            'post'  => array('instructed_discipline', 'teaching_level'),
            'user'  => array('instructed_discipline', 'teaching_level'),
            'question'  => array('instructed_discipline')
        );
        
        foreach ($map_relations as $object => $relations) {
            foreach ($relations as $relation_object) {
                
                // take GROUP, USER, POST as target object
                // take Instructed_discipline, Teaching_level as relation object
                $relation_table = $relation_object . 's';
                $relation_table_name            = 'obj__rpe__assoc__'.$object.'__'.$relation_table;
                $relation_obj_table_name        = 'obj__rpe__'.$relation_object;
                $target_obj_table_name          = 'obj__rpe__'.$object;
                $target_obj_id                  = $object . '_id';
                $relation_obj_id                = $relation_object . '_id';
                
                // special name for post
                if ($object === "post" && $relation_object === "instructed_discipline") {
                    $relation_table_name = 'obj__rpe__assoc__post__disciplines';
                }
                
                // start loop for basic database, if a relation not found in current db, create it
                $query = 'SELECT * FROM '. $relation_table_name . ' ORDER BY ' . $target_obj_id;
                
                try {
                    $statement = $this->pdo_basic->prepare($query);
                    $statement->execute();
                    while ($row = $statement->fetch(\PDO::FETCH_ASSOC)) {
                        // check if this relation exist in current db
                        if ($this->checkIfExist($this->pdo, $relation_table_name, $row)) {
                            // if relation exist in current db, do nothing
                        } else {
                            
                            // check if the group existe in current db, only correct existed groups/post)
                            if ($this->checkIfExist($this->pdo, $target_obj_table_name, array('id' => $row[$target_obj_id]))) {
                                
                                if ($new_relation_id = $this->treateMapping($relation_obj_table_name, $row[$relation_obj_id])) {
                                    
                                    $this->insertRow($this->pdo, $relation_table_name, array(
                                        $target_obj_id => $row[$target_obj_id],
                                        $relation_obj_id => $new_relation_id
                                    ));
                                }
                            }
                        }
                    }
                } catch (\PDOException $e) {
                    $this->output->writeln('<error>' . $e->getMessage() . '</error>');
                }
                
//                 var_dump($this->checkIfExist($this->pdo, 'obj__rpe__instructed_discipline', array('id' => 135)));
                
//                 var_dump($this->insertRow($this->pdo, 'obj__rpe__instructed_discipline', array('name' => $this->pdo->quote("Agir et s'exprimer avec son corps"), 'sequence' => 1, 'parent_id' => 95)));
                
                
            }
            
        }

//         $this->output->writeln('<deleted>COUNT : '.$count.' DELETED</deleted>');
//         $this->output->writeln('<info>End CleanUp</info>');
    }
    
    private function getRow($pdo, $table_name, $params)
    {
        $count = 0;
        $condition = "";
        foreach ($params as $k => $v) {
            $v = is_int($v) ? $v : $pdo->quote($v);
            if ($count == 0) {
                $condition .= " WHERE $k = " . $v;
            } else {
                $condition .= " AND $k = " . $v;
            }
            $count++;
        }
        $query = 'SELECT * FROM ' . $table_name . $condition;
        
        try {
            $statement = $pdo->prepare($query);
        
            $statement->execute();
            $result = $statement->fetch(\PDO::FETCH_ASSOC);
        
        } catch (\PDOException $e) {
            $this->output->writeln('<error>Select error:' . $e->getMessage() .', # ' . $query . ' #</error>');
            return false;
        }
        return $result;
    }
    
    private function checkIfExist($pdo, $table_name, $params)
    {
        $count = 0;
        $condition = "";
        foreach ($params as $k => $v) {
            $v = is_int($v) ? $v : $pdo->quote($v);
            if ($count == 0) {
                $condition .= " WHERE $k = " . $v;
            } else {
                $condition .= " AND $k = " . $v;
            }
            $count++;
        }
        $query = 'SELECT COUNT(*) FROM ' . $table_name . $condition;
        
        try {
            $statement = $pdo->prepare($query);
            
            $statement->execute();
            $result = $statement->fetch(\PDO::FETCH_NUM);
            
        } catch (\PDOException $e) {
            $this->output->writeln('<error>Check error:' . $e->getMessage() . ', # ' . $query . ' #</error>');
            return false;
        }
        return $result[0];
    }
    
    private function insertRow($pdo, $table, $params)
    {
        foreach ($params as $k => $v) {
            if (empty($v)) {
                unset($params[$k]);
            }
        }
        
        $field = "";
        $data = "";
        $count = 1;
        foreach ($params as $key => $value) {
            
            if ($count == 1) {
                $field .= ' (';
                $data .= ' VALUES (';
            }
            $field .= $key;
            $data  .=  $pdo->quote($value);  
            if ($count < count($params)) {
                $field .= ',';
                $data .=  ',';
            }
            
            if ($count === (count($params))){   
                $field .= ')';
                $data .= ')';
            }
            $count++;
        }
        $query = 'INSERT INTO ' . $table . ' ' . $field . $data;
        
        
        try {
            $statement = $pdo->prepare($query);
            $statement->execute();
            
            $lastId = $pdo->lastInsertId();
            $this->output->writeln('<inserted>Inserted in ' . $table . ": $lastId</inserted>");
            return $lastId;
            
        } catch (\PDOException $e) {
            
            $this->output->writeln('<error>Inserted error:' . $e->getMessage() . ', # ' . $query . ' #</error>');
            return false;
        }
    }
    
    private function treateMapping($object, $old, $new = null)
    {
        // search the temp array, if a new relation object (ex: teaching_level) is already created, 
        // return the created relation object id, else save it for after use
        
        if (!isset($this->new_object_mapping[$object])) {
            $this->new_object_mapping[$object] = array();
        }
        
        if (array_key_exists($old, $this->new_object_mapping[$object])) {
            return $this->new_object_mapping[$object][$old];
        } else {
            if (isset($new)) {
                $this->new_object_mapping[$object][$old] = $new;
            } else {
                return false;
            }
        }
    }
    
    /**
     * recusive method to create relation object (if it has parent)
     * 
     * @param unknown $pdo
     * @param unknown $table
     * @param unknown $params
     */
    private function createRelationObject($pdo, $table, $params)
    {
        $old_id = $params['id'];
        
        $parent_id = $params['parent_id'];
        
        if (empty($parent_id)) {
            unset($params['id']);
            $last_insert = $this->insertRow($pdo, $table, $params);
            $this->treateMapping($table, $old_id, $last_insert);
        } else {
            // to be treated after all the parent entities has been created
            if ($this->checkIfExist($pdo, $table, array('id' => $parent_id))) {
                $last_insert = $this->insertRow($pdo, $table, $params);
                $this->treateMapping($table, $old_id, $last_insert);
                
            } elseif ($new_parent_id = $this->treateMapping($table, $parent_id)) {
                $params['parent_id'] = $new_parent_id;
                $last_insert = $this->insertRow($pdo, $table, $params);
                $this->treateMapping($table, $old_id, $last_insert);
            } else {
                $this->parent_object_stack[$table][] = $params; 
            }
        }
    }
}
