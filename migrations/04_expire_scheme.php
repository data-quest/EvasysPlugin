<?php
require_once 'lib/classes/SimpleORMap.class.php';
class ExpireScheme extends DBMigration
{
    function up()
    {
        SimpleORMap::expireTableScheme();
    }
}