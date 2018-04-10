<form action="<?= PluginEngine::getLink($plugin, array(), "matching/".$action) ?>"
      method="post"
      class="default">

    <table class="default">
        <caption>
            <?= $action === "seminartypes" ? _("Veranstaltungstypen") : _("Einrichtungen") ?>
        </caption>
        <thead>
            <tr>
                <th><?= _("Name") ?></th>
                <th><?= _("Name in Evasys") ?></th>
            </tr>
        </thead>
        <tbody>
            <? foreach ($items as $item) : ?>
            <tr>
                <td>
                    <?= htmlReady($item['long_name']) ?>
                </td>
                <td>
                    <input type="text"
                           name="matching[<?= htmlReady($item['id']) ?>]"
                           value="<?= htmlReady($item['matching'] ? ($item['matching']['name'] !== null ? $item['matching']['name'] : $item['name']) : $item['name']) ?>">
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