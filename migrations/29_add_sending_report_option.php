<?php

class AddSendingReportOption extends Migration
{
    public function up()
    {
        DBManager::get()->exec("
            ALTER TABLE `evasys_global_profiles`
            ADD COLUMN `send_report` enum('yes','no') NOT NULL DEFAULT 'no' AFTER `paper_mode`
        ");
        DBManager::get()->exec("
            ALTER TABLE `evasys_global_profiles`
            ADD COLUMN `send_report_delay` int(11) DEFAULT NULL AFTER `send_report`
        ");

        DBManager::get()->exec("
            ALTER TABLE `evasys_institute_profiles`
            ADD COLUMN `send_report` enum('yes','no') NULL DEFAULT NULL AFTER `paper_mode`
        ");
        DBManager::get()->exec("
            ALTER TABLE `evasys_institute_profiles`
            ADD COLUMN `send_report_delay` int(11) DEFAULT NULL AFTER `send_report`
        ");
        SimpleORMap::expireTableScheme();
    }

    public function down()
    {
    }
}
