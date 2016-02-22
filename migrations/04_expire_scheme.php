<?php
if (file_exists('lib/classes/SimpleORMap.class.php')) {
    require_once 'lib/classes/SimpleORMap.class.php';
}

class ExpireScheme extends Migration
{
    function up()
    {
        SimpleORMap::expireTableScheme();
    }
}