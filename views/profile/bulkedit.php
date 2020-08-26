<form class="default bulkedit"
      action="<?= PluginEngine::getLink($plugin, Request::get("individual") ? array('individual' => Request::get("individual")) : array(), "profile/bulkedit") ?>"
      method="post">
    <? foreach ($ids as $seminar_and_semester_id) : ?>
        <input type="hidden" name="c[<?= htmlReady($seminar_and_semester_id) ?>]" value="1">
    <? endforeach ?>


    <table class="default nohover">
        <caption><?= sprintf(dgettext("evasys", "Bearbeiten von %s Veranstaltungen"), count($ids)) ?></caption>
        <thead>
            <tr>
                <th width="50%"><?= dgettext("evasys", "Zu verändernde Eigenschaft auswählen") ?></th>
                <th width="50%"><?= dgettext("evasys", "Neuen Wert festlegen") ?></th>
            </tr>
        </thead>
        <tbody>
        <tr>
            <td>
                <label>
                    <input type="checkbox" name="change[]" value="applied" onChange="jQuery(this).closest('tr').toggleClass('active');">
                    <?= dgettext("evasys", "Veranstaltungen sollen evaluiert werden.") ?>
                </label>
            </td>
            <td>
                <select name="applied"
                        onChange="jQuery(this).closest('tr').addClass('active').find('td:first-child :checkbox').prop('checked', 'checked');">
                    <option value="">
                        <? if ($values['applied'] === "EVASYS_UNEINDEUTIGER_WERT") : ?>
                            <?= dgettext("evasys", "Unterschiedliche Werte") ?>
                        <? endif ?>
                    </option>
                    <option value="0"<?= !$values['applied'] ? " selected" : "" ?>>
                        <?= dgettext("evasys", "Nein") ?>
                    </option>
                    <option value="1"<?= $values['applied'] == 1 ? " selected" : "" ?>>
                        <?= dgettext("evasys", "Ja") ?>
                    </option>
                </select>
            </td>
        </tr>
        <? if (Config::get()->EVASYS_ENABLE_SPLITTING_COURSES) : ?>
        <tr>
            <td>
                <label>
                    <input type="checkbox" name="change[]" value="split" onChange="jQuery(this).closest('tr').toggleClass('active');">
                    <?= dgettext("evasys", "Lehrende einzeln evaluieren?") ?>
                </label>
            </td>
            <td>
                <select name="split"
                        onChange="jQuery(this).closest('tr').addClass('active').find('td:first-child :checkbox').prop('checked', 'checked');">
                    <option value="">
                        <? if ($values['split'] === "EVASYS_UNEINDEUTIGER_WERT") : ?>
                            <?= dgettext("evasys", "Unterschiedliche Werte") ?>
                        <? endif ?>
                    </option>
                    <option value="0"<?= !$values['split'] ? " selected" : "" ?>>
                        <?= dgettext("evasys", "Nein") ?>
                    </option>
                    <option value="1"<?= $values['split'] == 1 ? " selected" : "" ?>>
                        <?= dgettext("evasys", "Ja") ?>
                    </option>
                </select>
            </td>
        </tr>
        <? endif ?>
        <tr>
            <td>
                <label>
                    <input type="checkbox" name="change[]" value="begin" onChange="jQuery(this).closest('tr').toggleClass('active');">
                    <?= dgettext("evasys", "Evaluationsbeginn") ?>
                </label>
            </td>
            <td>
                <input type="text"
                       name="begin"
                       value="<?= is_numeric($values['begin']) ? date("d.m.Y H:i", $values['begin']) : ($values['begin'] ? dgettext("evasys", "Unterschiedliche Werte") : "") ?>"
                       class="datepicker"
                       onChange="jQuery(this).closest('tr').addClass('active').find('td:first-child :checkbox').prop('checked', 'checked');">
            </td>
        </tr>
        <tr>
            <td>
                <label>
                    <input type="checkbox" name="change[]" value="end" onChange="jQuery(this).closest('tr').toggleClass('active');">
                    <?= dgettext("evasys", "Evaluationsende") ?>
                </label>
            </td>
            <td>
                <input type="text"
                       name="end"
                       value="<?= is_numeric($values['end']) ? date("d.m.Y H:i", $values['end']) : ($values['end'] ? dgettext("evasys", "Unterschiedliche Werte") : "") ?>"
                       class="datepicker"
                       onChange="jQuery(this).closest('tr').addClass('active').find('td:first-child :checkbox').prop('checked', 'checked');">
            </td>
        </tr>
        <tr>
            <td>
                <label>
                    <input type="checkbox" name="change[]" value="form_id" onChange="jQuery(this).closest('tr').toggleClass('active');">
                    <?= dgettext("evasys", "Fragebogen") ?>
                </label>
            </td>
            <td>
                <? if (!empty($available_form_ids)) : ?>
                <select name="form_id"
                        onChange="jQuery(this).closest('tr').addClass('active').find('td:first-child :checkbox').prop('checked', 'checked');">
                    <option value="">
                        <? if ($values['form_id'] === "EVASYS_UNEINDEUTIGER_WERT") : ?>
                            <?= dgettext("evasys", "Unterschiedliche Werte") ?>
                        <? endif ?>
                    </option>
                    <?
                    $forms = EvasysForm::findMany($all_form_ids);
                    usort($forms, function ($a, $b) {
                        return strcasecmp($a['name'], $b['name']);
                    });
                    ?>
                    <? foreach ($forms as $form) : ?>
                        <option value="<?= htmlReady($form->getId()) ?>"<?= $values['form_id'] == $form->getId() ? " selected" : "" ?><?= !in_array($form->getId(), $available_form_ids) ? " disabled title='".dgettext("evasys", "Fragebogen darf nicht allen ausgewählten Veranstaltungstypen zugewiesen werden.")."'" : "" ?>>
                            <?= htmlReady($form['name'].": ".$form['description']) ?>
                        </option>
                    <? endforeach ?>
                </select>
                <? else : ?>
                    <?= dgettext("evasys", "Es gibt keinen Fragebogen, der bei allen ausgewählten Veranstaltungen erlaubt ist.") ?>
                <? endif ?>
            </td>
        </tr>
        <tr>
            <td>
                <label>
                    <input type="checkbox" name="change[]" value="mode" onChange="jQuery(this).closest('tr').toggleClass('active');">
                    <?= dgettext("evasys", "Art der Evaluation") ?>
                </label>
            </td>
            <td>
                <select name="mode"
                        onChange="jQuery(this).closest('tr').addClass('active').find('td:first-child :checkbox').prop('checked', 'checked');">
                    <option value="">
                        <? if ($values['mode'] === "EVASYS_UNEINDEUTIGER_WERT") : ?>
                            <?= dgettext("evasys", "Unterschiedliche Werte") ?>
                        <? endif ?>
                    </option>
                    <option value="online"<?= $values['mode'] == "online" ? " selected" : "" ?>>
                        <?= dgettext("evasys", "Online-Evaluation") ?>
                    </option>
                    <option value="paper"<?= $values['mode'] == "paper" ? " selected" : "" ?>>
                        <?= dgettext("evasys", "Papierbasierte Evaluation") ?>
                    </option>
                </select>
            </td>
        </tr>

        <? foreach (EvasysAdditionalField::findBySQL("1=1 ORDER BY position ASC, name ASC") as $field) : ?>
            <tr>
                <td>
                    <label>
                        <input type="checkbox" name="change[]" value="<?= htmlReady($field->getId()) ?>" onChange="jQuery(this).closest('tr').toggleClass('active');">
                        <?= htmlReady($field['name']) ?>
                    </label>
                </td>
                <td>
                    <? if ($field['type'] === "TEXTAREA") : ?>
                        <textarea
                               name="<?= htmlReady($field->getId()) ?>"
                               onChange="jQuery(this).closest('tr').addClass('active').find('td:first-child :checkbox').prop('checked', 'checked');"><?= htmlReady($values[$field->getId()]) ?></textarea>
                    <? else : ?>
                        <input
                                name="<?= htmlReady($field->getId()) ?>"
                                onChange="jQuery(this).closest('tr').addClass('active').find('td:first-child :checkbox').prop('checked', 'checked');"
                                value="<?= htmlReady($values[$field->getId()]) ?>">
                    <? endif ?>
                </td>
            </tr>
        <? endforeach ?>

        <? if ($values['by_dozent'] == 1) : ?>
        <tr>
            <td>
                <label>
                    <input type="checkbox" name="change[]" value="by_dozent" onChange="jQuery(this).closest('tr').toggleClass('active');">
                    <?= ucfirst(EvasysMatching::wording("freiwillige Evaluation")) ?>
                </label>
            </td>
            <td>
                <input type="checkbox"
                       name="by_dozent"
                       value="1"
                       onChange="jQuery(this).closest('tr').addClass('active').find('td:first-child :checkbox').prop('checked', 'checked');">
            </td>
        </tr>
        <? endif ?>

        </tbody>

    </table>

    <script>
        jQuery(function () {
            jQuery("input.datepicker").datetimepicker();
        });
    </script>

    <div data-dialog-button>
        <?= \Studip\Button::create(dgettext("evasys", "Speichern"), "submit") ?>
        <?= \Studip\LinkButton::create(dgettext("evasys", "Abbrechen"), URLHelper::getURL("dispatch.php/admin/courses")) ?>
    </div>

</form>
