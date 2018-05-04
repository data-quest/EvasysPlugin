<? if (!Config::get()->EVASYS_ENABLE_PROFILES) : ?>
    <input type="checkbox"
           name="c[<?= htmlReady($course_id) ?>]"
           value="1"
           <?= EvasysSeminar::countBySQL("Seminar_id = ? AND activated = 1", array($course_id)) > 0 ? " checked" : "" ?>>
    <input type="hidden" name="course[]" value="<?= htmlReady($course_id) ?>">
<? else : ?>
    <? if ($profile && $profile['applied'] && !$profile->hasDatesInEvalTimespan()) : ?>
        <?= Icon::create("exclaim-circle", "status-red")->asImg(20, array('title' => _("Es gibt keinen Termin dieser Veranstaltung im gewünschten Evaluationszeitraum"))) ?>
    <? endif ?>

    <? if ($profile && $profile['transferred']) : ?>
        <?= Icon::create("arr_1up", "inactive")->asImg(20, array('title' => _("Veranstaltung wurde bereits übertragen."))) ?>
    <? endif ?>

    <a href="<?= PluginEngine::getLink($plugin, array(), "profile/edit/".$course_id) ?>" data-dialog>
        <?= Icon::create(($profile && $profile['applied']) ? "check-circle" : "radiobutton-unchecked", "clickable")->asImg(20) ?>
    </a>


    <input type="checkbox"
           name="c[<?= htmlReady($course_id) ?>]"
           value="1">
    <input type="hidden" name="course[]" value="<?= htmlReady($course_id) ?>">
<? endif ?>
