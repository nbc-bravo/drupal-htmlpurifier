(function ($) {

  Drupal.behaviors.htmlpurifierConfigForm = {
    // Makes all configuration links open in new windows; can save lots of grief!
    attach: function (context, settings) {
      $(".hp-config a", context).click(function () {
        window.open(this.href);
        return false;
      });
    }
  };

})(jQuery);

// This has been copied from vendor/ezyang/htmlpurifier/library/HTMLPurifier/Printer/ConfigForm.js
function toggleWriteability(id_of_patient, checked) {
  document.getElementById(id_of_patient).disabled = checked;
}
