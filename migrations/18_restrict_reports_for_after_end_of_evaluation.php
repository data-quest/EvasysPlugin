<?php

class RestrictReportsForAfterEndOfEvaluation extends Migration
{
    public function up()
    {
        DBManager::get()->exec("
            ALTER TABLE `evasys_global_profiles`
            ADD COLUMN `reports_after_evaluation` VARCHAR(3) DEFAULT 'yes' AFTER `student_infotext`
        ");
        DBManager::get()->exec("
            ALTER TABLE `evasys_institute_profiles`
            ADD COLUMN `reports_after_evaluation` VARCHAR(3) DEFAULT '' AFTER `student_infotext`
        ");
        SimpleORMap::expireTableScheme();
    }

    public function down()
    {
        DBManager::get()->exec("
            ALTER TABLE `evasys_global_profiles`
            DROP COLUMN `reports_after_evaluation`
        ");
        DBManager::get()->exec("
            ALTER TABLE `evasys_institute_profiles`
            DROP COLUMN `reports_after_evaluation`
        ");
        SimpleORMap::expireTableScheme();
    }
}
