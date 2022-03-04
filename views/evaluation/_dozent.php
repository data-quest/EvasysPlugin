<div style="padding: 15px; font-size: 1.2em; text-align: center;">
    <? if (!$profile['transferred']) : ?>
        <table class="default">
            <tbody>
            <tr>
                <td><?= dgettext("evasys", "Semester") ?></td>
                <td><?= htmlReady($profile->semester['name']) ?></td>
            </tr>
            <tr>
                <td><?= dgettext("evasys", "Papier/Online-Umfrage") ?></td>
                <td><?= $profile->getFinalmode() === 'online' ? dgettext("evasys", "Onlineumfrage") : dgettext("evasys", "Papierumfrage") ?></td>
            </tr>
            <tr>
                <td><?= dgettext("evasys", "Status") ?></td>
                <td><?= dgettext("evasys", "Angemeldet") . ($profile->isEditable() ? " / ".dgettext("evasys","Anmeldedaten editierbar") : "") ?></td>
            </tr>
            </tbody>
        </table>
    <? else : ?>
        <? $surveys = $profile->getSurveyInformation($dozent_id) ?>
        <? foreach ($surveys as $survey) : ?>
            <table class="default">
                <tbody>
                    <tr>
                        <td><?= dgettext("evasys", "Semester") ?></td>
                        <td><?= htmlReady($profile->semester['name']) ?></td>
                    </tr>
                    <tr>
                        <td><?= dgettext("evasys", "Papier/Online-Umfrage") ?></td>
                        <td><?= $survey->m_cType == "o" ? dgettext("evasys", "Onlineumfrage") : dgettext("evasys", "Papierumfrage") ?></td>
                    </tr>
                    <tr>
                        <td><?= dgettext("evasys", "Aktueller Rücklauf") ?></td>
                        <td>
                            <? if ($survey->m_nFormCount == 0) : ?>
                                <?= dgettext("evasys", "Noch keine Antworten abgegeben") ?>
                            <? elseif ($survey->m_nFormCount == 1) : ?>
                                <?= dgettext("evasys", "Ein abgegebener Fragebogen") ?>
                            <? else : ?>
                                <?= htmlReady(sprintf("%s abgegebene Fragebögen", $survey->m_nFormCount)) ?>
                            <? endif ?>
                        </td>
                    </tr>
                    <? if (($profile->evasys_seminar->publishingAllowed($dozent_id) || in_array($GLOBALS['user']->id, $dozent_ids)) && $profile->evasys_seminar->reportsAllowed()) {
                        $pdf_link = $profile->evasys_seminar->getPDFLink($survey->m_nSurveyId);
                    } ?>
                    <tr>
                        <td><?= dgettext("evasys", "Status") ?></td>
                        <td><? switch($survey->m_nState) {
                                case 0:
                                    if ($profile->getFinalBegin() > time()) {
                                        echo dgettext("evasys", "Anmeldedaten übertragen und fixiert / Evaluationszeitraum noch nicht erreicht");
                                    } else {
                                        echo dgettext("evasys", "Lehrveranstaltungsevaluation bereit / keine Daten vorhanden");
                                    }
                                    break;
                                case 1:
                                    if ($profile->getFinalEnd() < time()) {
                                        echo dgettext("evasys", "Lehrveranstaltungsevaluation abgeschlossen")
                                                ." / "
                                                .($pdf_link ? dgettext("evasys", "Bericht kann abgerufen werden") : dgettext("evasys","Rücklauf für Bericht zu gering"));
                                    } else {
                                        echo dgettext("evasys", "Lehrveranstaltungsevaluation bereit / Bericht kann abgerufen werden");
                                    }
                                    break;
                                case 4:
                                    echo dgettext("evasys", "Lehrveranstaltungsevaluation bereit / Rücklauf für Bericht zu gering");
                                    break;
                                case 5:
                                    echo dgettext("evasys", "Validierung / Datenerfassungskraft");
                                    break;
                                case 6:
                                    echo dgettext("evasys", "Verifikation");
                            } ?></td>
                    </tr>
                    <? if (($profile->evasys_seminar->publishingAllowed($dozent_id) || in_array($GLOBALS['user']->id, $dozent_ids)) && $profile->evasys_seminar->reportsAllowed()) : ?>
                        <? if ($pdf_link) : ?>
                            <tr>
                                <td><?= dgettext("evasys", "Auswertung der Evaluation") ?></td>
                                <td>
                                    <a href="<?= htmlReady($pdf_link) ?>" target="_blank">
                                        <?= Icon::create("file-pdf", "clickable")->asImg(48) ?>
                                        <?= dgettext("evasys", "Ergebnisse als PDF") ?>
                                    </a>
                                </td>
                            </tr>
                        <? endif ?>
                    <? endif ?>
                </tbody>
            </table>
        <? endforeach ?>
    <? endif ?>
</div>



