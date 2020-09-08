<? if (!$surveys && empty($surveys)) : ?>
    <h3><?= dgettext("evasys", "Es gibt keine aktuellen Evaluationen zu dieser Veranstaltung.") ?></h3>
<? else : ?>
    <div style="padding: 15px; font-size: 1.2em; text-align: center;">
        <? if (count($surveys) < 2) : ?>
            <h3><?= dgettext("evasys", "Es gibt eine Evaluation zu dieser Veranstaltung") ?></h3>
        <? elseif(empty($surveys)) : ?>
            <h3><?= dgettext("evasys", "Keine verfügbaren Evaluationen") ?></h3>
        <? else : ?>
            <h3><?= dgettext("evasys", "Evaluationen zu dieser Veranstaltung") ?></h3>
        <? endif ?>

        <? foreach ($surveys as $survey) : ?>

            <table class="default">
                <tbody>
                <tr>
                    <td><?= dgettext("evasys", "Semester") ?></td>
                    <td><?= htmlReady(Semester::findByTimestamp(strtotime($survey->m_oPeriod->m_sEndDate))->name ?: $survey->m_oPeriod->m_sTitel) ?></td>
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
                <tr>
                    <td><?= dgettext("evasys", "Status") ?></td>
                    <td><? switch($survey->m_nState) {
                            case 0:
                                echo dgettext("evasys", "Bereit / Keine Daten vorhanden");
                                break;
                            case 1:
                                echo dgettext("evasys", "Daten vorhanden / Bereit zur Auswertung");
                                break;
                            case 4:
                                echo dgettext("evasys", "Daten unter Mindestrücklauf");
                                break;
                            case 5:
                                echo dgettext("evasys", "Validierung / Datenerfassungskraft");
                                break;
                            case 6:
                                echo dgettext("evasys", "Verifikation");
                        } ?></td>
                </tr>
                <? if (($evasys_seminar->publishingAllowed($dozent_id) || in_array($GLOBALS['user']->id, $dozent_ids)) && $evasys_seminar->reportsAllowed()) : ?>
                    <? $pdf_link = $evasys_seminar->getPDFLink($survey->m_nSurveyId) ?>
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

        <? if ($GLOBALS['perm']->have_studip_perm("dozent", Context::get()->id)) : ?>
            <?= dgettext("evasys", "Zum Administrieren dieser Evaluation melden Sie sich bitte in EvaSys an oder wenden Sie sich an Ihren Administrator.") ?>
        <? endif ?>

    </div>
<? endif ?>


