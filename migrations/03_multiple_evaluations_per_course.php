<?php
class MultipleEvaluationsPerCourse extends Migration
{
    function up(){
        DBManager::get()->exec("
            ALTER TABLE evasys_seminar DROP PRIMARY KEY;
        ");
        DBManager::get()->exec("
            ALTER TABLE `evasys_seminar` ADD PRIMARY KEY ( `Seminar_id` , `evasys_id` ) 
        ");
    }
}