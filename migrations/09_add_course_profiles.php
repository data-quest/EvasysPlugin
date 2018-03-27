<?php

class AddCourseProfiles extends Migration
{
    public function up()
    {
        DBManager::get()->exec("
            CREATE TABLE `evasys_course_profiles` (
                `course_profile_id` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL,
                `seminar_id` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
                `semester_id` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
                `form_id` int(11) DEFAULT NULL,
                `begin` int(11) DEFAULT NULL,
                `end` int(11) DEFAULT NULL,
                `applied` tinyint(4) NOT NULL DEFAULT '0',
                `individuell` tinyint(4) NOT NULL DEFAULT '0',
                `user_id` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
                `chdate` int(11) NOT NULL,
                `mkdate` int(11) NOT NULL,
                PRIMARY KEY (`course_profile_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");
        DBManager::get()->exec("
            CREATE TABLE `evasys_forms` (
                `form_id` int(11) NOT NULL,
                `name` varchar(256) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
                `active` int(11) NOT NULL DEFAULT '0',
                `link` varchar(128) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
                `chdate` int(11) DEFAULT NULL,
                `mkdate` int(11) DEFAULT NULL,
                PRIMARY KEY (`form_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");
        DBManager::get()->exec("
            CREATE TABLE `evasys_institute_profiles` (
                `institute_profile_id` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
                `institut_id` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
                `semester_id` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
                `form_id` int(11) DEFAULT NULL,
                `begin` int(11) DEFAULT NULL,
                `end` int(11) DEFAULT NULL,
                `user_id` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
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
                `user_id` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
                `chdate` int(11) NOT NULL,
                `mkdate` int(11) NOT NULL,
                PRIMARY KEY (`semester_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");
    }
    
    public function down()
    {

    }
}
