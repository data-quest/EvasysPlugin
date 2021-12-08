<?php

class ObjectionToPublication extends Migration
{
    public function up()
    {
        DBManager::get()->exec("
            ALTER TABLE `evasys_global_profiles`
            ADD COLUMN `enable_objection_to_publication` enum('yes', 'no') NOT NULL DEFAULT 'no' AFTER `lockaftertransferforrole`,
            ADD COLUMN `objection_teilbereich` VARCHAR(128) DEFAULT NULL AFTER `enable_objection_to_publication`
        ");
        DBManager::get()->exec("
            ALTER TABLE `evasys_institute_profiles`
            ADD COLUMN `enable_objection_to_publication` enum('yes', 'no') DEFAULT NULL AFTER `lockaftertransferforrole`,
            ADD COLUMN `objection_teilbereich` VARCHAR(128) DEFAULT NULL AFTER `enable_objection_to_publication`
        ");

        DBManager::get()->exec("
            ALTER TABLE `evasys_course_profiles`
            ADD COLUMN `objection_to_publication` tinyint(1) NOT NULL DEFAULT '0' AFTER `locked`,
            ADD COLUMN `objection_reason` TEXT DEFAULT NULL AFTER `objection_to_publication`
        ");
        SimpleORMap::expireTableScheme();
    }

    public function down()
    {
        DBManager::get()->exec("
            ALTER TABLE `evasys_course_profiles`
            DROP COLUMN `objection_to_publication`,
            DROP COLUMN `objection_reason`
        ");
        DBManager::get()->exec("
            ALTER TABLE `evasys_institute_profiles`
            DROP COLUMN `enable_objection_to_publication`,
            DROP COLUMN `objection_teilbereich`
        ");
        DBManager::get()->exec("
            ALTER TABLE `evasys_global_profiles`
            DROP COLUMN `enable_objection_to_publication`,
            DROP COLUMN `objection_teilbereich`
        ");
        SimpleORMap::expireTableScheme();
    }
}
