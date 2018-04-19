<? if (!Config::get()->EVASYS_ENABLE_PROFILES) : ?>
    <input type="checkbox"
           name="c[<?= htmlReady($course_id) ?>]"
           value="1"
           <?= EvasysSeminar::countBySQL("Seminar_id = ? AND activated = 1", array($course_id)) > 0 ? " checked" : "" ?>>
    <input type="hidden" name="course[]" value="<?= htmlReady($course_id) ?>">
<? else : ?>
    <a href="<?= PluginEngine::getLink($plugin, array(), "profile/edit/".$course_id) ?>" data-dialog>
        <?= Icon::create(($profile && $profile['applied']) ? "check-circle" : "radiobutton-unchecked", "clickable")->asImg(20) ?>
    </a>

    <input type="checkbox"
           name="c[<?= htmlReady($course_id) ?>]"
           value="1"
        <?= EvasysSeminar::countBySQL("Seminar_id = ? AND activated = 1", array($course_id)) > 0 ? " checked" : "" ?>>
    <input type="hidden" name="course[]" value="<?= htmlReady($course_id) ?>">
<? endif ?>
