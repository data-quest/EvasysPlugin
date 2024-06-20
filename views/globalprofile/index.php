<? if ($profile) : ?>

<form action="<?= PluginEngine::getLink($plugin, array(), $con."/edit") ?>"
      method="post"
      class="default evasys_presets">

    <div style="text-align: center;">
        <?= \Studip\Button::create(dgettext("evasys", "Speichern")) ?>
    </div>

    <fieldset>
        <legend>
            <? if ($this->controller->profile_type !== "institute") : ?>
                <?= dgettext("evasys", "Standarddaten der Evaluationen") ?>
            <? else : ?>
                <?= sprintf(dgettext("evasys", "Standarddaten der Evaluationen der Einrichtung %s"), htmlReady($profile->institute->name)) ?>
            <? endif ?>
        </legend>
        <label>
            <?= dgettext("evasys","Beginn") ?>
            <input type="text"
                   name="data[begin]"
                   value="<?= $profile['begin'] ? date("d.m.Y H:i", $profile['begin']) : "" ?>"
                   data-datetime-picker
                   id="evasys_eval_begin">
        </label>
        <? if ($this->controller->profile_type === "institute") : ?>
            <? $default_value = $profile->getParentsDefaultValue("begin") ?>
            <span title="<?= dgettext("evasys", "Standardwert, wenn nichts eingetragen ist.") ?>"
                  class="default_value">(<?= $default_value ? date("d.m.Y H:i", $default_value) : dgettext("evasys", "Kein Standardwert") ?>)</span>
        <? endif ?>

        <label>
            <?= dgettext("evasys","Ende") ?>
            <input type="text"
                   name="data[end]"
                   value="<?= $profile['end'] ? date("d.m.Y H:i", $profile['end']) : "" ?>"
                   data-datetime-picker='{">=":"#evasys_eval_begin"}'>
        </label>
        <? if ($this->controller->profile_type === "institute") : ?>
            <? $default_value = $profile->getParentsDefaultValue("end") ?>
            <span title="<?= dgettext("evasys", "Standardwert, wenn nichts eingetragen ist.") ?>"
                  class="default_value">(<?= $default_value ? date("d.m.Y H:i", $default_value) : dgettext("evasys", "Kein Standardwert") ?>)</span>
        <? endif ?>

        <? if (is_a($profile, "EvasysGlobalProfile")) : ?>
            <label>
                <?= dgettext("evasys","Beginn Bearbeitungszeitraum der Admins") ?>
                <input type="text"
                       name="data[adminedit_begin]"
                       value="<?= $profile['adminedit_begin'] ? date("d.m.Y H:i", $profile['adminedit_begin']) : "" ?>"
                       data-datetime-picker
                       id="evasys_admin_begin">
            </label>

            <label>
                <?= dgettext("evasys","Ende Bearbeitungszeitraum der Admins") ?>
                <input type="text"
                       name="data[adminedit_end]"
                       value="<?= $profile['adminedit_end'] ? date("d.m.Y H:i", $profile['adminedit_end']) : "" ?>"
                       data-datetime-picker='{">=":"#evasys_admin_begin"}'>
            </label>
        <? endif ?>

        <label>
            <?= dgettext("evasys", "Standardfragebogen (nur aktive werden angezeigt)") ?>
            <select name="data[form_id]" class="select2">
                <option value=""></option>
                <? foreach (EvasysForm::findBySQL("active = '1' ORDER BY name ASC") as $form) : ?>
                    <option value="<?= htmlReady($form->getId()) ?>"<?= $form->getId() == $profile['form_id'] ? " selected" : "" ?> title="<?= htmlReady($form['description']) ?>">
                        <?= htmlReady($form['name']) ?>:
                        <?= htmlReady($form['description']) ?>
                    </option>
                <? endforeach ?>
            </select>
        </label>
        <? if ($this->controller->profile_type === "institute") : ?>
            <? $default_value = $profile->getParentsDefaultValue("form_id") ?>
            <span title="<?= dgettext("evasys", "Standardwert, wenn nichts eingetragen ist.") ?>"
                  class="default_value">(<?= $default_value ? htmlReady(EvasysForm::find($default_value)->name) : dgettext("evasys", "Kein Standardwert") ?>)</span>
        <? endif ?>

        <label>
            <?= dgettext("evasys", "Modus der Evaluation") ?>
            <select name="data[mode]">
                <option value=""></option>
                <option value="paper"<?= $profile['mode'] === "paper" ? " selected" : "" ?>>
                    <?= dgettext("evasys", "Papier-Evaluation") ?>
                </option>
                <option value="online"<?= $profile['mode'] === "online" ? " selected" : "" ?>>
                    <?= dgettext("evasys", "Online-Evaluation") ?>
                </option>
            </select>
        </label>
        <? if ($this->controller->profile_type === "institute") : ?>
            <? $default_value = $profile->getParentsDefaultValue("mode") ?>
            <span title="<?= dgettext("evasys", "Standardwert, wenn nichts eingetragen ist.") ?>"
                  class="default_value">(<?= $default_value ? htmlReady($default_value) : dgettext("evasys", "Kein Standardwert") ?>)</span>
        <? endif ?>

        <label>
            <?= dgettext("evasys","Papierverfahren (nur bei Papierevaluationen)") ?>
            <select name="data[paper_mode]">
                <? if (is_a($profile, "EvasysInstituteProfile")) : ?>
                <option value=""></option>
                <? endif ?>
                <option value="s"<?= $profile['paper_mode'] === "s" ? " selected" : "" ?>>
                    <?= dgettext("evasys","Selbstdruckverfahren") ?>
                </option>
                <option value="d"<?= $profile['paper_mode'] === "d" ? " selected" : "" ?>>
                    <?= dgettext("evasys","Deckblattverfahren") ?>
                </option>
            </select>
        </label>
        <? if ($this->controller->profile_type === "institute") : ?>
            <? $default_value = $profile->getParentsDefaultValue("paper_mode") ?>
            <span title="<?= dgettext("evasys","Standardwert, wenn nichts eingetragen ist.") ?>"
                  class="default_value">(<?= $default_value === "d" ? dgettext("evasys","Deckblattverfahren") : dgettext("evasys","Selbstdruckverfahren") ?>)</span>
        <? endif ?>

        <? if (is_a($profile, "EvasysInstituteProfile")) : ?>
        <label>
            <?= dgettext("evasys", "Weitere Emails, an die die Ergebnisse gesendet werden sollen (mit Leerzeichen getrennt)") ?>
            <input type="text" name="data[results_email]" value="<?= htmlReady($profile['results_email']) ?>">
        </label>
        <? endif ?>

        <label>
            <?= dgettext("evasys", "Informationen für Lehrende zu den Evaluationsdaten") ?>
            <textarea name="data[teacher_info]"><?= htmlReady($profile['teacher_info']) ?></textarea>
        </label>
        <? if ($this->controller->profile_type === "institute") : ?>
            <? $default_value = $profile->getParentsDefaultValue("teacher_info") ?>
            <span title="<?= dgettext("evasys", "Standardwert, wenn nichts eingetragen ist.") ?>"
                  class="default_value">(<?= $default_value ? htmlReady($default_value) : dgettext("evasys", "Kein Standardwert") ?>)</span>
        <? endif ?>

        <label>
            <?= dgettext("evasys", "Infotext für Studierende über der Evaluation") ?>
            <textarea name="data[student_infotext]"><?= htmlReady($profile['student_infotext']) ?></textarea>
        </label>
        <? if ($this->controller->profile_type === "institute") : ?>
            <? $default_value = $profile->getParentsDefaultValue("student_infotext") ?>
            <span title="<?= dgettext("evasys", "Standardwert, wenn nichts eingetragen ist.") ?>"
                  class="default_value">(<?= $default_value ? htmlReady($default_value) : dgettext("evasys", "Kein Standardwert") ?>)</span>
        <? endif ?>

        <? $strings = array('yes' => dgettext("evasys", "Erst nach Ablauf der Evaluation"), "no" => dgettext("evasys", "Sobald der Mindestrücklauf erreicht ist.")) ?>
        <label>
            <?= dgettext("evasys", "PDF-Berichte bereitstellen") ?>
            <select name="data[reports_after_evaluation]">
                <? if ($this->controller->profile_type === "institute") : ?>
                    <option value=""></option>
                <? endif ?>
                <option value="no"<?= $profile['reports_after_evaluation'] === "no" ? " selected" : "" ?>>
                    <?= $strings['no'] ?>
                </option>
                <option value="yes"<?= $profile['reports_after_evaluation'] === "yes" ? " selected" : "" ?>>
                    <?= $strings['yes'] ?>
                </option>
            </select>
        </label>
        <? if ($this->controller->profile_type === "institute") : ?>
            <? $default_value = $profile->getParentsDefaultValue("reports_after_evaluation") ?>
            <span title="<?= dgettext("evasys", "Standardwert, wenn nichts eingetragen ist.") ?>"
                  class="default_value">(<?= $default_value ? $strings[$default_value] : dgettext("evasys", "Kein Standardwert") ?>)</span>
        <? endif ?>

        <label>
            <?= dgettext("evasys","Berichte erst x Tage nach Ablauf der Evaluation anzeigen") ?>
            <input type="number"
                   name="data[extended_report_offset]"
                   value="<?= htmlReady($profile['extended_report_offset'] !== null ? $profile['extended_report_offset'] : $profile->getParentsDefaultValue("extended_report_offset")) ?>"
                   >
        </label>
        <? if ($this->controller->profile_type === "institute") : ?>
            <? $default_value = $profile->getParentsDefaultValue("extended_report_offset") ?>
            <span title="<?= dgettext("evasys","Standardwert, wenn nichts eingetragen ist.") ?>"
                  class="default_value">(<?= dgettext("evasys","Standardwert").": " . $default_value ?: dgettext("evasys","Kein Standardwert") ?>)</span>
        <? endif ?>

        <label>
            <?= dgettext("evasys","Berichte per Mail von EvaSys verschicken") ?>
            <select name="data[send_report]">
                <? if ($this->controller->profile_type === "institute") : ?>
                    <option value=""></option>
                <? endif ?>
                <option value="yes"<?= $profile['send_report'] == "yes" ? " selected" : "" ?>><?= dgettext("evasys","Ja") ?></option>
                <option value="no"<?= $profile['send_report'] == "no" ? " selected" : "" ?>><?= dgettext("evasys","Nein") ?></option>
            </select>
        </label>
        <? if ($this->controller->profile_type === "institute") : ?>
            <? $default_value = $profile->getParentsDefaultValue("send_report") ?>
            <span title="<?= dgettext("evasys","Standardwert, wenn nichts eingetragen ist.") ?>"
                  class="default_value">(<?= dgettext("evasys","Standardwert").": " . $default_value ?: dgettext("evasys","Kein Standardwert") ?>)</span>
        <? endif ?>

        <label>
            <?= dgettext("evasys","Berichte (und Ende) der Befragung um x Sekunden verzögern") ?>
            <input type="number"
                   min="0"
                   name="data[send_report_delay]"
                   value="<?= htmlReady($profile['send_report_delay']) ?>">
        </label>
        <? if ($this->controller->profile_type === "institute") : ?>
            <? $default_value = $profile->getParentsDefaultValue("send_report_delay") ?>
            <span title="<?= dgettext("evasys","Standardwert, wenn nichts eingetragen ist.") ?>"
                  class="default_value">(<?= dgettext("evasys","Standardwert").": " . $default_value ?: dgettext("evasys","Kein Standardwert") ?>)</span>
        <? endif ?>

        <label>
            <?= dgettext("evasys","Profile sperren nach Transfer für Rolle") ?>
            <select name="data[lockaftertransferforrole]">
                <option value=""></option>
                <option value="admin"<?= $profile['lockaftertransferforrole'] === 'admin' ? " selected" : "" ?>><?= dgettext("evasys","Admin") ?></option>
                <option value="dozent"<?= $profile['lockaftertransferforrole'] === 'dozent' ? " selected" : "" ?>><?= dgettext("evasys","Lehrende") ?></option>
            </select>
        </label>
        <? if ($this->controller->profile_type === "institute") : ?>
            <? $default_value = $profile->getParentsDefaultValue("lockaftertransferforrole") ?>
            <span title="<?= dgettext("evasys","Standardwert, wenn nichts eingetragen ist.") ?>"
                  class="default_value">(<?= dgettext("evasys","Standardwert").": " . $default_value ?: dgettext("evasys","Kein Standardwert") ?>)</span>
        <? endif ?>

        <label>
            <?= dgettext("evasys","Widerspruch erlauben bzw. auf Wunsch Veranstaltung in gesonderten Teilbereich verschieben") ?>
            <select name="data[enable_objection_to_publication]">
                <? if ($this->controller->profile_type === "institute") : ?>
                <option value=""></option>
                <? endif ?>
                <option value="yes"<?= $profile['enable_objection_to_publication'] === 'yes' ? " selected" : "" ?>><?= dgettext("evasys","Ja") ?></option>
                <option value="no"<?= $profile['enable_objection_to_publication'] === 'no' ? " selected" : "" ?>><?= dgettext("evasys","Nein") ?></option>
            </select>
        </label>
        <? if ($this->controller->profile_type === "institute") : ?>
            <? $default_value = $profile->getParentsDefaultValue("enable_objection_to_publication") ?>
            <span title="<?= dgettext("evasys","Standardwert, wenn nichts eingetragen ist.") ?>"
                  class="default_value">(<?= dgettext("evasys","Standardwert").": " . $default_value ?: dgettext("evasys","Kein Standardwert") ?>)</span>
        <? endif ?>

        <label>
            <?= dgettext("evasys","Bei Widerspruch Veranstaltungen in folgenden Teilbereich verschieben") ?>
            <input type="text"
                   name="data[objection_teilbereich]"
                   value="<?= htmlReady($profile['objection_teilbereich']) ?>">
        </label>
        <? if ($this->controller->profile_type === "institute") : ?>
            <? $default_value = $profile->getParentsDefaultValue("objection_teilbereich") ?>
            <span title="<?= dgettext("evasys","Standardwert, wenn nichts eingetragen ist.") ?>"
                  class="default_value">(<?= dgettext("evasys","Standardwert").": " . $default_value ?: dgettext("evasys","Kein Standardwert") ?>)</span>
        <? endif ?>

    </fieldset>

    <fieldset class="forms_for_types">
        <legend>
            <?= dgettext("evasys", "Standardfragebögen nach Veranstaltungstypen") ?>
        </legend>

        <table class="default semtype_matching">
            <tbody>
                <? foreach (SemType::getTypes() as $sem_type) : ?>
                <tr>
                    <td>
                        <?= htmlReady($GLOBALS['SEM_CLASS'][$sem_type['class']]['name']) ?>: <?= htmlReady($sem_type['name']) ?>

                        <div class="copypaste">
                            <a href="#" class="copy" title="<?= dgettext("evasys", "Werte kopieren") ?>">
                                <?= Icon::create("topic+export", "clickable")->asImg(20) ?>
                            </a>
                            <a href="#" class="paste" title="<?= dgettext("evasys", "Werte hier einfügen") ?>">
                                <?= Icon::create("topic+move_down", "status-green")->asImg(20) ?>
                            </a>
                            <a href="#" class="from" title="<?= dgettext("evasys", "Doch nicht kopieren") ?>">
                                <?= Icon::create("topic+decline", "status-yellow")->asImg(20) ?>
                            </a>
                        </div>
                    </td>
                    <td>
                        <label>
                            <div>
                                <?= dgettext("evasys", "Standardfragebogen") ?>
                            </div>
                            <select name="forms_by_type[<?= htmlReady($sem_type['id']) ?>]" class="select2 standard">
                                <option value=""></option>
                                <? foreach (EvasysForm::findBySQL("active = '1' ORDER BY name ASC") as $form) : ?>
                                    <option value="<?= htmlReady($form->getId()) ?>"<?= isset($forms_by_type[$sem_type['id']][0]) && $forms_by_type[$sem_type['id']][0] == $form->getId() ? " selected" : "" ?>  title="<?= htmlReady($form['description']) ?>">
                                        <?= htmlReady($form['name']) ?>:
                                        <?= htmlReady($form['description']) ?>
                                    </option>
                                <? endforeach ?>
                            </select>
                        </label>

                        <label>
                            <div>
                                <?= dgettext("evasys", "Verfügbar") ?>
                            </div>
                            <select name="available_forms_by_type[<?= htmlReady($sem_type['id']) ?>][]" multiple class="select2 available">
                                <option value=""></option>
                                <? foreach (EvasysForm::findBySQL("active = '1' ORDER BY name ASC") as $form) : ?>
                                    <option value="<?= htmlReady($form->getId()) ?>"<?= isset($available_forms_by_type[$sem_type['id']]) && in_array($form->getId(), (array) $available_forms_by_type[$sem_type['id']]) ? " selected" : "" ?>  title="<?= htmlReady($form['name'].": ".$form['description']) ?>">
                                        <?= htmlReady($form['name']) ?>
                                    </option>
                                <? endforeach ?>
                            </select>
                        </label>

                        <? if ($this->controller->profile_type === "institute") : ?>
                            <? $semtypeforms = $profile->getParentsAvailableForms($sem_type['id']) ?>
                            <span title="<?= dgettext("evasys", "Standardwert, wenn nichts eingetragen ist.") ?>"
                                  class="default_value">(<? if (!empty($semtypeforms)) {
                                    foreach ($semtypeforms as $key => $semtypeform) {
                                        if ($key > 0) {
                                            echo ", ";
                                            echo htmlReady($semtypeform->form->name);
                                        } else {
                                            echo "<u>".htmlReady($semtypeform->form->name)."</u>";
                                        }
                                    }
                                } else {
                                    echo dgettext("evasys", "Keine Standardwerte");
                                } ?>)
                            </span>
                        <? endif ?>
                    </td>
                    <td>
                        <? if (!empty($available_forms_by_type[$sem_type['id']])) : ?>
                        <a href="<?= PluginEngine::getLink($plugin, array(), "forms/sort/".$this->controller->profile_type."/".$sem_type['id']."/".$profile->getId()) ?>"
                           title="<?= dgettext("evasys", "Sortierung bearbeiten") ?>"
                           data-dialog>
                            <?= Icon::create("settings", "clickable")->asImg(20, array('class' => "text-bottom")) ?>
                        </a>
                        <? endif ?>
                    </td>
                </tr>
                <? endforeach ?>
            </tbody>
        </table>
    </fieldset>

    <fieldset>
        <legend><?= ucfirst(EvasysMatching::wording("freiwillige Evaluationen")) ?></legend>

        <label>
            <?= dgettext("evasys","Beginn der Antragsfrist") ?>
            <input type="text"
                   name="data[antrag_begin]"
                   value="<?= $profile['antrag_begin'] ? date("d.m.Y H:i", $profile['antrag_begin']) : "" ?>"
                   data-datetime-picker
                   id="evasys_free_begin">
        </label>
        <? if ($this->controller->profile_type === "institute") : ?>
            <? $default_value = $profile->getParentsDefaultValue("antrag_begin") ?>
            <span title="<?= dgettext("evasys", "Standardwert, wenn nichts eingetragen ist.") ?>"
                  class="default_value">(<?= $default_value ? date("d.m.Y H:i", $default_value) : dgettext("evasys", "Kein Standardwert") ?>)</span>
        <? endif ?>

        <label>
            <?= dgettext("evasys","Ende der Antragsfrist") ?>
            <input type="text"
                   name="data[antrag_end]"
                   value="<?= $profile['antrag_end'] ? date("d.m.Y H:i", $profile['antrag_end']) : "" ?>"
                   data-datetime-picker='{">=":"#evasys_free_begin"}'>
        </label>
        <? if ($this->controller->profile_type === "institute") : ?>
            <? $default_value = $profile->getParentsDefaultValue("antrag_end") ?>
            <span title="<?= dgettext("evasys", "Standardwert, wenn nichts eingetragen ist.") ?>"
                  class="default_value">(<?= $default_value ? date("d.m.Y H:i", $default_value) : dgettext("evasys", "Kein Standardwert") ?>)</span>
        <? endif ?>

        <label>
            <?= dgettext("evasys", "Informationstext") ?>
            <textarea name="data[antrag_info]"><?= htmlReady($profile['antrag_info']) ?></textarea>
        </label>
        <? if ($this->controller->profile_type === "institute") : ?>
            <? $default_value = $profile->getParentsDefaultValue("antrag_info") ?>
            <span title="<?= dgettext("evasys", "Standardwert, wenn nichts eingetragen ist.") ?>"
                  class="default_value">(<?= $default_value ? nl2br(htmlReady($default_value)) : dgettext("evasys", "Kein Standardwert") ?>)</span>
        <? endif ?>

    </fieldset>

    <? if (CronjobScheduler::getInstance()) : ?>
    <fieldset>
        <legend><?= dgettext("evasys","Mails / Benachrichtigungen") ?></legend>

        <h2>
            <?= Icon::create('mail', Icon::ROLE_INFO)->asImg(20, ['class' => "text-bottom"])  ?>
            <?= dgettext('evasys', 'Nachricht zum Beantragen an Admins und andere Lehrende')?>
        </h2>
        <?= dgettext('evasys', 'Diese Nachricht wird beim Beantragen der Lehrenden verschickt an die anderen Lehrende und die Eval-Admins')?>
        <label>
            <?= dgettext('evasys', 'Betreff')?>
            <?= I18N::input('mail_apply_subject', $profile['mail_apply_subject']) ?>
        </label>
        <label>
            <?= dgettext('evasys', 'Betreff')?>
            <?= I18N::textarea('mail_apply_body', $profile['mail_apply_body']) ?>
        </label>


        <h2>
            <?= Icon::create('mail', Icon::ROLE_INFO)->asImg(20, ['class' => "text-bottom"])  ?>
            <?= dgettext('evasys', 'Nachricht an Lehrende')?>
        </h2>
        <?= sprintf(dgettext('evasys', 'Diese Nachricht wird beim Verändern von %s der Befragungsdaten an die anderen Lehrenden verschickt.'), EvasysMatching::wording('freiwillige Evaluationen')) ?>
        <label>
            <?= dgettext('evasys', 'Betreff')?>
            <?= I18N::input('mail_changed_subject', $profile['mail_changed_subject']) ?>
        </label>
        <label>
            <?= dgettext('evasys', 'Betreff')?>
            <?= I18N::textarea('mail_changed_body', $profile['mail_changed_body']) ?>
        </label>


        <h2>
            <?= Icon::create('mail', Icon::ROLE_INFO)->asImg(20, ['class' => "text-bottom"])  ?>
            <?= dgettext('evasys', 'Nachricht an Lehrende 24 Stunden vor Beginn')?>
        </h2>
        <?= dgettext('evasys', 'Diese Nachricht wird 24 Stunden vor Beginn des Evaluationszeitraumes an die Lehrenden verschickt.')?>
        <label>
            <?= dgettext('evasys', 'Betreff')?>
            <?= I18N::input('mail_begin_subject', $profile['mail_begin_subject']) ?>
        </label>
        <label>
            <?= dgettext('evasys', 'Betreff')?>
            <?= I18N::textarea('mail_begin_body', $profile['mail_begin_body']) ?>
        </label>



        <h2>
            <?= Icon::create('mail', Icon::ROLE_INFO)->asImg(20, ['class' => "text-bottom"])  ?>
            <?= dgettext('evasys', 'Nachricht an Studierende')?>
        </h2>
        <?= dgettext('evasys', 'Diese Nachricht wird zu Beginn des Evaluationszeitraumes an die Studierenden verschickt.')?>
        <label>
            <?= dgettext('evasys', 'Betreff')?>
            <?= I18N::input('mail_reminder_subject', $profile['mail_reminder_subject']) ?>
        </label>
        <label>
            <?= dgettext('evasys', 'Betreff')?>
            <?= I18N::textarea('mail_reminder_body', $profile['mail_reminder_body']) ?>
        </label>


    </fieldset>
    <? endif ?>

    <script>
        jQuery(function () {
            jQuery(".forms_for_types .select2").select2({
                "closeOnSelect": false,
                "width": 'resolve'
            });
        });
    </script>
    <style>
        .ui-widget, #layout_wrapper #barBottomContainer {
            z-index: 100000 !important;
        }
    </style>

    <div style="text-align: center;">
        <?= \Studip\Button::create(dgettext("evasys", "Speichern")) ?>
        <? if ($profile->semester['beginn'] > Semester::findCurrent()->beginn) : ?>
            <?= \Studip\Button::create(dgettext("evasys", "Löschen"), "delete", array('onClick' => "return window.confirm('".dgettext("evasys", "Sollen die Einstellungen des gesamten Semesters wirklich gelöscht werden?")."');")) ?>
        <? endif ?>
    </div>

</form>

<? else : ?>
    <?= MessageBox::info(sprintf(dgettext("evasys", "Wählen Sie erst eine %s aus."), EvasysMatching::wording("Einrichtung"))) ?>
<? endif ?>

<?
if ($this->controller->profile_type === "institute") {
    $list = new SelectWidget(
        dgettext("evasys", 'Einrichtung'),
        PluginEngine::getURL($plugin, array(), "instituteprofile/change_institute"),
        'institute'
    );
    $insts = Institute::getMyInstitutes($GLOBALS['user']->id);
    $list->class = 'institute-list';
    if ($GLOBALS['perm']->have_perm('root') || (count($insts) > 1)) {
        $list->addElement(new SelectElement(
            StudipVersion::newerThan('5.3.99') ? '' : 'all',
            $GLOBALS['perm']->have_perm('root') ? dgettext("evasys", 'Alle') : dgettext("evasys", 'Alle meine Einrichtungen'),
            !$GLOBALS['user']->cfg->MY_INSTITUTES_DEFAULT || $GLOBALS['user']->cfg->MY_INSTITUTES_DEFAULT === 'all'),
            'select-all'
        );
    }

    foreach ($insts as $institut) {
        $list->addElement(
            new SelectElement(
                $institut['Institut_id'],
                (!$institut['is_fak'] ? ' ' : '') . $institut['Name'],
                $GLOBALS['user']->cfg->MY_INSTITUTES_DEFAULT === $institut['Institut_id']
            ),
            'select-' . $institut['Institut_id']
        );
    }
    Sidebar::Get()->addWidget($list, 'filter_institute');
}

$list = new SelectWidget(
    dgettext("evasys", 'Semester'),
    PluginEngine::getURL($plugin, array(), $this->controller->profile_type."profile/index"),
    'semester_id'
);

foreach (EvasysGlobalProfile::findBySQL("1=1 ORDER BY begin DESC ") as $profile) {
    $list->addElement(
        new SelectElement(
            $profile->getId(),
            $profile->semester ? $profile->semester['name'] : $profile->getId(),
            $profile->getId() === $semester_id
        ),
        'select-'.$profile->getId()
    );
}
Sidebar::Get()->addWidget($list, 'set_semester_id');

if ($this->controller->profile_type === "global" && $addSemester) {
    $actions = new ActionsWidget();
    $actions->addLink(
        dgettext("evasys", "Neues Semester anlegen"),
        PluginEngine::getURL($plugin, array(), "globalprofile/add"),
        Icon::create("add", "clickable"),
        array('data-dialog' => 1)
    );
    Sidebar::Get()->addWidget($actions);
}
