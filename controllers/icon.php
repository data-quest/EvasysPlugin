<?php

class IconController extends PluginController {

    public function get_action($course_id)
    {
        $evasys_seminars = EvasysSeminar::findBySeminar($course_id);
        foreach ($evasys_seminars as $evasys_seminar) {
            $number += $evasys_seminar->getEvaluationStatus();

            if ($number > 0) break;
        }
        
        $icon = Icon::create("evaluation", ($number > 0)?"new":"inactive")->asImagePath();
        $fp = fopen($icon, 'rb');
        header("Content-Type: svg/xml");
        header("Content-Length: " . filesize($icon));

        fpassthru($fp);
        exit;
    }

}
