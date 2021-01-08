<?php

class IconController extends PluginController {

    public function get_action($course_id)
    {
        $evasys_seminars = EvasysSeminar::findBySeminar($course_id);
        foreach ($evasys_seminars as $evasys_seminar) {
            $number += $evasys_seminar->getEvaluationStatus();

            if ($number > 0) break;
        }
        
        $icon_url = Icon::create("evaluation", ($number > 0)?"new":"inactive")->asImagePath();
        $icon_path = $GLOBALS['STUDIP_BASE_PATH'] . '/public' . parse_url($icon_url, PHP_URL_PATH);

        header("Content-Type: image/svg+xml");
        readfile($icon_path);

        exit;
    }

}
