<?php

class AddExtendedReportOffset extends Migration
{
    public function up()
    {
        DBManager::get()->exec("
            ALTER TABLE `evasys_global_profiles`
            ADD COLUMN `extended_report_offset` INT(11) NOT NULL DEFAULT '0' AFTER `reports_after_evaluation`
        ");
        DBManager::get()->exec("
            ALTER TABLE `evasys_institute_profiles`
            ADD COLUMN `extended_report_offset` INT(11) NULL AFTER `reports_after_evaluation`
        ");
        SimpleORMap::expireTableScheme();
    }

    public function down()
    {
        DBManager::get()->exec("
            ALTER TABLE `evasys_global_profiles`
            DROP COLUMN `extended_report_offset`
        ");
        DBManager::get()->exec("
            ALTER TABLE `evasys_institute_profiles`
            DROP COLUMN `extended_report_offset`
        ");
        SimpleORMap::expireTableScheme();
    }
}
