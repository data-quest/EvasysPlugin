<? $min_user_permissions = EvasysPlugin::useLowerPermissionLevels() ? "user" : "autor" ?>
<? if ($GLOBALS['perm']->have_studip_perm($min_user_permissions, Context::get()->id)
    && !$GLOBALS['perm']->have_studip_perm("dozent", Context::get()->id) && count($profile->getTANs($dozent_id)) > 0) : ?>

    <? foreach ($profile->getTANs($dozent_id) as $tan) : ?>
        <? $_SESSION['EVASYS_SURVEY_TAN_EXISTED_'.Context::get()->id] = true ?>
        <?= MessageBox::info(dgettext("evasys", "Falls die Evaluation länger braucht zum Laden, drücken Sie bitte nicht auf Neuladen der ganzen Seite.")) ?>

        <!-- Set scrollable div around iframe when on iOS (Safari and other Browsers on iOS need this to scroll the iframe) -->
        <? if (preg_match('/iP(ad|hone|od).+Safari/', $_SERVER['HTTP_USER_AGENT']) === 1) : ?>
            <div style="-webkit-overflow-scrolling: touch; overflow: scroll;">
                <iframe
                    id="survey_<?= htmlReady($tan) ?>"
                    style="width: 100%; height: 90vh; border: 0px;"
                    allowfullscreen
                    src="<?= htmlReady(Config::get()->EVASYS_URI.'/indexstud.php?typ=html&user_tan='.urlencode($tan)) ?>">
                </iframe>
            </div>
        <? else : ?>
            <iframe
                id="survey_<?= htmlReady($tan) ?>"
                style="width: 100%; height: 90vh; border: 0px;"
                allowfullscreen
                src="<?= htmlReady(Config::get()->EVASYS_URI.'/indexstud.php?typ=html&user_tan='.urlencode($tan)) ?>">
            </iframe>
        <? endif ?>
    <? endforeach ?>
<? else : ?>
    <? if ($_SESSION['EVASYS_SURVEY_TAN_EXISTED_'.Context::get()->id]) : ?>
        <?= MessageBox::success(dgettext("evasys", "Sie haben schon an der aktuellen Evaluation teilgenommen. Besten Dank!")) ?>
    <? else : ?>
        <?= MessageBox::info(dgettext("evasys", "Sie können nicht (mehr) an dieser Befragung teilnehmen.")) ?>
    <? endif ?>
    <? if ($profile->evasys_seminar->publishingAllowed($dozent_id) && $profile->evasys_seminar->reportsAllowed()) : ?>
        <?= $this->render_partial("evaluation/_dozent.php", [
            'profile' => $profile,
            'dozent_ids' => array($dozent_id)
        ]) ?>
    <? endif ?>
<? endif ?>


<script>
    jQuery("iframe[id^=survey_]").each(function (index, frame) {
        if (document.getElementById(frame.id).contentWindow.document) {
            jQuery(frame).css("height", document.getElementById(frame.id).contentWindow.document.body.scrollHeight);
        }
    });
</script>
