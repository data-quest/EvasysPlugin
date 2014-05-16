<?php

/*
 *  Copyright (c) 2011  Rasmus Fuhse <fuhse@data-quest.de>
 * 
 *  This program is free software; you can redistribute it and/or
 *  modify it under the terms of the GNU General Public License as
 *  published by the Free Software Foundation; either version 2 of
 *  the License, or (at your option) any later version.
 */
?>
<style>
    #settings_window table > tbody > tr > td {
        padding-bottom: 7px;
        padding-top: 7px;
    }
</style>
<div id="settings_window" style="display: none;">
    <form action="?" method="post">
        <p class="info">
            <?= _("Das EvaSys-Plugin benötigt noch ein paar Informationen, um sauber zu funktionieren. Tragen Sie die jetzt bitte nach. Später können Sie die unter Globale Einstellungen -> Konfiguration jederzeit ändern (Sektion EVASYS_PLUGIN).") ?>
        </p>
        <table>
            <tbody>
                <tr>
                    <td width="50%"><label for="EVASYS_WSDL"><?= _("WSDL-Datei") ?></label><br><span style="font-size: 0.8em;"><?= _("Pfad zur WSDL-Datei. Diese Datei ist repräsentativ für die Schnittstelle zu EvaSys und wird vermutlich auf einem eigenen Server liegen.") ?></span></td>
                    <td width="50%"><input type="text" name="EVASYS_WSDL" id="EVASYS_WSDL" style="width: 100%" value="<?= htmlReady(get_config("EVASYS_WSDL")) ?>"></td>
                </tr>
                <tr>
                    <td width="50%"><label for="EVASYS_URI"><?= _("EvaSys Server-Adresse") ?></label><br><span style="font-size: 0.8em;"><?= _("Adresse des Servers, auf dem die Umfragen von EvaSys liegen.") ?></span></td>
                    <td width="50%"><input type="text" name="EVASYS_URI" id="EVASYS_WSDL" style="width: 100%" value="<?= htmlReady(get_config("EVASYS_URI")) ?>"></td>
                </tr>
                <tr>
                    <td><label for="EVASYS_USER"><?= _("Nutzername, damit sich Stud.IP an EvaSys anmelden kann.") ?></label></td>
                    <td><input type="text" name="EVASYS_USER" id="EVASYS_USER" style="width: 100%" value="<?= htmlReady(get_config("EVASYS_USER")) ?>"></td>
                </tr>
                <tr>
                    <td><label for="EVASYS_PASSWORD"><?= _("Passwort, damit sich Stud.IP an EvaSys anmelden kann.") ?></label><br><span style="font-size: 0.8em;"><?= _("Für verschlüsselten Zugriff auf den Server \"ssl://\" bzw \"tsl://\" voran schreiben!") ?></span></td>
                    <td><input type="text" name="EVASYS_PASSWORD" id="EVASYS_PASSWORD" style="width: 100%" value="<?= htmlReady(get_config("EVASYS_PASSWORD")) ?>"></td>
                </tr>
                <tr>
                    <td><label for="EVASYS_CACHE"><?= _("Wie lange sollen Abfragen an Evasys gecached werden?") ?></label><br><span style="font-size: 0.8em;"><?= _("Empfehlung: 15 Minuten.") ?></span></td>
                    <td><input type="text" name="EVASYS_CACHE" id="EVASYS_CACHE" style="width: 100%" value="<?= htmlReady(get_config("EVASYS_CACHE")) ?>"></td>
                </tr>
                <tr>
                    <td><label for="EVASYS_PUBLISH_RESULTS"><?= _("Ergebnisse für Studenten veröffentlichen?") ?></label><br><span style="font-size: 0.8em;"><?= _("Ergebnisse werden natürlich nur veröffentlicht, wenn die Evaluation abgeschlossen ist.") ?></span></td>
                    <td><input type="checkbox" name="EVASYS_PUBLISH_RESULTS" id="EVASYS_PUBLISH_RESULTS" value="1" <?= get_config("EVASYS_PUBLISH_RESULTS") ? " checked" : "" ?>></td>
                </tr>
                <tr>
                    <td></td>
                    <td><?= makebutton("absenden", "input") ?></td>
                </tr>
            </tbody>
        </table>
    </form>
    <div style="height: 20px; width:10px;"></div>
</div>
<script>
    jQuery(function ($) {
        $("#settings_window").dialog({
            modal: true,
            title: '<?= _("EvaSysPlugin einrichten") ?>',
            width: '600px'
        });
        $('#settings_window').zIndex($('#settings_window').parent().zIndex() + 1);
    });
</script>
