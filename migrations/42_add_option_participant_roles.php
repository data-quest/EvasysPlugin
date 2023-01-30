<?php

class AddOptionParticipantRoles extends Migration
{
    public function up()
    {
        $user_role = Config::get()->EVASYS_PLUGIN_USE_LOWER_PERMISSION_LEVELS;

        $roles = ['autor', 'tutor'];
        if ($user_role) {
            $roles[] = 'user';
        }
        $roles = implode("\n", $roles);
        Config::get()->delete('EVASYS_PLUGIN_USE_LOWER_PERMISSION_LEVELS');
        Config::get()->create("EVASYS_PLUGIN_PARTICIPANT_ROLES", array(
            'value' => $roles,
            'type' => "string",
            'range' => "global",
            'section' => "EVASYS_PLUGIN",
            'description' => "What roles can participate in the evaluation?"
        ));
    }

    public function down()
    {
        Config::get()->delete('EVASYS_PLUGIN_PARTICIPANT_ROLES');
        Config::get()->create("EVASYS_PLUGIN_USE_LOWER_PERMISSION_LEVELS", array(
            'value' => 0,
            'type' => "boolean",
            'range' => "global",
            'section' => "EVASYS_PLUGIN",
            'description' => "Allow users with 'user' permissions to evaluate courses."
        ));
    }
}
