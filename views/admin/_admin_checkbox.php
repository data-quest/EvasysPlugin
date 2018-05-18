<? if (!Config::get()->EVASYS_ENABLE_PROFILES) : ?>
    <? if ($checkbox) : ?>
        <input type="checkbox"
               name="c[<?= htmlReady($profile['seminar_id']) ?>]"
               value="1"
               <?= EvasysSeminar::countBySQL("Seminar_id = ? AND activated = 1", array($profile['seminar_id'])) > 0 ? " checked" : "" ?>>
        <input type="hidden" name="course[]" value="<?= htmlReady($profile['seminar_id']) ?>">
    <? endif ?>
<? else : ?>
    <? if ($profile && $profile['applied'] && !$profile->hasDatesInEvalTimespan()) : ?>
        <?= Icon::create("exclaim-circle", "status-red")->asImg(20, array('title' => _("Es gibt keinen Termin dieser Veranstaltung im gewünschten Evaluationszeitraum"))) ?>
    <? endif ?>

    <? if ($profile && $profile['transferred']) : ?>
        <?= Icon::create("arr_1up", "inactive")->asImg(20, array('title' => _("Veranstaltung wurde bereits übertragen."))) ?>
    <? endif ?>

    <a href="<?= PluginEngine::getLink($plugin, array(), "profile/edit/".$profile['seminar_id']) ?>" data-dialog>
        <?= Icon::create(($profile && $profile['applied']) ? "check-circle" : "radiobutton-unchecked", "clickable")->asImg(20) ?>
    </a>

    <? if ($checkbox) : ?>
        <input type="checkbox"
               name="c[<?= htmlReady($profile['seminar_id']) ?>]"
               value="1">
        <input type="hidden" name="course[]" value="<?= htmlReady($profile['seminar_id']) ?>">
    <? endif ?>
<? endif ?>
