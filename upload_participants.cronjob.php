<?php

class EvasysUploadParticipantsJob extends CronJob
{
    /**
     * Returns the name of the cronjob.
     */
    public static function getName()
    {
        return dgettext("evasys", 'evasys: Teilnehmer erneut hochladen');
    }

    /**
     * Returns the description of the cronjob.
     */
    public static function getDescription()
    {
        return dgettext("evasys", 'Gleicht alle in den nächten 28 Stunden startenden Evaluationen nochmal mit dem EvaSys-Server ab und lädt zum Beispiel die aktuelle Teilnehmerliste automatisch mit hoch.');
    }

    /**
     * Setup method. Loads neccessary lib and checks environment. Will
     * bail out with an exception if environment does not match requirements.
     */
    public function setUp()
    {
        ini_set("memory_limit","1024M"); //won't work with suhosin
        require_once __DIR__."/EvasysPlugin.class.php";
    }

    /**
     * Return the parameters for this cronjob.
     *
     * @return Array Parameters.
     */
    public static function getParameters()
    {
        return [
            'prevent_paper' => [
                'type'        => 'boolean',
                'default'     => "0",
                'status'      => 'optional',
                'description' => _('Wenn diese Option gewählt ist, werden keine papierbasierten Evaluationen mit übertragen.')
            ]
        ];
    }

    /**
     * Executes the cronjob.
     *
     * @param mixed $last_result What the last execution of this cronjob
     *                           returned.
     * @param Array $parameters Parameters for this cronjob instance which
     *                          were defined during scheduling.
     *                          Only valid parameter at the moment is
     *                          "verbose" which toggles verbose output while
     *                          purging the cache.
     */
    public function execute($last_result, $parameters = [])
    {
        $start = mktime(0, 0, 0, date("n"), date("j") + 1); // 0 Uhr des nächsten Tages
        $end = $start + 86400; // 0 Uhr des übernächsten Tages
        $sql = "
            SELECT `evasys_course_profiles`.`seminar_id`
            FROM `evasys_course_profiles`
                LEFT JOIN seminare ON (`evasys_course_profiles`.`seminar_id` = `seminare`.`Seminar_id`)
                LEFT JOIN evasys_institute_profiles ON (`evasys_institute_profiles`.`institut_id` = `seminare`.`Institut_id`
                        AND evasys_institute_profiles.semester_id = `evasys_course_profiles`.`semester_id`)
                LEFT JOIN `Institute` ON (`seminare`.`Institut_id` = `Institute`.`Institut_id`)
                LEFT JOIN `evasys_institute_profiles` AS `evasys_fakultaet_profiles` ON (`evasys_fakultaet_profiles`.`institut_id` = `Institute`.`fakultaets_id`
                        AND `evasys_fakultaet_profiles`.`semester_id` = `evasys_course_profiles`.`semester_id`)
                LEFT JOIN evasys_global_profiles ON (`evasys_global_profiles`.`semester_id` = `evasys_course_profiles`.`semester_id`)
            WHERE `evasys_course_profiles`.`transferred` = '1'
                AND IFNULL(`evasys_course_profiles`.`begin`, IFNULL(evasys_institute_profiles.begin, IFNULL(evasys_fakultaet_profiles.begin, evasys_global_profiles.begin))) >= :start
                AND IFNULL(`evasys_course_profiles`.`begin`, IFNULL(evasys_institute_profiles.begin, IFNULL(evasys_fakultaet_profiles.begin, evasys_global_profiles.begin))) < :end
        ";
        if ($parameters['prevent_paper']) {
            $sql .= " AND IFNULL(`evasys_course_profiles`.`mode`, IFNULL(evasys_institute_profiles.`mode`, IFNULL(evasys_fakultaet_profiles.`mode`, evasys_global_profiles.`mode`))) = 'online'";
        }
        $statement = DBManager::get()->prepare($sql);
        $statement->execute([
            'start' => $start,
            'end' => $end
        ]);
        $seminars = [];
        foreach ($statement->fetchAll(PDO::FETCH_COLUMN, 0) as $seminar_id) {
            $seminars[] = new EvasysSeminar($seminar_id);
        }
        if (count($seminars)) {
            echo count($seminars) . " Veranstaltungen werden mit EvaSys synchronisiert.\n";
            echo implode(" ", array_map(function ($s) { return $s->getId(); }, $seminars))."\n";
            $error = EvasysSeminar::UploadSessions($seminars);
            if (is_string($error)) {
                echo "error: " . $error . "\n";
            }
            foreach (PageLayout::getMessages() as $messagebox) {
                echo $messagebox->class . ": " . $messagebox->message . "\n";
            }
        } else {
            echo "Es müssen keine Evaluationen am ".date("d.m.Y", $start)." gestartet werden, daher wird auch nichts synchronisiert.";
        }
    }
}
