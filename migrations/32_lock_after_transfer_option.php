<?php

class LockAfterTransferOption extends Migration
{
    public function up()
    {
        Config::get()->create("EVASYS_LOCK_AFTER_TRANSFER_FOR_ROLE", array(
            'value' => '',
            'type' => "string",
            'range' => "global",
            'section' => "EVASYS_PLUGIN",
            'description' => "Should evaluations be locked for dozent or admin or noone '' after transfer?"
        ));
        DBManager::get()->exec("
            ALTER TABLE `evasys_course_profiles`
            ADD COLUMN `locked` tinyint(1) DEFAULT '0' AFTER `user_id`
        ");
    }

    public function down()
    {
        DBManager::get()->exec("
            ALTER TABLE `evasys_course_profiles`
            DROP COLUMN `locked`
        ");
        Config::get()->delete("EVASYS_LOCK_AFTER_TRANSFER_FOR_ROLE");
    }
}
