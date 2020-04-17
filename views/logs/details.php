<table class="default up">
    <tbody>
        <tr>
            <td><strong><?= _("SOAP-Methode") ?></strong></td>
            <td><?= htmlReady($log['function']) ?></td>
        </tr>
        <tr>
            <td><strong><?= _("Argumente") ?></strong></td>
            <td>
                <pre><?= htmlReady(json_encode($log['arguments']->getArrayCopy(), JSON_PRETTY_PRINT)) ?></pre>
            </td>
        </tr>
        <tr>
            <td><strong><?= _("Ergebnis") ?></strong></td>
            <td>
                <pre><?= htmlReady(json_encode($log['result']->getArrayCopy(), JSON_PRETTY_PRINT)) ?></pre>
            </td>
        </tr>
    </tbody>
</table>
