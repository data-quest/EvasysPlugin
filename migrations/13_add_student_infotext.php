<?php

class AddStudentInfotext extends Migration
{
    public function up()
    {
        DBManager::get()->exec("
            ALTER TABLE `evasys_global_profiles`
            ADD COLUMN `student_infotext` TEXT NULL AFTER `user_id`
        ");
        DBManager::get()->exec("
            ALTER TABLE `evasys_institute_profiles`
            ADD COLUMN `student_infotext` TEXT NULL AFTER `user_id`
        ");
        SimpleORMap::expireTableScheme();
    }

    public function down()
    {
        DBManager::get()->exec("
            ALTER TABLE `evasys_global_profiles`
            DROP COLUMN `student_infotext`
        ");
        DBManager::get()->exec("
            ALTER TABLE `evasys_institute_profiles`
            DROP COLUMN `student_infotext`
        ");
        SimpleORMap::expireTableScheme();
    }
}
