<?php

class AddReminderMessageConfig extends Migration
{
    public function up()
    {
        $field = "EVASYS_REMINDER_MESSAGE";
        $message_de = "Für die Veranstaltung {{coursename}} ist eine Evaluation freigeschaltet worden. Sie können hier daran teilnehmen: {{url}}";
        $message_en = "A new evaluation started for {{coursename}}. You can participate here: {{url}}";

        Config::get()->create($field, array(
            'value' => $message_de,
            'type' => "i18n",
            'range' => "global",
            'section' => "EVASYS_PLUGIN",
            'description' => "Message text for reminder messages. Possible placeholders are: {{coursename}}, {{url}}, {{evaluationsbeginn}} and {{evaluationsende}}"
        ));

        $i18n_identifier = md5($field);

        $translation = new I18NString($i18n_identifier);
        $translation->setMetadata([
            'object_id' => $i18n_identifier,
            'table' => "config",
            'field' => "value"
        ]);
        if (Config::get()->CONTENT_LANGUAGES["de_DE"]) {
            $translation->setLocalized($message_de, "de_DE");
        }
        if (Config::get()->CONTENT_LANGUAGES["en_GB"]) {
            $translation->setLocalized($message_en, "en_GB");
        }
        $translation->storeTranslations();


        $field = "EVASYS_REMINDER_MESSAGE_SUBJECT";
        $message_de = "Neue Evaluation: {{coursename}}";
        $message_en = "New evaluation: {{coursename}}";

        Config::get()->create($field, array(
            'value' => $message_de,
            'type' => "i18n",
            'range' => "global",
            'section' => "EVASYS_PLUGIN",
            'description' => "Message text for reminder messages."
        ));

        $i18n_identifier = md5($field);

        $translation = new I18NString($i18n_identifier);
        $translation->setMetadata([
            'object_id' => $i18n_identifier,
            'table' => "config",
            'field' => "value"
        ]);
        if (Config::get()->CONTENT_LANGUAGES["de_DE"]) {
            $translation->setLocalized($message_de, "de_DE");
        }
        if (Config::get()->CONTENT_LANGUAGES["en_GB"]) {
            $translation->setLocalized($message_en, "en_GB");
        }
        $translation->storeTranslations();
    }

    public function down()
    {
        Config::get()->delete("EVASYS_REMINDER_MESSAGE");
        Config::get()->delete("EVASYS_REMINDER_MESSAGE_SUBJECT");
    }
}
