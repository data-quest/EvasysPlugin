<?php

class ChangeDefaultReportsAfterEvaluation extends Migration
{
    public function up()
    {
        DBManager::get()->exec("
            ALTER TABLE `evasys_global_profiles` ALTER `reports_after_evaluation` SET DEFAULT 'no';
        ");
        SimpleORMap::expireTableScheme();
    }

    public function down()
    {
        DBManager::get()->exec("
            ALTER TABLE `evasys_global_profiles` ALTER `reports_after_evaluation` SET DEFAULT 'yes';
        ");
        SimpleORMap::expireTableScheme();
    }
}
