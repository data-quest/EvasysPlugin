<?php

class RemoveProfilesConfig extends Migration
{
    public function up()
    {
        Config::get()->delete("EVASYS_ENABLE_PROFILES");
    }

    public function down()
    {

    }
}
