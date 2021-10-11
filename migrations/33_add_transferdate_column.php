<?php

class AddTransferdateColumn extends Migration
{
    public function up()
    {
        DBManager::get()->exec("
            ALTER TABLE `evasys_course_profiles`
            ADD COLUMN `transferdate` int(11) DEFAULT '0' AFTER `transferred`
        ");
        DBManager::get()->exec("
            UPDATE `evasys_course_profiles`
            SET `transferdate` = `chdate`
        ");
    }

    public function down()
    {
        DBManager::get()->exec("
            ALTER TABLE `evasys_course_profiles`
            DROP COLUMN `transferdate`
        ");
        Config::get()->delete("EVASYS_LOCK_AFTER_TRANSFER_FOR_ROLE");
    }
}
