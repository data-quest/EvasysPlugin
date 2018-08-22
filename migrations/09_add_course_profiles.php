<?php

class AddCourseProfiles extends Migration
{
    public function up()
    {
        DBManager::get()->exec("
            CREATE TABLE `evasys_course_profiles` (
                `course_profile_id` varchar(32) NOT NULL,
                `seminar_id` varchar(32) NOT NULL DEFAULT '',
                `semester_id` varchar(32) NOT NULL DEFAULT '',
                `form_id` int(11) DEFAULT NULL,
                `begin` int(11) DEFAULT NULL,
                `end` int(11) DEFAULT NULL,
                `teachers` text ,
                `results_email` text,
                `applied` tinyint(4) NOT NULL DEFAULT '0',
                `transferred` tinyint(4) NOT NULL DEFAULT '0',
                `surveys` text,
                `split` tinyint(4) NOT NULL DEFAULT '0',
                `by_dozent` tinyint(4) NOT NULL DEFAULT '0',
                `mode` enum('paper','online') DEFAULT NULL,
                `address` text,
                `language` text,
                `number_of_sheets` int(11) DEFAULT NULL,
                `hinweis` text,
                `user_id` varchar(32) DEFAULT NULL,
                `chdate` int(11) NOT NULL,
                `mkdate` int(11) NOT NULL,
                PRIMARY KEY (`course_profile_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");
        DBManager::get()->exec("
            CREATE TABLE `evasys_forms` (
                `form_id` int(11) NOT NULL,
                `name` varchar(256) DEFAULT NULL,
                `description` text DEFAULT NULL,
                `active` int(11) NOT NULL DEFAULT '0',
                `link` varchar(128) DEFAULT NULL,
                `chdate` int(11) DEFAULT NULL,
                `mkdate` int(11) DEFAULT NULL,
                PRIMARY KEY (`form_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");
        DBManager::get()->exec("
            CREATE TABLE `evasys_institute_profiles` (
                `institute_profile_id` varchar(32) NOT NULL DEFAULT '',
                `institut_id` varchar(32) NOT NULL DEFAULT '',
                `semester_id` varchar(32) NOT NULL DEFAULT '',
                `form_id` int(11) DEFAULT NULL,
                `begin` int(11) DEFAULT NULL,
                `end` int(11) DEFAULT NULL,
                `results_email` text,
                `mode` enum('paper','online') DEFAULT NULL,
                `address` text,
                `antrag_begin` int(11) DEFAULT NULL,
                `antrag_end` int(11) DEFAULT NULL,
                `antrag_info` text,
                `language` text DEFAULT NULL,
                `user_id` varchar(32) DEFAULT NULL,
                `chdate` int(11) NOT NULL,
                `mkdate` int(11) NOT NULL,
                PRIMARY KEY (`institute_profile_id`),
                UNIQUE KEY `institute_id` (`institut_id`,`semester_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");
        DBManager::get()->exec("
            CREATE TABLE `evasys_global_profiles` (
                `semester_id` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
                `form_id` int(11) DEFAULT NULL,
                `begin` int(11) DEFAULT NULL,
                `end` int(11) DEFAULT NULL,
                `adminedit_begin` int(11) DEFAULT NULL,
                `adminedit_end` int(11) DEFAULT NULL,
                `mode` enum('paper','online') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
                `address` text COLLATE utf8mb4_unicode_ci,
                `antrag_begin` int(11) DEFAULT NULL,
                `antrag_end` int(11) DEFAULT NULL,
                `antrag_info` text COLLATE utf8mb4_unicode_ci,
                `language` text DEFAULT NULL,
                `user_id` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
                `chdate` int(11) NOT NULL,
                `mkdate` int(11) NOT NULL,
                PRIMARY KEY (`semester_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        DBManager::get()->exec("
            CREATE TABLE `evasys_profiles_semtype_forms` (
                `profile_form_id` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL,
                `profile_id` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
                `profile_type` enum('global','institute') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'global',
                `form_id` int(11) NOT NULL,
                `sem_type` int(11) NOT NULL,
                `position` int(11) DEFAULT NULL,
                `standard` tinyint(4) NOT NULL DEFAULT '0',
                `chdate` int(11) DEFAULT NULL,
                `mkdate` int(11) DEFAULT NULL,
                PRIMARY KEY (`profile_form_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        DBManager::get()->exec("
            CREATE TABLE `evasys_matchings` (
                `matching_id` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
                `item_id` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
                `item_type` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
                `name` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
                `chdate` int(11) DEFAULT NULL,
                `mkdate` int(11) DEFAULT NULL,
                PRIMARY KEY (`matching_id`),
                KEY `item_id` (`item_id`),
                KEY `item_type` (`item_type`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        Config::get()->create("EVASYS_ENABLE_PROFILES", array(
            'value' => 1,
            'type' => "boolean",
            'range' => "global",
            'section' => "EVASYS_PLUGIN",
            'description' => "Is the feature to edit the evaluation profiles on in Stud.IP?"
        ));
        Config::get()->create("EVASYS_ENABLE_PROFILES_FOR_ADMINS", array(
            'value' => 1,
            'type' => "boolean",
            'range' => "global",
            'section' => "EVASYS_PLUGIN",
            'description' => "Are admins (not root) allowed to edit their institute-profiles?"
        ));
        Config::get()->create("EVASYS_ENABLE_SPLITTING_COURSES", array(
            'value' => 0,
            'type' => "boolean",
            'range' => "global",
            'section' => "EVASYS_PLUGIN",
            'description' => "May a course with multiple teachers be split into multiple evaluations?"
        ));
        Config::get()->create("EVASYS_FORCE_ONLINE", array(
            'value' => 0,
            'type' => "boolean",
            'range' => "global",
            'section' => "EVASYS_PLUGIN",
            'description' => "In editing the profiles there are only online-evaluations allowed."
        ));
        Config::get()->create("EVASYS_ENABLE_MESSAGE_FOR_ADMINS", array(
            'value' => 1,
            'type' => "boolean",
            'range' => "global",
            'section' => "EVASYS_PLUGIN",
            'description' => "Should admins receive messages if a new profile is edited by a teacher?"
        ));
        DBManager::get()->exec("
            INSERT INTO roles
            SET rolename = 'Evasys-Admin',
            system = 'n'
        ");
        DBManager::get()->exec("
            INSERT INTO roles
            SET rolename = 'Evasys-Dozent-Admin',
            system = 'n'
        ");
        Config::get()->create("EVASYS_LANGUAGE_OPTIONS", array(
            'value' => "",
            'type' => "string",
            'range' => "global",
            'section' => "EVASYS_PLUGIN",
            'description' => "Name the languages (Deutsch English), which are selectable for paper-evaluations. Separate them with newlines. Leave empty for free text."
        ));
        Config::get()->create("EVASYS_TRANSFER_PERMISSION", array(
            'value' => "root",
            'type' => "string",
            'range' => "global",
            'section' => "EVASYS_PLUGIN",
            'description' => "Which permission state is necessary to transfer a course to evasys (root, admin, dozent)."
        ));

        DBManager::get()->exec("ALTER TABLE evasys_seminar DROP PRIMARY KEY");
        DBManager::get()->exec("
            DELETE e1 FROM evasys_seminar AS e1, evasys_seminar AS e2
            WHERE e1.Seminar_id = e2.Seminar_id 
                AND e1.evasys_id != e2.evasys_id
        "); //remove possible double entries, which could possibly have occured in the past
        DBManager::get()->exec("ALTER TABLE evasys_seminar ADD PRIMARY KEY (`Seminar_id`), ADD KEY `evasys_id` (`evasys_id`)");

        StudipLog::registerActionPlugin('EVASYS_EVAL_APPLIED', 'Evasys: Lehrevaluation wurde beantragt', '%user beantragt neue Lehrevaluation %coaffected(%info) für %user(%affected).', 'EvasysPlugin');
        StudipLog::registerActionPlugin('EVASYS_EVAL_UPDATE', 'Evasys: Lehrevaluationsdaten geändert', '%user ändert Lehrevaluationsdaten %coaffected(%info) für %user(%affected).', 'EvasysPlugin');
        StudipLog::registerActionPlugin('EVASYS_EVAL_DELETE', 'Evasys: Lehrevaluation gelöscht', '%user löscht Lehrevaluation %coaffected(%info) für %user(%affected).', 'EvasysPlugin');
        StudipLog::registerActionPlugin('EVASYS_EVAL_TRANSFER', 'Evasys: Lehrevaluation nach Evasys übertragen', '%user überträgt Lehrevaluation %coaffected(%info) für %user(%affected) nach Evasys.', 'EvasysPlugin');
        SimpleORMap::expireTableScheme();
    }
    
    public function down()
    {
        StudipLog::unregisterAction('EVASYS_EVAL_APPLIED');
        StudipLog::unregisterAction('EVASYS_EVAL_UPDATE');
        StudipLog::unregisterAction('EVASYS_EVAL_DELETE');
    }
}
