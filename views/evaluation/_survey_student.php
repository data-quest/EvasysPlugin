<? if (count($surveys)) : ?>

    <? foreach ($evasys_seminar->getSurveys() as $survey_data) : ?>
        <? if ($survey_data->TransactionNumber && ($survey_data->TransactionNumber !== "null")) : ?>
            <iframe
                id="survey_<?= htmlReady($survey_data->TransactionNumber) ?>"
                style="width: 100%; height: 600px; border: 0px;"
                frameborder="0"
                allowfullscreen
                src="<?= htmlReady(Config::get()->EVASYS_URI."/indexstud.php?typ=html&user_tan=".urlencode($survey_data->TransactionNumber)) ?>">
            </iframe>
        <? else : ?>
            <?= MessageBox::success(_("Sie haben schon an der aktuellen Evaluation teilgenommen. Besten Dank.")) ?>
        <? endif ?>
    <? endforeach ?>
<? else : ?>
    <? if ($evasys_seminar->publishingAllowed()) : ?>
        <?= $this->render_partial("evaluation/_survey_dozent.php", array(
            'surveys' => $surveys,
            'evasys_seminar' => $evasys_seminars
        )) ?>
    <? else : ?>
        <?= MessageBox::info(_("Es gibt für Sie keine aktuellen, ausstehenden Evaluationen zu dieser Veranstaltung.")) ?>
    <? endif ?>
<? endif ?>
<script>
    jQuery("iframe[id^=survey_]").each(function (index, frame) {
        if (document.getElementById(frame.id).contentWindow.document) {
            jQuery(frame).css("height", document.getElementById(frame.id).contentWindow.document.body.scrollHeight);
        }
    });
</script>