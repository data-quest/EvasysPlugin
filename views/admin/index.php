<?php

$args = array();
if (Request::get("page")) {
    $args['page'] = Request::get("page");
}
if (Request::get("semester")) {
    $args['semester'] = Request::get("semester");
}
if (Request::get("inst")) {
    $args['inst'] = Request::get("inst");
}

?>
<form action="<?= PluginEngine::getLink($plugin, array(), "admin/index") ?>" method="<?= Request::submitted("semester") ? "POST" : "GET" ?>">
    <div style="padding: 10px; border: thin solid #aaaaaa; width: 40%; margin-left: auto; margin-right: auto; margin-bottom: 50px;">
        <table style="width: 100%">
            <tbody>
                <tr>
                    <td>
                        <label for="semester"><?= _("Semester") ?></label>
                    </td>
                    <td>
                        <select name="semester" id="semester" style="width: 100%;">
                            <option value=""><?= _("alle") ?></option>
                            <? foreach (Semester::findBySql('TRUE ORDER BY beginn DESC') as $semester) : ?>
                            <option value="<?= $semester['semester_id'] ?>"<?= Request::get("semester") === $semester['semester_id'] ? " selected" : "" ?>><?= htmlReady($semester['name']) ?></option>
                            <? endforeach ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td>
                        <label for="inst"><?= _("Einrichtung") ?></label>
                    </td>
                    <td>
                        <select name="inst" id="inst" style="width: 100%;">
                            <option value=""><?= _("alle") ?></option>
                            <? foreach ($institute as $institut) : ?>
                            <option value="<?= $institut['Institut_id'] ?>"<?= Request::get("inst") === $institut['Institut_id'] ? " selected" : "" ?>><?= htmlReady($institut['Name']) ?></option>
                            <? endforeach ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td>
                        <label for="sem_type"><?= _("Veranstaltungstyp") ?></label>
                    </td>
                    <td>
                        <select name="sem_type" id="sem_type" style="width: 100%;">
                            <option value=""><?= _("alle") ?></option>
                            <? foreach ($GLOBALS['SEM_TYPE'] as $number => $type) : ?>
                            <option value="<?= $number ?>"<?= Request::int("sem_type") === $number ? " selected" : "" ?>><?= htmlReady($GLOBALS['SEM_CLASS'][$type['class']]['name']." - ".$type['name']) ?></option>
                            <? endforeach ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td>
                        <label for="sem_dozent"><?= _("DozentIn") ?></label>
                    </td>
                    <td>
                        <?php
                        $qs = new Quicksearch("sem_dozent", new PermissionSearch("user", _("Dozenten suchen"), "user_id", array("permission" => "dozent", "exclude_user" => array())));
                        if (Request::get("sem_dozent") && Request::get("sem_dozent_parameter")) {
                            $qs->defaultValue(Request::get("sem_dozent"), get_fullname(Request::get("sem_dozent"), "full_rev_username"));
                        }
                        $qs->setInputStyle("width: 100%");
                        echo $qs->render();
                        ?>
                    </td>
                </tr>
                <tr>
                    <td>
                        <label for="sem_name"><?= _("Name/Nummer") ?></label>
                    </td>
                    <td>
                        <input name="sem_name" id="sem_name" style="width: 100%;" value="<?= htmlReady(Request::get('sem_name')) ?>">
                    </td>
                </tr>
                <tr>
                    <td></td>
                    <td>
                        <?= \Studip\Button::create(_("Auswählen"), "auswaehlen") ?>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="page_nav">
        <? if ($nextPage) : ?>
        <div style="float: right;">
            <a href="<?= URLHelper::getLink("?", array_merge($args, array('page' => Request::get("page") + 1))) ?>"><?= _("nächste Seite") ?></a>
        </div>
        <? endif ?>
        <? if (Request::int("page") > 0) : ?>
        <div>
            <a href="<?= URLHelper::getLink("?", array_merge($args, array('page' => Request::get("page") - 1))) ?>"><?= _("vorige Seite") ?></a>
        </div>
        <? endif ?>
        <div style="clear:both;"></div>
    </div>

    <? if ($searched) : ?>
    <table class="default" id="select_table">
        <thead>
            <tr>
            <th><?= _("Nummer") ?></th>
            <th><?= _("Veranstaltung") ?></th>
                <th><?= _("Dozenten") ?></th>
                <th><?= _("Zeitraum") ?></th>
                <th>
                    <input type="checkbox" data-proxyfor="#select_table > tbody :checkbox">
                    <?= _("Plugin aktiviert") ?>
                </th>
            </tr>
        </thead>
        <tbody>
        <? if (count($courses)) : ?>
        <? foreach ($courses as $course) : ?>
            <tr>
                <td>
                    <?=htmlready($course['VeranstaltungsNummer']);?>
                </td>
                <td>
                    <a href="<?= URLHelper::getLink("seminar_main.php", array('auswahl' => $course['Seminar_id'])) ?>"><?= htmlReady($course['Name']) ?></a>
                </td>
                <td>
                    <? foreach (explode("_", $course['dozenten']) as $key => $dozent_id) {
                        if ($key !== 0) echo ", ";
                        echo '<a href="'.URLHelper::getLink("dispatch.php/profile", array('username' => get_username($dozent_id))).'">';
                        echo htmlready(get_fullname($dozent_id));
                        echo '</a>';
                    } ?>
                </td>
                <td><?
                    $semester1 = Semester::findByTimestamp($course['start_time']);
                    if ($semester1) {
                        echo htmlReady($semester1['name']);
                        if ($course['duration_time'] === "-1") {
                            echo " - "._("unbegrenzt");
                        } elseif($course['duration_time'] > 0) {
                            $semester2 = Semester::findByTimestamp($course['start_time'] + $course['duration_time']);
                            if ($semester2['semester_id'] !== $semester1['semester_id']) {
                                echo " - ".htmlReady($semester2['name']);
                            }
                        }
                    }
                ?></td>
                <td>
                    <input type="checkbox" name="activate[<?= $course['Seminar_id'] ?>]" id="activate_<?= $course['Seminar_id'] ?>" value="1" <?= $course['activated'] ? " checked" : "" ?>>
                    <input type="hidden" name="course[]" value="<?= $course['Seminar_id'] ?>">
                </td>
            </tr>
        <? endforeach ?>
        <? else : ?>
            <tr>
                <td colspan="5" style="text-align: center;">
                    <?= _("Keine Ergebnisse gefunden.") ?>
                </td>
            </tr>
        <? endif ?>
        </tbody>
    </table>


    <div class="page_nav">
        <? if ($nextPage) : ?>
        <div style="float: right;">
            <a href="<?= URLHelper::getLink("?", array_merge($args, array('page' => Request::get("page") + 1))) ?>"><?= _("nächste Seite") ?></a>
        </div>
        <? endif ?>
        <? if (Request::int("page") > 0) : ?>
        <div>
            <a href="<?= URLHelper::getLink("?", array_merge($args, array('page' => Request::get("page") - 1))) ?>"><?= _("vorige Seite") ?></a>
        </div>
        <? endif ?>
        <div style="clear:both;"></div>
    </div>

    <div style="text-align: center;">
        <?= \Studip\Button::create(_("Absenden"), "absenden") ?>
    </div>

    <? endif ?>
</form>