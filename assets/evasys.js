jQuery(function () {
    jQuery(".semtype_matching .copypaste .copy").on("click", function () {
        jQuery(this).closest(".semtype_matching").addClass("copying");
        jQuery(this).closest(".copypaste").addClass("fromhere");
        return false;
    });
    jQuery(".semtype_matching .copypaste .from").on("click", function () {
        jQuery(this).closest(".semtype_matching").removeClass("copying");
        jQuery(this).closest(".copypaste").removeClass("fromhere");
        return false;
    });
    jQuery(".semtype_matching .copypaste .paste").on("click", function () {
        //paste values
        jQuery(this).closest("tr").find("select.standard").val(
            jQuery(".semtype_matching .copypaste.fromhere").closest("tr").find("select.standard").val()
        ).trigger('change');
        var values = [];
        jQuery(this).closest("tr").find("select.available").val(
            jQuery(".semtype_matching .copypaste.fromhere").closest("tr").find("select.available").val()
        ).trigger('change');
        jQuery(this).closest(".semtype_matching").removeClass("copying");
        jQuery(this).closest(".semtype_matching").find(".fromhere").removeClass("fromhere");
        return false;
    });
});
STUDIP.EvaSys = {
    showQR: function () {
        var qr = jQuery("#qr_code_evasys")[0];
        if (qr.requestFullscreen) {
            qr.requestFullscreen();
        } else if (qr.msRequestFullscreen) {
            qr.msRequestFullscreen();
        } else if (qr.mozRequestFullScreen) {
            qr.mozRequestFullScreen();
        } else if (qr.webkitRequestFullscreen) {
            qr.webkitRequestFullscreen(Element.ALLOW_KEYBOARD_INPUT);
        }
    }
};