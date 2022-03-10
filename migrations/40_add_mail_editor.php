<?php

class AddMailEditor extends Migration
{
    public function up()
    {
        DBManager::get()->exec("
            ALTER TABLE `evasys_global_profiles`
            ADD COLUMN `mail_begin_subject` TEXT DEFAULT NULL AFTER `objection_teilbereich`
        ");
        DBManager::get()->exec("
            ALTER TABLE `evasys_global_profiles`
            ADD COLUMN `mail_begin_body` TEXT DEFAULT NULL AFTER `mail_begin_subject`
        ");

        DBManager::get()->exec("
            ALTER TABLE `evasys_institute_profiles`
            ADD COLUMN `mail_begin_subject` TEXT DEFAULT NULL AFTER `objection_teilbereich`
        ");
        DBManager::get()->exec("
            ALTER TABLE `evasys_institute_profiles`
            ADD COLUMN `mail_begin_body` TEXT DEFAULT NULL AFTER `mail_begin_subject`
        ");

        DBManager::get()->exec("
            ALTER TABLE `evasys_global_profiles`
            ADD COLUMN `mail_reminder_subject` TEXT DEFAULT NULL AFTER `mail_begin_body`
        ");
        DBManager::get()->exec("
            ALTER TABLE `evasys_global_profiles`
            ADD COLUMN `mail_reminder_body` TEXT DEFAULT NULL AFTER `mail_reminder_subject`
        ");
        DBManager::get()->exec("
            ALTER TABLE `evasys_institute_profiles`
            ADD COLUMN `mail_reminder_subject` TEXT DEFAULT NULL AFTER `mail_begin_body`
        ");
        DBManager::get()->exec("
            ALTER TABLE `evasys_institute_profiles`
            ADD COLUMN `mail_reminder_body` TEXT DEFAULT NULL AFTER `mail_reminder_subject`
        ");

        if (Config::get()->EVASYS_REMINDER_MESSAGE && Config::get()->EVASYS_REMINDER_MESSAGE_SUBJECT) {
            $statement = DBManager::get()->prepare("
                UPDATE evasys_global_profiles
                SET `mail_reminder_subject` = :subject,
                    `mail_reminder_body` = :body
            ");
            $statement->execute([
                'subject' => Config::get()->EVASYS_REMINDER_MESSAGE_SUBJECT,
                'body' => Config::get()->EVASYS_REMINDER_MESSAGE
            ]);
        }

        Config::get()->delete('EVASYS_REMINDER_MESSAGE_SUBJECT');
        Config::get()->delete('EVASYS_REMINDER_MESSAGE');

        SimpleORMap::expireTableScheme();
    }

    public function down()
    {
        DBManager::get()->exec("
            ALTER TABLE `evasys_global_profiles`
            DROP COLUMN `mail_begin_subject`
        ");
        DBManager::get()->exec("
            ALTER TABLE `evasys_global_profiles`
            DROP COLUMN `mail_begin_body`
        ");
        DBManager::get()->exec("
            ALTER TABLE `evasys_institute_profiles`
            DROP COLUMN `mail_begin_subject`
        ");
        DBManager::get()->exec("
            ALTER TABLE `evasys_institute_profiles`
            DROP COLUMN `mail_begin_body`
        ");

        DBManager::get()->exec("
            ALTER TABLE `evasys_global_profiles`
            DROP COLUMN `mail_reminder_subject`
        ");
        DBManager::get()->exec("
            ALTER TABLE `evasys_global_profiles`
            DROP COLUMN `mail_reminder_body`
        ");
        DBManager::get()->exec("
            ALTER TABLE `evasys_institute_profiles`
            DROP COLUMN `mail_reminder_subject`
        ");
        DBManager::get()->exec("
            ALTER TABLE `evasys_institute_profiles`
            DROP COLUMN `mail_reminder_body`
        ");
        SimpleORMap::expireTableScheme();
    }
}
