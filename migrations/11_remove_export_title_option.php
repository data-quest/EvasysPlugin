<?php

class RemoveExportTitleOption extends Migration
{
    public function up()
    {
        Config::get()->delete("EVASYS_EXPORT_TITLES");
    }
}
