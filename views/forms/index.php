<form action="<?= PluginEngine::getLink($plugin, array(), "forms/activate") ?>" method="post">

    <table class="default evasys_formstable">
        <caption><?= dgettext("evasys", "EvaSys-Fragebögen") ?></caption>
        <thead>
            <tr>
                <th width="20">
                    <input data-proxyfor=".evasys_formstable tbody input[type=checkbox]" type="checkbox">
                </th>
                <th><?= dgettext("evasys", "Name") ?></th>
                <th><?= dgettext("evasys", "Überschrift") ?></th>
                <th><?= dgettext("evasys", "Info") ?></th>
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
                    <td><?= htmlReady($form['description']) ?></td>
                    <td>
                        <? if ($form['link']) : ?>
                            <a href="<?= htmlReady($form['link']) ?>" target="_blank">
                                <?= Icon::create("info-circle", "clickable")->asImg(20, array('class' => "text-middle")) ?>
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
                    <?= \Studip\Button::create(dgettext("evasys", "Speichern")) ?>
                </td>
            </tr>
        </tfoot>
    </table>

</form>