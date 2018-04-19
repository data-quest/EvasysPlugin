<form class="default bulkedit"
      action="<?= PluginEngine::getLink($plugin, array(), "profile/bulkedit") ?>"
      method="post">
    <? foreach ($course_ids as $course_id) : ?>
        <input type="hidden" name="c[<?= htmlReady($course_id) ?>]" value="1">
    <? endforeach ?>


    <table class="default nohover">
        <thead>
            <tr>
                <th width="50%"><?= _("Zu verändernde Eigenschaft auswählen") ?></th>
                <th width="50%"><?= _("Neuen Wert festlegen") ?></th>
            </tr>
        </thead>
        <tbody>
        <tr>
            <td>
                <label>
                    <input type="checkbox" name="change[]" value="applied" onChange="jQuery(this).closest('tr').toggleClass('active');">
                    <?= _("Veranstaltungen sollen evaluiert werden.") ?>
                </label>
            </td>
            <td>
                <select name="applied"
                        onChange="jQuery(this).closest('tr').addClass('active').find('td:first-child :checkbox').prop('checked', 'checked');">
                    <option value="">
                        <? if ($values['applied'] === "EVASYS_UNEINDEUTIGER_WERT") : ?>
                            <?= _("Unterschiedliche Werte") ?>
                        <? endif ?>
                    </option>
                    <option value="0"<?= !$values['applied'] ? " selected" : "" ?>>
                        <?= _("Nein") ?>
                    </option>
                    <option value="1"<?= $values['applied'] == 1 ? " selected" : "" ?>>
                        <?= _("Ja") ?>
                    </option>
                </select>
            </td>
        </tr>
        <tr>
            <td>
                <label>
                    <input type="checkbox" name="change[]" value="begin" onChange="jQuery(this).closest('tr').toggleClass('active');">
                    <?= _("Evaluationsbeginn") ?>
                </label>
            </td>
            <td>
                <input type="text"
                       name="begin"
                       value="<?= is_numeric($values['begin']) ? date("d.m.Y H:i", $values['begin']) : ($values['begin'] ? _("Unterschiedliche Werte") : "") ?>"
                       class="datepicker"
                       onChange="jQuery(this).closest('tr').addClass('active').find('td:first-child :checkbox').prop('checked', 'checked');">
            </td>
        </tr>
        <tr>
            <td>
                <label>
                    <input type="checkbox" name="change[]" value="end" onChange="jQuery(this).closest('tr').toggleClass('active');">
                    <?= _("Evaluationsende") ?>
                </label>
            </td>
            <td>
                <input type="text"
                       name="end"
                       value="<?= is_numeric($values['end']) ? date("d.m.Y H:i", $values['end']) : ($values['end'] ? _("Unterschiedliche Werte") : "") ?>"
                       class="datepicker"
                       onChange="jQuery(this).closest('tr').addClass('active').find('td:first-child :checkbox').prop('checked', 'checked');">
            </td>
        </tr>
        </tbody>

    </table>

    <script>
        jQuery(function () {
            jQuery("input.datepicker").datetimepicker();
        });
    </script>

    <div data-dialog-button>
        <?= \Studip\Button::create(_("Speichern"), "submit") ?>
        <?= \Studip\LinkButton::create(_("Abbrechen"), URLHelper::getURL("dispatch.php/admin/courses")) ?>
    </div>

</form>