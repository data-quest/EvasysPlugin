<?php
class AddSendMessagesLastExecutionOption extends Migration
{
	function up()
    {
        Config::get()->create("EVASYS_SEND_MESSAGES_LAST_EXECUTION", array(
            'value' => 0,
            'type' => "integer",
            'range' => "global",
            'section' => "EVASYS_PLUGIN",
            'description' => "When was teh cronjob to send messages EvasysSendMessagesJob last executed? Leave this option as it is. No need to change it manually."
        ));
	}

    public function down()
    {
        Config::get()->delete("EVASYS_SEND_MESSAGES_LAST_EXECUTION");
    }
}
