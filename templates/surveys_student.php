<?php

/*
 *  Copyright (c) 2011  Rasmus Fuhse <fuhse@data-quest.de>
 *
 *  This program is free software; you can redistribute it and/or
 *  modify it under the terms of the GNU General Public License as
 *  published by the Free Software Foundation; either version 2 of
 *  the License, or (at your option) any later version.
 */
?>
<? if (count($open_surveys)) : ?>
    <? foreach ($open_surveys as $survey) : ?>
        <? if ($survey->TransactionNumber) : ?>
        <iframe
            id="survey_<?= htmlReady($survey->TransactionNumber) ?>"
            style="width: 100%; height: 600px; border: 0px;"
            src="<?= htmlReady(get_config("EVASYS_URI")."/indexstud.php?typ=html&user_tan=".urlencode($survey->TransactionNumber)) ?>">
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