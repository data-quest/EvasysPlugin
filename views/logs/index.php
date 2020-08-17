<table class="default" id="evasys_logs">
    <caption>
        <?= _("SOAP-Logeinträge") ?>
    </caption>
    <thead>
        <tr>
            <th width="16"></th>
            <th><?= _("SOAP-Methode") ?></th>
            <th><?= _("Dauer (ms)") ?></th>
            <th><?= _("Zeitpunkt") ?></th>
            <th class="actions"><?= _("Aktion") ?></th>
        </tr>
    </thead>
    <tbody>
        <? foreach ($logs as $log) : ?>
            <?= $this->render_partial("logs/_row", ['log' => $log, 'plugin' => $plugin]) ?>
        <? endforeach ?>
    </tbody>
    <tfoot>
    <? if ($more) : ?>
        <tr class="more">
            <td colspan="5" style="text-align: center">
                <?= Assets::img("ajax-indicator-black.svg") ?>
            </td>
        </tr>
    <? endif ?>
    </tfoot>
</table>


<script>
    //Infinity-scroll:
    jQuery(window.document).bind('scroll', _.throttle(function (event) {
        if ((jQuery(window).scrollTop() + jQuery(window).height() > jQuery(window.document).height() - 1200)
            && (jQuery("#evasys_logs .more").length > 0)) {
            //nachladen
            jQuery("#evasys_logs .more").removeClass("more").addClass("loading");
            let earliest = null;
            jQuery("#evasys_logs tbody > tr").each(function () {
                if (earliest === null || earliest > jQuery(this).data("id")) {
                    earliest = jQuery(this).data("id");
                }
            });
            jQuery.ajax({
                url: STUDIP.ABSOLUTE_URI_STUDIP + "plugins.php/evasysplugin/logs/more",
                data: {
                    'earliest': earliest
                },
                dataType: "json",
                success: function (response) {
                    for (let i in response.rows) {
                        jQuery("#evasys_logs tbody").append(response.rows[i]);
                    }
                    if (response.more) {
                        jQuery("#evasys_logs .loading").removeClass("loading").addClass("more");
                    } else {
                        jQuery("#evasys_logs .loading").remove();
                    }
                }
            });
        }
    }, 30));
</script>
