(function ($) {
  $(document).ready(function () {
    $(document).on("submit", "[data-js-form=filter]", function (e) {
      e.preventDefault();

      var data = $(this).serialize();

      $.ajax({
        url: wpAjax.ajaxUrl,
        data: data,
        type: "post",
        success: function (result) {
          $("[data-ja-filter=xyz-services]").html(result);
        },
        error: function (result) {
          console.warn(result);
        },
      });
    });
  });
})(jQuery);
