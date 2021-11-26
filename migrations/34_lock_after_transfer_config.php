<?php

class LockAfterTransferConfig extends Migration
{
    public function up()
    {
        Config::get()->delete("EVASYS_LOCK_AFTER_TRANSFER_FOR_ROLE");
        DBManager::get()->exec("
            ALTER TABLE `evasys_global_profiles`
            ADD COLUMN `lockaftertransferforrole` enum('admin', 'dozent') DEFAULT NULL AFTER `send_report_delay`
        ");
        DBManager::get()->exec("
            ALTER TABLE `evasys_institute_profiles`
            ADD COLUMN `lockaftertransferforrole` enum('admin', 'dozent') DEFAULT NULL AFTER `send_report_delay`
        ");
    }

    public function down()
    {
        DBManager::get()->exec("
            ALTER TABLE `evasys_global_profiles`
            DROP COLUMN `lockaftertransferforrole`
        ");
        DBManager::get()->exec("
            ALTER TABLE `evasys_institute_profiles`
            DROP COLUMN `lockaftertransferforrole`
        ");
    }
}
