<h1><?= _("EvaSys-Fragebögen") ?></h1>
<form action="<?= PluginEngine::getLink($plugin, array(), "forms/activate") ?>" method="post">

    <table class="default">
        <thead>
            <tr>
                <th width="20"></th>
                <th><?= _("Name") ?></th>
                <th><?= _("Anzahl Veranstaltungen") ?></th>
                <th><?= _("Info") ?></th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <? foreach ($forms as $form) : ?>
                <tr class="<?= $form['active'] ? "" : "inactive" ?>">
                    <td>
                        <input type="checkbox" name="a[]" value="<?= htmlReady($form->getId()) ?>" <?= $form['active'] ? " checked" : "" ?>>
                    </td>
                    <td><?= htmlReady($form['name']) ?></td>
                    <td><?= htmlReady($form->getNumberOfCourses()) ?></td>
                    <td>
                        <? if ($form['link']) : ?>
                            <a href="<?= htmlReady($form['link']) ?>" target="_blank">
                                <?= Icon::create("info-circle", "clickable")->asImg(20) ?>
                            </a>
                        <? endif ?>
                    </td>
                    <td class="actions">
                        <a href="<?= PluginEngine::getLink($plugin, array(), "forms/edit/".$form->getId()) ?>" data-dialog>
                            <?= Icon::create("edit", "clickable")->asImg(20) ?>
                        </a>
                    </td>
                </tr>
            <? endforeach ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="100">
                    <?= \Studip\Button::create(_("Speichern")) ?>
                </td>
            </tr>
        </tfoot>
    </table>

</form>