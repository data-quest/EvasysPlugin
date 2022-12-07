<? $editable = $profile->isEditable() ?>
<? if (!$editable && !$profile['applied']) : ?>
    <?= MessageBox::info(dgettext("evasys", "Diese Veranstaltung ist aktuell nicht für eine Lehrveranstaltungsevaluation vorgesehen. Rückfragen richten Sie bitte an Ihr zuständiges Studiendekanat.")) ?>
<? else : ?>


    <form action="<?= PluginEngine::getLink($plugin, array('semester_id' => Request::option("semester_id")), "profile/edit/".$profile['seminar_id']) ?>"
          method="post"
          <?= Request::isDialog() ? "data-dialog" : "" ?>
          class="default">

        <? if ($editable && !Request::isDialog()) : ?>
            <? $antrag_info = $profile->getAntragInfo() ?>
            <? if (trim($antrag_info)) : ?>
            <fieldset style="padding-top: 10px;">
                <?= formatReady($profile->getAntragInfo()) ?>
            </fieldset>
            <? endif ?>
        <? endif ?>

        <fieldset>
            <legend>
                <?= dgettext("evasys", "Evaluationsdaten") ?>
            </legend>

            <? if ($editable) : ?>
            <label>
                <input type="checkbox"
                       name="data[applied]"
                       value="1"
                       onChange="jQuery('#evasys_evaldata').toggle();"
                        <?= $profile['applied'] ? " checked" : "" ?>>
                <?= dgettext("evasys", "Veranstaltung soll evaluiert werden.") ?>
            </label>
            <? endif ?>

            <div<?= $profile['applied'] ? '' : ' style="display: none;"' ?> id="evasys_evaldata">
                <? $seminar = new Seminar($profile['seminar_id']) ?>
                <? $teachers = $seminar->getMembers("dozent") ?>
                <?= dgettext("evasys", "Wer wird evaluiert?") ?>
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
                            <? if (!Config::get()->EVASYS_LEAVE_OUT_MENTIONING) : ?>
                                <span class="note">(<?= dgettext("evasys", "Wird auf dem Fragebogen genannt.") ?>)</span>
                            <? endif ?>
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
                    <?= dgettext("evasys", "Lehrende einzeln evaluieren") ?>
                </label>
                <? endif ?>

                <? if ($editable || trim($profile['results_email'])) : ?>
                <label>
                    <?= dgettext("evasys", "Weitere Emailadressen, an die die Ergebnisse gesendet werden sollen (mit Leerzeichen getrennt)") ?>
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
                    <?= dgettext("evasys", "Evaluationsbeginn") ?>
                    <?
                    $begin = $profile->getFinalBegin();
                    if ($profile->isNew() && ($begin < time()) && Config::get()->EVASYS_INDIVIDUAL_TIME_OFFSETS) {
                        $offsets = preg_split("/\n/", Config::get()->EVASYS_INDIVIDUAL_TIME_OFFSETS, -1, PREG_SPLIT_NO_EMPTY);
                        $begin = time() + $offsets[0] * 60;
                    }
                    ?>
                    <? if ($editable) : ?>
                        <input type="text"
                               name="data[begin]"
                               value="<?= $begin ? date("d.m.Y H:i", $begin) : "" ?>"
                               data-datetime-picker='{">=":"today"}'
                               id="evasys_eval_begin"
                               class="datepicker evasys_begin">

                    <? else : ?>
                    <div>
                        <?= $begin ? date("d.m.Y H:i", $begin) : "" ?>
                    </div>
                    <? endif ?>
                </label>

                <label>
                    <?= dgettext("evasys", "Evaluationsende") ?>
                    <?
                    $end = $profile->getFinalEnd();
                    if ($profile->isNew() && ($end <= time()) && Config::get()->EVASYS_INDIVIDUAL_TIME_OFFSETS) {
                        $offsets = preg_split("/\n/", Config::get()->EVASYS_INDIVIDUAL_TIME_OFFSETS, -1, PREG_SPLIT_NO_EMPTY);
                        $end = time() + $offsets[1] * 60;
                    }
                    ?>
                    <? if ($editable) : ?>
                    <input type="text"
                           name="data[end]"
                           value="<?= $end ? date("d.m.Y H:i", $end) : "" ?>"
                           data-datetime-picker='{">=":"#evasys_eval_begin"}'
                           class="datepicker evasys_end">
                    <? else : ?>
                        <div>
                            <?= $end ? date("d.m.Y H:i", $end) : "" ?>
                        </div>
                    <? endif ?>
                </label>

                <? if (!$profile->hasDatesInEvalTimespan()) : ?>
                    <?= MessageBox::warning(dgettext("evasys", "Im angegebenen Zeitraum befinden sich keine Veranstaltungstermine. Das wäre aber zu empfehlen.")) ?>
                    <? if (!empty($profile->course->dates) && $editable) : ?>
                        <?= dgettext("evasys", "Anderen Termin auswählen") ?>
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
                                <?= \Studip\LinkButton::create(sprintf(dgettext("evasys", "Termin am %s aussuchen"), date("d.m.Y", $default_date['date'])), "#", array('onClick' => "jQuery('.evasys_begin').val('". date("d.m.Y H:i", $default_date['date']) ."'); jQuery('.evasys_end').val('". date("d.m.Y H:i", $default_date['end_time']) ."'); return false;")) ?>
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
                                                   title="<?= dgettext("evasys", "Termin als Evaluationszeitraum auswählen") ?>">
                                                    <?= Icon::create("date+move_up", "clickable")->asImg(20) ?>
                                                </a>
                                            </td>
                                        </tr>
                                    <? endif ?>
                                <? endforeach ?>
                                <? if (!$found) : ?>
                                <tr>
                                    <td style="text-align: center;">
                                        <?= dgettext("evasys", "Keine möglichen Termine gefunden.") ?>
                                    </td>
                                </tr>
                                <? endif ?>
                                </tbody>
                            </table>
                        </div>
                        <? endif ?>
                <? endif ?>

                <? if (!Config::get()->EVASYS_FORCE_ONLINE) : ?>
                    <label>
                        <?= dgettext("evasys", "Modus der Evaluation") ?>
                        <? if ($editable) : ?>
                            <select name="data[mode]" onClick="jQuery('.evasys_paper').toggle(this.value === 'paper');" required>
                                <? if (!in_array($profile->getFinalMode(), array("online", "paper"))) : ?>
                                    <option value=""></option>
                                <? endif ?>
                                <option value="online"<?= $profile->getFinalMode() === "online" ? " selected" : "" ?>>
                                    <?= dgettext("evasys", "Online-Evaluation") ?>
                                </option>
                                <option value="paper"<?= $profile->getFinalMode() === "paper" ? " selected" : "" ?>>
                                    <?= dgettext("evasys", "Papier-Evaluation") ?>
                                </option>
                            </select>
                        <? else : ?>
                            <div>
                                <?= $profile->getFinalMode() === "online" ? dgettext("evasys", "Online-Evaluation") : dgettext("evasys", "Papierbasierte Evaluation") ?>
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

                <div style="margin-top: 10px;">
                    <table class="default nohover">
                        <thead>
                            <tr>
                                <th><?= dgettext("evasys", "Ausgewähler Fragebogen") ?></th>
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

                <? if ($profile->getPresetAttribute('enable_objection_to_publication') === 'yes') : ?>
                    <? if ($profile->mayObjectToPublication()) : ?>
                        <input type="hidden"
                               name="data[objection_to_publication]"
                               value="0">
                        <label>
                            <input type="checkbox"
                                   name="data[objection_to_publication]"
                                   onchange="if ($(this).is(':checked')) { $('#objection_reason').attr('required', ''); } else { $('#objection_reason').removeAttr('required'); }"
                                   value="1"<?= $profile['objection_to_publication'] ? ' checked' : '' ?>>
                            <?= dgettext("evasys", "Ich widerspreche der Weitergabe der Evaluationsergebnisse.") ?>
                        </label>

                        <label>
                            <?= dgettext("evasys", "Begründung für den Widerspruch (notwendig)") ?>
                            <textarea id="objection_reason"
                                      <?= $profile['objection_to_publication'] ? 'required' : '' ?>
                                      name="data[objection_reason]"><?= htmlReady($profile['objection_reason']) ?></textarea>
                        </label>
                    <? elseif($profile['objection_to_publication']) : ?>
                        <label>
                            <input type="checkbox" disabled="disabled" checked>
                            <?= dgettext("evasys", "Ich widerspreche der Weitergabe des Evaluationsergebnisse.") ?>
                        </label>

                        <label>
                            <?= dgettext("evasys", "Begründung für den Widerspruch (notwendig)") ?>
                            <textarea id="objection_reason"
                                      readonly><?= htmlReady($profile['objection_reason']) ?></textarea>
                        </label>
                    <? endif ?>
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
            <legend><?= dgettext("evasys", "Log") ?></legend>

            <? if ($profile['by_dozent'] && (EvasysPlugin::isRoot() || EvasysPlugin::isAdmin($profile['seminar_id']))) : ?>
                <?= MessageBox::info(sprintf(dgettext("evasys", "Evaluationsart: %s"), EvasysMatching::wording("freiwillige Evaluation"))) ?>
            <? endif ?>

            <table class="default no-hover">
                <thead>
                    <tr>
                        <th><?= dgettext('evasys', 'Aktion') ?></th>
                        <th><?= dgettext('evasys', 'Nutzer') ?></th>
                        <th><?= dgettext('evasys', 'Datum') ?></th>
                        <th class="actions"><?= dgettext('evasys', 'Info') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <? foreach ($logs as $log) : ?>
                    <tr>
                        <td>
                            <?
                            switch ($log->action['name']) {
                                case 'EVASYS_EVAL_APPLIED':
                                    echo dgettext('evasys', 'Evaluation beantragt');
                                    break;
                                case 'EVASYS_EVAL_UPDATE':
                                    echo dgettext('evasys', 'Daten verändert');
                                    break;
                                case 'EVASYS_EVAL_TRANSFER':
                                    echo dgettext('evasys', 'Übertragen nach EvaSys');
                                    break;
                                case 'EVASYS_EVAL_DELETE':
                                    echo dgettext('evasys', 'Gelöscht');
                                    break;
                                default:
                                    echo htmlReady($log->action['name']);
                            }
                            ?>
                        </td>
                        <td>
                            <? $user = User::find($log['user_id']) ?>
                            <? if ($user) : ?>
                            <a href="<?= URLHelper::getLink('dispatch.php/profile', ['username' => $user['username']]) ?>">
                                <?= Avatar::getAvatar($log['user_id'])->getImageTag(Avatar::SMALL) ?>
                                <?= htmlReady($user->getFullName()) ?>
                            </a>
                            <? else : ?>
                            <?= htmlReady($log['user_id']) ?>
                            <? endif ?>
                        </td>
                        <td><?= date('d.m.Y H:i', $log['mkdate']) ?></td>
                        <td class="actions">
                            <?
                            $message = '';
                            if ($log->action['name'] === 'EVASYS_EVAL_UPDATE') {
                                $change = json_decode($log['dbg_info'], true);
                                if ($change['new']['form_id'] !== $change['old']['form_id']) {
                                    $message .= dgettext('evasys', 'Fragebogen geändert. ');
                                }
                                if ($change['new']['begin'] !== $change['old']['begin']) {
                                    $message .= dgettext('evasys', 'Beginn geändert. ');
                                }
                                if ($change['new']['end'] !== $change['old']['end']) {
                                    $message .= dgettext('evasys', 'Befragungsendende geändert. ');
                                }
                                if ($change['new']['split'] !== $change['old']['split']) {
                                    $message .= dgettext('evasys', 'Teilevaluation geändert. ');
                                }
                                if ($change['new']['mode'] !== $change['old']['mode']) {
                                    $message .= dgettext('evasys', 'Modus der Evaluation geändert. ');
                                }
                                if ($change['new']['locked'] !== $change['old']['locked']) {
                                    $message .= dgettext('evasys', 'Sperrung geändert. ');
                                }
                                if ($change['new']['objection_to_publication'] !== $change['old']['objection_to_publication']) {
                                    $message .= dgettext('evasys', 'Widerspruch geändert. ');
                                }
                            }
                            if ($message) {
                                echo tooltipIcon($message);
                            }
                            ?>
                        </td>
                    </tr>
                    <? endforeach ?>
                </tbody>
            </table>
        </fieldset>
        <? endif ?>

        <? if ($editable) : ?>
            <script>
                jQuery(function () {
                    <? if ($editable && count($teachers) > 1) : ?>
                    jQuery(".evasys_teachers").sortable({
                        "axis": "y",
                        "handle": ".avatar, .anfasser",
                        "revert": 300
                    });
                    <? endif ?>
                });
            </script>
        <? endif ?>

        <? if ($editable) : ?>

            <div data-dialog-button>
                <? if (EvasysPlugin::isRoot() || EvasysPlugin::isAdmin($profile['seminar_id'])) : ?>
                    <? if ($profile['by_dozent']) : ?>
                        <?= \Studip\Button::create(dgettext("evasys", "In Pflichtevaluation umwandeln"), "unset_by_dozent", array('onclick' => "return window.confirm('".dgettext("evasys", "Wirklich in Pflichtevaluation umwandeln?")."');")) ?>
                    <? else : ?>
                        <?= \Studip\Button::create(sprintf(dgettext("evasys", "In %s umwandeln"), EvasysMatching::wording('freiwillige Evaluation')), "set_by_dozent", array('onclick' => "return window.confirm('".sprintf(dgettext("evasys", "Wirklich in %s umwandeln?"), EvasysMatching::wording('freiwillige Evaluation'))."'));")) ?>
                    <? endif ?>
                <? endif ?>

                <? if (EvasysPlugin::isRoot() && $profile['locked']) : ?>
                    <?= \Studip\Button::create(dgettext("evasys", "Entsperren"), 'unlock', array('onclick' => "return window.confirm('".dgettext("evasys", "Diese Veranstaltung wieder entsperren?")."');")) ?>
                <? endif ?>

                <?= \Studip\Button::create(dgettext("evasys", "Speichern")) ?>
            </div>
        <? else : ?>
            <? $info = $profile->getPresetAttribute("teacher_info") ?>
            <? if (trim($info)) : ?>
                <fieldset style="padding-top: 10px;">
                    <?= formatReady($info) ?>
                </fieldset>
            <? endif ?>
            <? if ($profile->getPresetAttribute('enable_objection_to_publication') === 'yes') : ?>
                <?= \Studip\Button::create(dgettext("evasys", "Speichern")) ?>
            <? endif ?>
        <? endif ?>
    </form>
<? endif ?>
