<?php

class AddTwoMailEditors extends Migration
{
    public function up()
    {
        DBManager::get()->exec("
            ALTER TABLE `evasys_global_profiles`
            ADD COLUMN `mail_apply_subject` TEXT DEFAULT NULL AFTER `mail_reminder_body`
        ");
        DBManager::get()->exec("
            ALTER TABLE `evasys_global_profiles`
            ADD COLUMN `mail_apply_body` TEXT DEFAULT NULL AFTER `mail_apply_subject`
        ");

        DBManager::get()->exec("
            ALTER TABLE `evasys_institute_profiles`
            ADD COLUMN `mail_apply_subject` TEXT DEFAULT NULL AFTER `mail_reminder_body`
        ");
        DBManager::get()->exec("
            ALTER TABLE `evasys_institute_profiles`
            ADD COLUMN `mail_apply_body` TEXT DEFAULT NULL AFTER `mail_apply_subject`
        ");

        DBManager::get()->exec("
            ALTER TABLE `evasys_global_profiles`
            ADD COLUMN `mail_changed_subject` TEXT DEFAULT NULL AFTER `mail_apply_body`
        ");
        DBManager::get()->exec("
            ALTER TABLE `evasys_global_profiles`
            ADD COLUMN `mail_changed_body` TEXT DEFAULT NULL AFTER `mail_changed_subject`
        ");
        DBManager::get()->exec("
            ALTER TABLE `evasys_institute_profiles`
            ADD COLUMN `mail_changed_subject` TEXT DEFAULT NULL AFTER `mail_apply_body`
        ");
        DBManager::get()->exec("
            ALTER TABLE `evasys_institute_profiles`
            ADD COLUMN `mail_changed_body` TEXT DEFAULT NULL AFTER `mail_changed_subject`
        ");

        $statement = DBManager::get()->prepare("
            UPDATE evasys_global_profiles
            SET `mail_apply_subject` = :subject,
                `mail_apply_body` = :body
        ");
        $statement->execute([
            'subject' => "Lehrevaluation für Veranstaltung '{{course}}' wurde von {{person}} beantragt",
            'body' => "{{person}} hat eine Lehrevaluation für die Veranstaltung {{course}} beantragt. Sie können die Evaluationsdaten hier einsehen: \n\n{{url}}"
        ]);

        $statement = DBManager::get()->prepare("
            UPDATE evasys_global_profiles
            SET `mail_changed_subject` = :subject,
                `mail_changed_body` = :body
        ");
        $statement->execute([
            'subject' => "Bearbeitung der Evaluationsdaten",
            'body' => "{{person}} hat gerade die Lehrevaluationsdaten der Veranstaltung '{{course}}' verändert. Die geänderten Daten können Sie hier einsehen und gegebenenfalls bearbeiten: \n\n {{url}}"
        ]);


        SimpleORMap::expireTableScheme();
    }

    public function down()
    {
        DBManager::get()->exec("
            ALTER TABLE `evasys_global_profiles`
            DROP COLUMN `mail_apply_subject`
        ");
        DBManager::get()->exec("
            ALTER TABLE `evasys_global_profiles`
            DROP COLUMN `mail_apply_body`
        ");
        DBManager::get()->exec("
            ALTER TABLE `evasys_institute_profiles`
            DROP COLUMN `mail_apply_subject`
        ");
        DBManager::get()->exec("
            ALTER TABLE `evasys_institute_profiles`
            DROP COLUMN `mail_apply_body`
        ");

        DBManager::get()->exec("
            ALTER TABLE `evasys_global_profiles`
            DROP COLUMN `mail_changed_subject`
        ");
        DBManager::get()->exec("
            ALTER TABLE `evasys_global_profiles`
            DROP COLUMN `mail_changed_body`
        ");
        DBManager::get()->exec("
            ALTER TABLE `evasys_institute_profiles`
            DROP COLUMN `mail_changed_subject`
        ");
        DBManager::get()->exec("
            ALTER TABLE `evasys_institute_profiles`
            DROP COLUMN `mail_changed_body`
        ");
        SimpleORMap::expireTableScheme();
    }
}
