<?php

class AddLanguageMatching extends Migration
{
    public function up()
    {
        Config::get()->create("EVASYS_LANGUAGE_MATCHING", array(
            'value' => '',
            'type' => "string",
            'range' => "global",
            'section' => "EVASYS_PLUGIN",
            'description' => "The languages of Evasys in the way English=en_GB separated by newlines. The first language is the language in Evasys and the second is the name of the language code in Stud.IP."
        ));
        DBManager::get()->exec("
            ALTER TABLE `evasys_forms`
            ADD COLUMN `translations` text DEFAULT NULL
        ");
        SimpleORMap::expireTableScheme();
    }

    public function down()
    {
        Config::get()->delete("EVASYS_LANGUAGE_MATCHING");
        DBManager::get()->exec("
            ALTER TABLE `evasys_forms`
            DROP COLUMN `translations`
        ");
        SimpleORMap::expireTableScheme();
    }
}
