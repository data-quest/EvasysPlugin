<table class="default">
    <thead>
        <tr>
            <th><?= dgettext("evasys", "Name") ?></th>
            <th><?= dgettext("evasys", "Nur papierbasierte Evaluationen") ?></th>
            <th class="actions"><?= dgettext("evasys", "Aktion") ?></th>
        </tr>
    </thead>
    <tbody>
        <? foreach ($fields as $field) : ?>
            <tr>
                <td><?= htmlReady($field['name']) ?></td>
                <td><?= Icon::create("checkbox-".(!$field['paper'] ? "un" : "")."checked", "info")->asImg(20, array('class' => "text-bottom")) ?></td>
                <td class="actions">
                    <a href="<?= PluginEngine::getLink($plugin, array(), "config/edit_additionalfield/".$field->getId()) ?>" data-dialog>
                        <?= Icon::create("edit", "clickable")->asImg(20, array('class' => "text-bottom")) ?>
                    </a>
                    <form action="<?= PluginEngine::getLink($plugin, array(), "config/delete_additionalfield/".$field->getId()) ?>"
                          method="post"
                          style="display: inline; margin: 0px; padding: 0px; border: none;">
                        <button data-confirm style="display: inline; margin: 0px; padding: 0px; border: none; cursor: pointer;">
                            <?= Icon::create("trash", "clickable")->asImg(20, array('class' => "text-bottom")) ?>
                        </button>
                    </form>
                </td>
            </tr>
        <? endforeach ?>
    </tbody>
    <tfoot>
        <tr>
            <td colspan="100">
                <a href="<?= PluginEngine::getLink($plugin, array(), "config/edit_additionalfield") ?>" data-dialog>
                    <?= Icon::create("add", "clickable")->asImg(20, array('class' => "text-bottom")) ?>
                </a>
            </td>
        </tr>
    </tfoot>
</table>