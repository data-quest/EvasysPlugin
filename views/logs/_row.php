<tr data-id="<?= htmlReady($log->getId()) ?>">
    <td>
        <a href="<?= PluginEngine::getLink($plugin, ['function' => $log['function']], "logs/index") ?>" title="<?= dgettext("evasys", "Auf Typ filtern") ?>">
            <?= Icon::create("filter2", "clickable")->asImg(16, ['class' => "text-bottom"]) ?>
        </a>
    </td>
    <td>
        <a href="<?= PluginEngine::getLink($plugin, [], "logs/details/".$log->getId()) ?>" data-dialog title="<?= dgettext("evasys", "Details anzeigen") ?>">
            <?= htmlReady($log['function']) ?>
        </a>
    </td>
    <td><?= htmlReady(str_replace(".", ",", $log['time'])) ?></td>
    <td><?= date("d.m.Y H:i:s", $log['mkdate']) ?></td>
    <td class="actions">
        <a href="<?= PluginEngine::getLink($plugin, [], "logs/details/".$log->getId()) ?>"
           title="<?= dgettext("evasys", "Details anzeigen") ?>" data-dialog>
            <?= Icon::create("info-circle", "clickable")->asImg(20, ['class' => "text-bottom"]) ?>
        </a>
    </td>
</tr>
