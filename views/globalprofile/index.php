<? if ($profile) : ?>

<form action="<?= PluginEngine::getLink($plugin, array(), $con."/edit") ?>"
      method="post"
      class="default">

    <fieldset>
        <legend>
            <?= _("Standarddaten der Evaluationen") ?>
        </legend>
        <label>
            <?= _("Beginn") ?>
            <input type="text" name="data[begin]" value="<?= $profile['begin'] ? date("d.m.Y H:i", $profile['begin']) : "" ?>" class="datepicker">
        </label>

        <label>
            <?= _("Ende") ?>
            <input type="text" name="data[end]" value="<?= $profile['end'] ? date("d.m.Y H:i", $profile['end']) : "" ?>" class="datepicker">
        </label>

        <label>
            <?= _("Standardfragebogen (nur aktive werden angezeigt)") ?>
            <select name="data[form_id]" class="select2">
                <option value=""></option>
                <? foreach (EvasysForm::findBySQL("active = '1' ORDER BY name ASC") as $form) : ?>
                    <option value="<?= htmlReady($form->getId()) ?>"<?= $form->getId() == $profile['form_id'] ? " selected" : "" ?>>
                        <?= htmlReady($form['name']) ?>
                    </option>
                <? endforeach ?>
            </select>
        </label>

        <label>
            <?= _("Art der Evaluation") ?>
            <select name="data[mode]">
                <option value=""></option>
                <option value="paper"<?= $profile['mode'] === "paper" ? " selected" : "" ?>>
                    <?= _("Papierbasierte Evaluation") ?>
                </option>
                <option value="online"<?= $profile['mode'] === "online" ? " selected" : "" ?>>
                    <?= _("Online-Evaluation") ?>
                </option>
            </select>
        </label>

        <label>
            <?= _("Adresse für den Versand der Fragebögen") ?>
            <textarea name="data[address]"><?= htmlReady($profile['address']) ?></textarea>
        </label>

    </fieldset>

    <fieldset>
        <legend>
            <?= _("Standardfragebögen nach Veranstaltungstypen") ?>
        </legend>

        <table class="default">
            <tbody>
                <? foreach (SemType::getTypes() as $sem_type) : ?>
                <tr>
                    <td>
                        <?= htmlReady($GLOBALS['SEM_CLASS'][$sem_type['class']]['name']) ?>: <?= htmlReady($sem_type['name']) ?>
                    </td>
                    <td>
                        <label>
                            <div>
                                <?= _("Standardfragebogen") ?>
                            </div>
                            <select name="forms_by_type[<?= htmlReady($sem_type['id']) ?>]" class="select2">
                                <option value=""></option>
                                <? foreach (EvasysForm::findBySQL("active = '1' ORDER BY name ASC") as $form) : ?>
                                    <option value="<?= htmlReady($form->getId()) ?>"<?= $forms_by_type[$sem_type['id']][0] == $form->getId() ? " selected" : "" ?>>
                                        <?= htmlReady($form['name']) ?>
                                    </option>
                                <? endforeach ?>
                            </select>
                        </label>

                        <label>
                            <div>
                                <?= _("Verfügbar") ?>
                            </div>
                            <select name="available_forms_by_type[<?= htmlReady($sem_type['id']) ?>][]" multiple class="select2">
                                <option value=""></option>
                                <? foreach (EvasysForm::findBySQL("active = '1' ORDER BY name ASC") as $form) : ?>
                                    <option value="<?= htmlReady($form->getId()) ?>"<?= in_array($form->getId(), (array) $available_forms_by_type[$sem_type['id']]) ? " selected" : "" ?>>
                                        <?= htmlReady($form['name']) ?>
                                    </option>
                                <? endforeach ?>
                            </select>
                        </label>
                    </td>
                    <td>
                        <? if (count($available_forms_by_type[$sem_type['id']])) : ?>
                        <a href="<?= PluginEngine::getLink($plugin, array(), "forms/sort/".$this->controller->profile_type."/".$sem_type['id']."/".$profile->getId()) ?>"
                           title="<?= _("Sortierung bearbeiten") ?>"
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
        <legend><?= _("Freiwillige Evaluationen") ?></legend>

        <label>
            <?= _("Beginn der Antragsfrist") ?>
            <input type="text" name="data[antrag_begin]" value="<?= $profile['antrag_begin'] ? date("d.m.Y H:i", $profile['antrag_begin']) : "" ?>" class="datepicker">
        </label>

        <label>
            <?= _("Ende der Antragsfrist") ?>
            <input type="text" name="data[antrag_end]" value="<?= $profile['antrag_end'] ? date("d.m.Y H:i", $profile['antrag_end']) : "" ?>" class="datepicker">
        </label>

        <label>
            <?= _("Informationstext") ?>
            <textarea name="data[antrag_info]"><?= htmlReady($profile['antrag_info']) ?></textarea>
        </label>

    </fieldset>

    <script>
        jQuery(function () {
            jQuery("input.datepicker").datetimepicker();
            jQuery(".select2").select2();
            jQuery(".select2").select2();
        });
    </script>
    <style>
        .ui-widget, #layout_wrapper #barBottomContainer {
            z-index: 100000 !important;
        }
    </style>

    <div style="text-align: center;">
        <?= \Studip\Button::create(_("Speichern")) ?>
    </div>

</form>

<? else : ?>
    <?= MessageBox::info(_("Wählen Sie erst eine Einrichtung aus.")) ?>
<? endif ?>

<?

$list = new SelectWidget(
    _('Einrichtung'),
    PluginEngine::getURL($plugin, array(), ""),
    'institute'
);
$insts = Institute::getMyInstitutes($GLOBALS['user']->id);
$list->class = 'institute-list';
if ($GLOBALS['perm']->have_perm('root') || (count($insts) > 1)) {
    $list->addElement(new SelectElement(
        'all',
        $GLOBALS['perm']->have_perm('root') ? _('Alle') : _('Alle meine Einrichtungen'),
        $GLOBALS['user']->cfg->MY_INSTITUTES_DEFAULT === 'all'),
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

    //check if the institute is a faculty.
    //If true, then add another option to display all courses
    //from that faculty and all its institutes.

    //$institut is an array, we can't use the method isFaculty() here!
    if ($institut['fakultaets_id'] == $institut['Institut_id']) {
        $list->addElement(
            new SelectElement(
                $institut['Institut_id'] . '_withinst', //_withinst = with institutes
                ' ' . $institut['Name'] . ' +' . _('Institute'),
                ($GLOBALS['user']->cfg->MY_INSTITUTES_DEFAULT === $institut['Institut_id'] && $GLOBALS['user']->cfg->MY_INSTITUTES_INCLUDE_CHILDREN)
            ),
            'select-' . $institut['Name'] . '-with_institutes'
        );
    }
}
Sidebar::Get()->addWidget($list, 'filter_institute');