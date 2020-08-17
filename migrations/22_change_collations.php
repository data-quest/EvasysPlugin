<?php

class ChangeCollations extends Migration
{
    public function up()
    {
        DBManager::get()->exec("
            ALTER TABLE `evasys_additional_fields`
            CHANGE `field_id` `field_id` VARCHAR(32) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL;
        ");
        DBManager::get()->exec("
            ALTER TABLE `evasys_additional_fields_values`
            CHANGE `field_id` `field_id` VARCHAR(32) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL;
        ");
        DBManager::get()->exec("
            ALTER TABLE `evasys_additional_fields_values`
            CHANGE `profile_id` `profile_id` VARCHAR(32) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL;
        ");
        DBManager::get()->exec("
            ALTER TABLE `evasys_course_profiles`
            CHANGE `course_profile_id` `course_profile_id` VARCHAR(32) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL;
        ");
        DBManager::get()->exec("
            ALTER TABLE `evasys_course_profiles`
            CHANGE `seminar_id` `seminar_id` VARCHAR(32) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL;
        ");
        DBManager::get()->exec("
            ALTER TABLE `evasys_course_profiles`
            CHANGE `semester_id` `semester_id` VARCHAR(32) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL;
        ");
        DBManager::get()->exec("
            ALTER TABLE `evasys_course_profiles`
            CHANGE `user_id` `user_id` CHAR(32) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL;
        ");
        DBManager::get()->exec("
            ALTER TABLE `evasys_global_profiles`
            CHANGE `semester_id` `semester_id` VARCHAR(32) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL;
        ");
        DBManager::get()->exec("
            ALTER TABLE `evasys_institute_profiles`
            CHANGE `institute_profile_id` `institute_profile_id` VARCHAR(32) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL;
        ");
        DBManager::get()->exec("
            ALTER TABLE `evasys_institute_profiles`
            CHANGE `institut_id` `institut_id` VARCHAR(32) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL;
        ");
        DBManager::get()->exec("
            ALTER TABLE `evasys_institute_profiles`
            CHANGE `semester_id` `semester_id` VARCHAR(32) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL;
        ");
        DBManager::get()->exec("
            ALTER TABLE `evasys_matchings`
            CHANGE `matching_id` `matching_id` VARCHAR(32) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL;
        ");
        DBManager::get()->exec("
            ALTER TABLE `evasys_matchings`
            CHANGE `item_id` `item_id` VARCHAR(32) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL;
        ");
        DBManager::get()->exec("
            ALTER TABLE `evasys_profiles_semtype_forms`
            CHANGE `profile_form_id` `profile_form_id` VARCHAR(32) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL;
        ");
        DBManager::get()->exec("
            ALTER TABLE `evasys_profiles_semtype_forms`
            CHANGE `profile_id` `profile_id` VARCHAR(32) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL;
        ");
        DBManager::get()->exec("
            ALTER TABLE `evasys_seminar`
            CHANGE `Seminar_id` `Seminar_id` VARCHAR(32) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL;
        ");
        SimpleORMap::expireTableScheme();
    }

    public function down()
    {
    }
}
