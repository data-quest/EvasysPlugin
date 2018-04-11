<form action="<?= PluginEngine::getLink($plugin, array(), "matching/".$action) ?>"
      method="post"
      class="default">

    <table class="default">
        <caption>
            <?= $action === "seminartypes" ? _("Veranstaltungstypen") : ($action === "institutes" ? _("Einrichtungen") : _("Begriffe")) ?>
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
                    <? $value = $item['matching']
                        ? ($item['matching']['name'] !== null ? $item['matching']['name'] : new I18NString($item['name'], null, array('table' => "evasys_matchings", 'field' => "name")))
                        : new I18NString($item['name'], null, array('table' => "evasys_matchings", 'field' => "name")) ?>
                    <? if (!$i18n) : ?>
                        <input type="text"
                               name="matching[<?= htmlReady($item['id']) ?>]"
                               value="<?= htmlReady($value) ?>">
                    <? else : ?>
                        <?= I18N::input("matching__".$item['id']."__", $value) ?>
                    <? endif ?>
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