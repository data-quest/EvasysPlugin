<? if (count($surveys)) : ?>
    <? foreach ($surveys as $survey) : ?>
        <? if ($survey->TransactionNumber && ($survey->TransactionNumber !== "null")) : ?>
            <iframe
                id="survey_<?= htmlReady($survey->TransactionNumber) ?>"
                style="width: 100%; height: 600px; border: 0px;"
                frameborder="0"
                allowfullscreen
                src="<?= htmlReady(Config::get()->EVASYS_URI."/indexstud.php?typ=html&user_tan=".urlencode($survey->TransactionNumber)) ?>">
            </iframe>
        <? else : ?>
            <?= MessageBox::success(_("Sie haben schon an der aktuellen Evaluation teilgenommen. Besten Dank.")) ?>
        <? endif ?>
    <? endforeach ?>
<? else : ?>
    <?= MessageBox::info(_("Es gibt für Sie keine aktuellen, ausstehenden Evaluationen zu dieser Veranstaltung.")) ?>
<? endif ?>
<script>
    jQuery("iframe[id^=survey_]").each(function (index, frame) {
        if (document.getElementById(frame.id).contentWindow.document) {
            jQuery(frame).css("height", document.getElementById(frame.id).contentWindow.document.body.scrollHeight);
        }
    });
</script>