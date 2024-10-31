jQuery(document).ready(function() {
  // Validate if key is set when Piio is enabled
  jQuery("#piio_imageopt_enabled").closest("form").submit(function(event) {

    var apiKeyError = jQuery("#piio_imageopt_api_key_error");
    apiKeyError.hide();

    var enabled = jQuery("#piio_imageopt_enabled");
    if (enabled.length > 0 && enabled[0].value == 1) {

      var apiKey = jQuery("#piio_imageopt_api_key");
      if (apiKey.length > 0 && apiKey[0].value.trim().length === 0) {
        event.preventDefault();
        apiKeyError.show();
        apiKey.focus();
      }
    }
  })

  jQuery("#piio_check_consumption_link").click(function(event) {
    event.preventDefault();
    var data = {
      'action': 'piio_get_consumption'
    };

    jQuery.post(ajaxurl, data, function() {
      location.reload();
    });
  });
});