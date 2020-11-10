<table class="default up">
    <tbody>
        <tr>
            <td><strong><?= dgettext("evasys", "SOAP-Methode") ?></strong></td>
            <td><?= htmlReady($log['function']) ?></td>
        </tr>
        <tr>
            <td><strong><?= dgettext("evasys", "Argumente") ?></strong></td>
            <td>
                <pre><?= htmlReady(json_encode($log['arguments']->getArrayCopy(), JSON_PRETTY_PRINT)) ?></pre>
            </td>
        </tr>
        <tr>
            <td><strong><?= dgettext("evasys", "Ergebnis") ?></strong></td>
            <td>
                <pre><?= htmlReady(json_encode($log['result']->getArrayCopy(), JSON_PRETTY_PRINT)) ?></pre>
            </td>
        </tr>
    </tbody>
</table>
