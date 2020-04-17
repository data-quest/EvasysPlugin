<table class="default">
    <thead>
        <tr>
            <th><?= _("SOAP-Methode") ?></th>
            <th><?= _("Dauer (ms)") ?></th>
            <th><?= _("Zeitpunkt") ?></th>
            <th class="actions"><?= _("Aktion") ?></th>
        </tr>
    </thead>
    <tbody>
        <? foreach ($logs as $log) : ?>
        <tr>
            <td><a href="<?= PluginEngine::getLink($plugin, ['function' => $log['function']], "logs/index") ?>"><?= htmlReady($log['function']) ?></a></td>
            <td><?= htmlReady(str_replace(".", ",", $log['time'])) ?></td>
            <td><?= date("d.m.Y H:i:s", $log['mkdate']) ?></td>
            <td class="actions">
                <a href="<?= PluginEngine::getLink($plugin, [], "logs/details/".$log->getId()) ?>"
                   title="<?= _("Details anzeigen") ?>" data-dialog>
                    <?= Icon::create("info-circle", "clickable")->asImg(20, ['class' => "text-bottom"]) ?>
                </a>
            </td>
        </tr>
        <? endforeach ?>
    </tbody>
</table>
