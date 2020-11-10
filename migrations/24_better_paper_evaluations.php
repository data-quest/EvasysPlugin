<?php

class BetterPaperEvaluations extends Migration
{
    public function up()
    {
        DBManager::get()->exec("
            ALTER TABLE `evasys_global_profiles`
            ADD COLUMN `paper_mode` VARCHAR(1) NULL DEFAULT 's' AFTER `reports_after_evaluation`
        ");
        DBManager::get()->exec("
            ALTER TABLE `evasys_institute_profiles`
            ADD COLUMN `paper_mode` TEXT NULL AFTER `reports_after_evaluation`
        ");
        SimpleORMap::expireTableScheme();
    }

    public function down()
    {
        DBManager::get()->exec("
            ALTER TABLE `evasys_global_profiles`
            DROP COLUMN `paper_mode`
        ");
        DBManager::get()->exec("
            ALTER TABLE `evasys_institute_profiles`
            DROP COLUMN `paper_mode`
        ");
        SimpleORMap::expireTableScheme();
    }
}
