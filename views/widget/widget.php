<div style="padding: 10px;">
    <table class="default">
        <thead>
            <tr>
                <th>
                    <?= dgettext("evasys", "Veranstaltung") ?>
                </th>
                <th>
                    <?= dgettext("evasys", "Evaluation bis") ?>
                </th>
                <th>
                    <?= dgettext("evasys", "Rücklaufquote") ?>
                </th>
                <? /*<th></th> */ ?>
            </tr>
        </thead>
        <tbody>
            <? if (!empty($courses)) : ?>
                <? foreach ($courses as $course) : ?>
                    <? $profile = EvasysCourseProfile::findBySemester($course['Seminar_id']) ?>
                    <tr>
                        <td>
                            <a href="<?= URLHelper::getLink("plugins.php/evasysplugin/evaluation/show", array('cid' => $course['Seminar_id']), true) ?>">
                                <? if (Config::get()->IMPORTANT_SEMNUMBER && $course['Nummer']) : ?>
                                    <?= htmlReady($course['Nummer']) ?>:
                                <? endif ?>
                                <?= htmlReady($course['Name']) ?>
                            </a>
                        </td>
                        <td><?= date("d.m.Y G:i", $profile->getFinalEnd()) ?></td>
                        <? $return = round(100 * $course['ResponseCount'] / ($course['ParticipantCount'] ?: 1)) ?>
                        <? $color = $return >= 80 ? '#a8ce70' : ($return >= 30 ? '#a1aec7' : '#d60000') ?>
                        <td style="background-image: linear-gradient(0deg, <?= $color ?>, <?= $color ?>); background-repeat: no-repeat; background-size: <?= (int) $return ?>% 100%; width: 150px;">
                            <? if ($return >= 80) : ?>
                                <span style="float: right;">
                                    <?= Icon::create("accept", "info_alt")->asImg(20, ['class' => "text-bottom"]) ?>
                                </span>
                            <? endif ?>
                            <?= (int) $return ?>%
                        </td>
                        <? /*<td>
                            <? $evasys_seminar = new EvasysSeminar($course->getId()) ?>
                            <? $pdf_link = $evasys_seminar->getPDFLink($survey->m_nSurveyId) ?>
                            <? if ($pdf_link) : ?>
                                <a href="<?= htmlReady($pdf_link) ?>" target="_blank" title="<?= dgettext("evasys", "Ergebnisse ansehen") ?>">
                                    <?= Icon::create("file-pdf", "clickable")->asImg(20) ?>
                                </a>
                            <? else : ?>
                                <?= Icon::create("file-pdf", "inactive")->asImg(20, array('title' => dgettext("evasys", "Es liegen noch keine Ergebnisse vor."))) ?>
                            <? endif ?>
                        </td> */ ?>
                    </tr>
                <? endforeach ?>
            <? else : ?>
                <tr>
                    <td colspan="100" style="text-align: center;">
                        <?= dgettext("evasys", "Keine laufenden Evaluationen") ?>
                    </td>
                </tr>
            <? endif ?>
        </tbody>
    </table>
</div>
