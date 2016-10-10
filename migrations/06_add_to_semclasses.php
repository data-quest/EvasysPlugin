<?php
class AddToSemclasses extends Migration
{
	function up() {
		foreach ($GLOBALS['SEM_CLASS'] as $sem_class) {
		    $modules = $sem_class['modules'];
            $modules['EvasysPlugin'] = array(
                'activated' => 1,
                'sticky' => 1
            );
            $sem_class->setModules($modules);
            $sem_class->store();
        }
	}
}