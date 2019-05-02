<? $editable = $profile->isEditable() ?>
<? if (!$editable && !$profile['applied']) : ?>
    <?= MessageBox::info(_("Diese Veranstaltung ist aktuell nicht für eine Lehrveranstaltungsevaluation vorgesehen. Rückfragen richten Sie bitte an Ihr zuständiges Studiendekanat.")) ?>
<? else : ?>


    <form action="<?= PluginEngine::getLink($plugin, array('semester_id' => Request::option("semester_id")), "profile/edit/".$profile['seminar_id']) ?>"
          method="post"
          <?= Request::isDialog() ? "data-dialog" : "" ?>
          class="default">

        <? if ($editable && !EvasysPlugin::isAdmin($profile['seminar_id']) && !EvasysPlugin::isRoot()) : ?>
            <? $antrag_info = $profile->getAntragInfo() ?>
            <? if (trim($antrag_info)) : ?>
            <fieldset style="padding-top: 10px;">
                <?= formatReady($profile->getAntragInfo()) ?>
            </fieldset>
            <? endif ?>
        <? endif ?>

        <fieldset>
            <legend>
                <?= _("Evaluationsdaten") ?>
            </legend>

            <? if ($editable) : ?>
            <label>
                <input type="checkbox"
                       name="data[applied]"
                       value="1"
                       onChange="jQuery('#evasys_evaldata').toggle();"
                        <?= $profile['applied'] ? " checked" : "" ?>>
                <?= _("Veranstaltung soll evaluiert werden.") ?>
            </label>
            <? endif ?>

            <div<?= $profile['applied'] ? '' : ' style="display: none;"' ?> id="evasys_evaldata">
                <? $seminar = new Seminar($profile['seminar_id']) ?>
                <? $teachers = $seminar->getMembers("dozent") ?>
                <?= _("Wer wird evaluiert?") ?>
                <ul class="clean evasys_teachers<?= $editable ? " editable" : "" ?><?= Config::get()->EVASYS_ENABLE_SPLITTING_COURSES && $profile['split'] ? " split" : "" ?>">
                    <?
                    $active = array_flip($profile['teachers'] ? $profile['teachers']->getArrayCopy() : array());
                    usort($teachers, function ($a, $b) use ($active) {
                        if (!isset($active[$a['user_id']]) && !isset($active[$b['user_id']])) {
                            return $a['position'] < $b['position'] ? -1 : 1;
                        }
                        if (!isset($active[$a['user_id']])) {
                            return 1;
                        }
                        if (!isset($active[$b['user_id']])) {
                            return -1;
                        }
                        return $active[$a['user_id']] < $active[$b['user_id']] ? -1 : 1;
                    }) ?>
                    <? foreach ($teachers as $number => $teacher) : ?>
                    <li>
                        <? if ($editable && count($teachers) > 1) : ?>
                        <label>
                            <?= Assets::img("anfasser_24.png", array('class' => "anfasser")) ?>
                        <? endif ?>
                            <span class="avatar" style="background-image: url('<?= Avatar::getAvatar($teacher['user_id'])->getURL(Avatar::MEDIUM) ?>');"></span>
                            <?= htmlReady($teacher['fullname']) ?>
                            <input type="checkbox"
                                   name="data[teachers][]"
                                   <?= $profile->isEditable() ? "" : "disabled" ?>
                                   value="<?= htmlReady($teacher['user_id']) ?>"
                                   <?= count($teachers) === 1 || (!$profile['teachers'] && ($number == 0 || !Config::get()->EVASYS_SELECT_FIRST_TEACHER)) || ($profile['teachers'] && in_array($teacher['user_id'], $profile['teachers']->getArrayCopy())) ? " checked" : "" ?>>
                            <span class="note">(<?= _("Wird auf dem Fragebogen genannt.") ?>)</span>
                        <? if ($editable && count($teachers) > 1) : ?>
                        </label>
                        <? endif ?>
                    </li>
                    <? endforeach ?>
                </ul>

                <? if (Config::get()->EVASYS_ENABLE_SPLITTING_COURSES && count($teachers) > 1) : ?>
                <label>
                    <? if ($editable) : ?>
                    <input type="checkbox"
                           name="data[split]"
                           value="1"
                           onChange="jQuery('.evasys_teachers').toggleClass('split');"
                           <?= $profile['split'] ? " checked" : "" ?>>
                    <? else : ?>
                        <?= Icon::create("checkbox-".(!$profile['split'] ? "un" : "")."checked", "info") ?>
                    <? endif ?>
                    <?= _("Lehrende einzeln evaluieren") ?>
                </label>
                <? endif ?>

                <? if ($editable || trim($profile['results_email'])) : ?>
                <label>
                    <?= _("Weitere Emailadressen, an die die Ergebnisse gesendet werden sollen (mit Leerzeichen getrennt)") ?>
                    <? if ($editable) : ?>
                    <input type="text" name="data[results_email]" value="<?= htmlReady($profile['results_email']) ?>">
                    <? else : ?>
                    <div>
                        <?= htmlReady($profile['results_email']) ?>
                    </div>
                    <? endif ?>
                </label>
                <? endif ?>

                <label>
                    <?= _("Evaluationsbeginn") ?>
                    <? $begin = $profile->getFinalBegin() ?>
                    <? if ($editable) : ?>
                    <input type="text" name="data[begin]" value="<?= $begin ? date("d.m.Y H:i", $begin) : "" ?>" class="datepicker evasys_begin">
                    <? else : ?>
                    <div>
                        <?= $begin ? date("d.m.Y H:i", $begin) : "" ?>
                    </div>
                    <? endif ?>
                </label>

                <label>
                    <?= _("Evaluationsende") ?>
                    <? $end = $profile->getFinalEnd() ?>
                    <? if ($editable) : ?>
                    <input type="text" name="data[end]" value="<?= $end ? date("d.m.Y H:i", $end) : "" ?>" class="datepicker evasys_end">
                    <? else : ?>
                        <div>
                            <?= $end ? date("d.m.Y H:i", $end) : "" ?>
                        </div>
                    <? endif ?>
                </label>

                <? if (!$profile->hasDatesInEvalTimespan()) : ?>
                    <?= MessageBox::error(_("Kein Veranstaltungstermin befindet sich in dem vorgesehenen Evaluationszeitraum!")) ?>
                    <? if (!empty($profile->course->dates) && $editable) : ?>
                        <?= _("Anderen Termin auswählen") ?>
                        <?
                            $default_date = null;
                            $last_date = null;
                            foreach ($profile->course->dates as $date) {
                                $default_date = $last_date;
                                if (($date['end_time'] > time()) && ($date['date'] < Semester::findCurrent()->ende)) {
                                    $last_date = $date;
                                }
                                if ($date['date'] >= Semester::findCurrent()->ende) {
                                    break;
                                }
                            }
                            if (!$default_date) {
                                $default_date = $last_date;
                            }
                            //Es wurde der vorletzte Termin gesucht. Falls es nur einen Termin gibt, ist es eben der letzte Termin.
                        ?>
                        <? if ($default_date) : ?>
                            <div>
                                <?= \Studip\LinkButton::create(sprintf(_("Termin am %s aussuchen"), date("d.m.Y", $default_date['date'])), "#", array('onClick' => "jQuery('.evasys_begin').val('". date("d.m.Y H:i", $default_date['date']) ."'); jQuery('.evasys_end').val('". date("d.m.Y H:i", $default_date['end_time']) ."'); return false;")) ?>
                            </div>
                        <? endif ?>
                        <div class="evasys_propose_dates">
                            <table class="default nohover">
                                <tbody>
                                <? foreach ($profile->course->dates as $date) : ?>
                                    <? if (($date['end_time'] > time()) && ($date['date'] < Semester::findCurrent()->ende)) : ?>
                                        <? $found = true ?>
                                        <tr>
                                            <td>
                                                <? if (date("d.m.Y", $date['date']) !== date("d.m.Y", $date['end_time'])) : ?>
                                                <?= date("d.m.Y H:i", $date['date']) ?> - <?= date("d.m.Y H:i", $date['end_time']) ?>
                                                <? else : ?>
                                                    <?= date("d.m.Y H:i", $date['date']) ?> - <?= date("H:i", $date['end_time']) ?>
                                                <? endif ?>
                                            </td>
                                            <td>
                                                <? if (count($profile->course->statusgruppen) != count($date->statusgruppen)) : ?>
                                                    <? foreach ($date->statusgruppen as $i => $statusgruppe) : ?>
                                                    <? if ($i > 0) : ?>
                                                        ,
                                                    <? endif ?>
                                                    <?= htmlReady($statusgruppe['name']) ?>
                                                    <? endforeach ?>
                                                <? endif ?>
                                            </td>
                                            <td class="actions">
                                                <a href="#" onClick="jQuery('.evasys_begin').val('<?= date("d.m.Y H:i", $date['date']) ?>'); jQuery('.evasys_end').val('<?= date("d.m.Y H:i", $date['end_time']) ?>'); return false;"
                                                   title="<?= _("Termin als Evaluationszeitraum auswählen") ?>">
                                                    <?= Icon::create("date+move_up", "clickable")->asImg(20) ?>
                                                </a>
                                            </td>
                                        </tr>
                                    <? endif ?>
                                <? endforeach ?>
                                <? if (!$found) : ?>
                                <tr>
                                    <td style="text-align: center;">
                                        <?= _("Keine möglichen Termine gefunden.") ?>
                                    </td>
                                </tr>
                                <? endif ?>
                                </tbody>
                            </table>
                        </div>
                        <? endif ?>
                <? endif ?>

                <div style="margin-top: 10px;">
                    <table class="default nohover">
                        <thead>
                            <tr>
                                <th><?= _("Ausgewähler Fragebogen") ?></th>
                                <th class="actions"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <? if ($editable) : ?>
                                <? $standard_form_id = $profile->getPresetFormId() ?>
                                <? $form = EvasysForm::find($standard_form_id) ?>
                                <? if ($form) : ?>
                                    <tr>
                                        <td>
                                            <label>
                                                <input type="radio" name="data[form_id]" value="<?= htmlReady($form->getId()) ?>"<?= (!$profile['form_id'] || ($profile['form_id'] === $form->getId())) ? " checked" : "" ?>>
                                                <?= htmlReady($form['name'].": ".$form['description']) ?>
                                            </label>
                                        </td>
                                        <td class="actions">
                                            <? if ($form['link']) : ?>
                                            <a href="<?= htmlReady($form['link']) ?>" target="_blank">
                                                <?= Icon::create("info-circle", "clickable")->asImg(20) ?>
                                            </a>
                                            <? endif ?>
                                        </td>
                                    </tr>
                                <? endif ?>
                                <? foreach ($profile->getAvailableFormIds() as $form_id) : ?>
                                    <? if ($form_id != $standard_form_id) : ?>
                                        <? $form = EvasysForm::find($form_id) ?>
                                        <tr>
                                            <td>
                                                <label>
                                                    <input type="radio" name="data[form_id]" value="<?= htmlReady($form->getId()) ?>"<?= $profile['form_id'] === $form->getId() ? " checked" : "" ?>>
                                                    <?= htmlReady($form['name'].": ".$form['description']) ?>
                                                </label>
                                            </td>
                                            <td class="actions">
                                                <? if ($form['link']) : ?>
                                                    <a href="<?= htmlReady($form['link']) ?>" target="_blank">
                                                        <?= Icon::create("info-circle", "clickable")->asImg(20) ?>
                                                    </a>
                                                <? endif ?>
                                            </td>
                                        </tr>
                                    <? endif ?>
                                <? endforeach ?>
                            <? else : ?>
                                <? $form_id = $profile->getFinalFormId() ?>
                                <? $form = EvasysForm::find($form_id) ?>
                                <? if ($form) : ?>
                                    <tr>
                                        <td>
                                            <?= htmlReady($form['name'].": ".$form['description']) ?>
                                        </td>
                                        <td class="actions">
                                            <? if ($form['link']) : ?>
                                                <a href="<?= htmlReady($form['link']) ?>" target="_blank">
                                                    <?= Icon::create("info-circle", "clickable")->asImg(20) ?>
                                                </a>
                                            <? endif ?>
                                        </td>
                                    </tr>
                                <? endif ?>
                            <? endif ?>
                        </tbody>
                    </table>
                </div>

                <? if (!Config::get()->EVASYS_FORCE_ONLINE) : ?>
                    <label>
                        <?= _("Art der Evaluation") ?>
                        <? if ($editable) : ?>
                        <select name="data[mode]" onClick="jQuery('.evasys_paper').toggle(this.value === 'paper');">
                            <option value=""></option>
                            <option value="online"<?= $profile->getFinalMode() === "online" ? " selected" : "" ?>>
                                <?= _("Online-Evaluation") ?>
                            </option>
                            <option value="paper"<?= $profile->getFinalMode() === "paper" ? " selected" : "" ?>>
                                <?= _("Papierbasierte Evaluation") ?>
                            </option>
                        </select>
                        <? else : ?>
                            <div>
                                <?= $profile->getFinalMode() === "online" ? _("Online-Evaluation") : _("Papierbasierte Evaluation") ?>
                            </div>
                        <? endif ?>
                    </label>

                    <div class="evasys_paper" style="<?= $profile->getFinalMode() !== "paper" ? "display: none;" : "" ?>">
                        <? foreach (EvasysAdditionalField::findBySQL("`paper` = '1' ORDER BY position ASC, name ASC") as $field) : ?>
                            <label>
                                <? $value = $field->valueFor("course", $profile->getId()) ?>
                                <?= htmlReady($field['name']) ?>
                                <? if ($field['type'] === "TEXT") : ?>
                                    <input type="text" name="field[<?= $field->getId() ?>]" value="<?= htmlReady($value) ?>"<?= !$editable ? " readonly" : "" ?>>
                                <? else : ?>
                                    <textarea name="field[<?= $field->getId() ?>]"<?= !$editable ? " readonly" : "" ?>><?= htmlReady($value) ?></textarea>
                                <? endif ?>
                            </label>
                        <? endforeach ?>
                    </div>
                <? endif ?>

                <? foreach (EvasysAdditionalField::findBySQL("`paper` = '0' ORDER BY position ASC, name ASC") as $field) : ?>
                    <label>
                        <? $value = $field->valueFor("course", $profile->getId()) ?>
                        <?= htmlReady($field['name']) ?>
                        <? if ($field['type'] === "TEXT") : ?>
                            <input type="text" name="field[<?= $field->getId() ?>]" value="<?= htmlReady($value) ?>"<?= !$editable ? " readonly" : "" ?>>
                        <? else : ?>
                            <textarea name="field[<?= $field->getId() ?>]"<?= !$editable ? " readonly" : "" ?>><?= htmlReady($value) ?></textarea>
                        <? endif ?>
                    </label>
                <? endforeach ?>
            </div>

        </fieldset>

        <? if (!$profile->isNew() && $editable) : ?>
        <fieldset>
            <legend><?= _("Log") ?></legend>

            <? $last_author = $profile['user_id'] ? User::find($profile['user_id']) : null ?>
            <?= MessageBox::info(sprintf(_("Letzte Bearbeitung von %s am %s Uhr"), ($last_author ? $last_author->getFullName() : ($profile['user_id'] ?: _("unbekannt"))), date("d.m.Y H:i", $profile['chdate'])) ) ?>

            <? if ($profile['by_dozent'] && (EvasysPlugin::isRoot() || EvasysPlugin::isAdmin($profile['seminar_id']))) : ?>
                <?= MessageBox::info(sprintf(_("Diese Veranstaltung ist eine %s."), EvasysMatching::wording("freiwillige Evaluation"))) ?>
            <? endif ?>

            <? if ($profile['transferred']) : ?>
                <?= MessageBox::info(_("Diese Veranstaltung wurde bereits an den Evaluationsserver übertragen.")) ?>
            <? endif ?>
        </fieldset>
        <? endif ?>

        <? if ($editable) : ?>
            <script>
                jQuery(function () {
                    jQuery("input.datepicker").datetimepicker();
                    <? if ($editable && count($teachers) > 1) : ?>
                    jQuery(".evasys_teachers").sortable({
                        "axis": "y",
                        "handle": ".avatar, .anfasser",
                        "revert": 300
                    });
                    <? endif ?>
                });
            </script>

            <div data-dialog-button>
                <? if ($profile['by_dozent'] && (EvasysPlugin::isRoot() || EvasysPlugin::isAdmin($profile['seminar_id']))) : ?>
                    <?= \Studip\Button::create(_("In Pflichtevaluation umwandeln"), "unset_by_dozent", array('onclick' => "return window.confirm('"._("Wirklich in Pflichtevaluation umwandeln?")."');")) ?>
                <? endif ?>

                <?= \Studip\Button::create(_("Speichern")) ?>
            </div>
        <? else : ?>
            <? $info = $profile->getPresetAttribute("teacher_info") ?>
            <? if (trim($info)) : ?>
                <fieldset style="padding-top: 10px;">
                    <?= formatReady($info) ?>
                </fieldset>
            <? endif ?>
        <? endif ?>
    </form>
<? endif ?>
