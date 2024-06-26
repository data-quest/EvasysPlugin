<? foreach ($semesters as $i => $semester) : ?>
<?
if (($GLOBALS['user']->cfg->MY_COURSES_SELECTED_CYCLE && $GLOBALS['user']->cfg->MY_COURSES_SELECTED_CYCLE !== "all") && (count($semesters) > 1) && ($semester->getId() !== $GLOBALS['user']->cfg->MY_COURSES_SELECTED_CYCLE)) {
    continue;
}
$profile = null;
foreach ($profiles as $p) {
    if ($semester->getId() === $p['semester_id']) {
        $profile = $p;
        break;
    }
}
if ($profile === null) {
    $profile = EvasysCourseProfile::findBySemester(
        $course_id,
        $semester->getId()
    );
}
?>
<div class="evasys_profile_checkbox" style="<?= $i > 0 ? ' margin-top: 12px;' : "" ?>">
    <? if (count($semesters) > 1 && (!$GLOBALS['user']->cfg->MY_COURSES_SELECTED_CYCLE || $GLOBALS['user']->cfg->MY_COURSES_SELECTED_CYCLE === "all")) : ?>
        <div class="semester">
            <?= htmlReady($semester['name']) ?>
        </div>
    <? endif ?>
    <div class="controls">
        <? if ($profile['applied'] && $profile['by_dozent']) : ?>
            <?= Icon::create($plugin->getPluginURL()."/assets/f-circle_grey.svg")->asImg(20, array('title' => sprintf(dgettext("evasys", "Dies ist eine %s"), EvasysMatching::wording("freiwillige Evaluation")))) ?>
        <? endif ?>

        <? $teachers_not_in_course = $profile->teachersNotInCourse() ?>
        <? if (count($teachers_not_in_course) > 0) : ?>
            <? $teachers_not_in_course = array_map(function ($user_id) { $u = User::find($user_id); return $u ? $u->getFullName() : _('unbekannt'); }, $teachers_not_in_course) ?>
            <?= Icon::create($plugin->getPluginURL().'/assets/warn.svg', 'status-red')->asImg(20, ['class' => "text-bottom", 'title' => sprintf(dgettext('evasys', 'Lehrende %s sind beantragt aber nicht mehr in der Veranstaltung.'), implode(", ", $teachers_not_in_course))]) ?>
        <? endif ?>

        <? if (!$profile->hasDatesInEvalTimespan()) : ?>
            <?= Icon::create("exclaim-circle", "status-red")->asImg(20, array('title' => dgettext("evasys", "Es gibt keinen Termin dieser Veranstaltung im gewünschten Evaluationszeitraum"))) ?>
        <? endif ?>

        <? if ($profile['transferred']) : ?>
            <?= Icon::create($plugin->getPluginURL()."/assets/evasys-logo.svg", "inactive")->asImg(26, array(
                    'title' => dgettext("evasys", "Veranstaltung wurde bereits übertragen."),
                    'class' => "text-bottom"
            )) ?>
        <? endif ?>

        <? if ($profile->isEditable()) : ?>
            <a href="<?= PluginEngine::getLink($plugin, array('cid' => $course_id, 'semester_id' => $profile['semester_id']), "profile/edit/".$course_id) ?>"
               data-dialog
               title="<?= dgettext("evasys", "Evaluation beantragen oder bearbeiten").($profile->isChangedAfterTransfer() ? '. '.dgettext("evasys", "Evaluationsdaten wurden nach Transfer noch einmal verändert.") : '') ?>">
                <? if ($profile['applied']) : ?>
                    <? if ($profile->isChangedAfterTransfer()) : ?>
                        <?= Icon::create($plugin->getPluginURL()."/assets/check-circle-new.svg" /*"check-circle+new"*/, "clickable")->asImg(20) ?>
                    <? else : ?>
                        <?= Icon::create("check-circle", "clickable")->asImg(20) ?>
                    <? endif ?>
                <? else : ?>
                    <? if ($profile->isChangedAfterTransfer()) : ?>
                        <?= Icon::create("radiobutton-unchecked+new", "clickable")->asImg(20) ?>
                    <? else : ?>
                        <?= Icon::create("radiobutton-unchecked", "clickable")->asImg(20) ?>
                    <? endif ?>
                <? endif ?>
            </a>
        <? else : ?>
            <?= Icon::create(($profile && $profile['applied']) ? "check-circle" : "radiobutton-unchecked", "inactive")->asImg(20) ?>
        <? endif ?>

        <input type="checkbox"
               name="c[<?= htmlReady($course_id) ?>]"
               <?= !$profile->isEditable() ? "disabled" : ""?>
               value="1">
        <input type="hidden" name="course[]" value="<?= htmlReady($course_id."_".$profile['semester_id']) ?>">
    </div>
</div>
<? endforeach ?>
