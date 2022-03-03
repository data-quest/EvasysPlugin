<?php

class EvasysAdditionalField extends SimpleORMap
{

    protected static function configure($config = [])
    {
        $config['db_table'] = 'evasys_additional_fields';
        $config['i18n_fields']['name'] = true;
        parent::configure($config);
    }

    public function valueFor($profile_type, $profile_id, $value = false)
    {
        if ($value !== false) {
            $statement = DBManager::get()->prepare("
                INSERT INTO `evasys_additional_fields_values`
                SET `field_id` = :field_id,
                    `profile_type` = :type,
                    profile_id = :profile_id,
                    `value` = :value,
                    `chdate` = UNIX_TIMESTAMP(),
                    `mkdate` = UNIX_TIMESTAMP()
                ON DUPLICATE KEY UPDATE
                    `value` = :value,
                    `chdate` = UNIX_TIMESTAMP()
            ");
            $statement->execute([
                'field_id' => $this->getId(),
                'profile_id' => $profile_id,
                'type' => $profile_type,
                'value' => $value
            ]);
        } else {
            $statement = DBManager::get()->prepare("
                SELECT `value`
                FROM `evasys_additional_fields_values`
                WHERE `field_id` = :field_id
                    AND `profile_type` = :type
                    AND profile_id = :profile_id
            ");
            $statement->execute([
                'field_id' => $this->getId(),
                'profile_id' => $profile_id,
                'type' => $profile_type
            ]);
            return $statement->fetch(PDO::FETCH_COLUMN, 0);
        }
    }

}
