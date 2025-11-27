jQuery(document).ready(function ($) {
  // Approve pending user
  $(document).on("click", ".ims-approve-btn", function (e) {
    e.preventDefault();
    var $btn = $(this);
    var id = $btn.data("id");
    if (!id) return alert("Missing id");
    $btn.prop("disabled", true).text("Approving...");
    $.post(
      ims_ajax.ajax_url,
      {
        action: "ims_approve_user",
        id: id,
        nonce: ims_ajax.nonce,
      },
      function (resp) {
        if (resp.success) {
          $btn.closest("tr").fadeOut(300, function () {
            $(this).remove();
          });
        } else {
          alert("Failed: " + (resp.data || "unknown"));
          $btn.prop("disabled", false).text("Approve");
        }
      }
    );
  });

  // Set user status (from All Users page)
  $(document).on("click", ".ims-set-status", function (e) {
    e.preventDefault();
    var $row = $(this).closest("tr");
    var id = $(this).data("id") || $row.data("id");
    var status = $row.find(".ims-user-status-select").val();
    if (!id) return alert("Missing id");
    $(this).prop("disabled", true).text("Saving...");
    $.post(
      ims_ajax.ajax_url,
      {
        action: "ims_set_user_status",
        id: id,
        status: status,
        nonce: ims_ajax.nonce,
      },
      function (resp) {
        if (resp.success) location.reload();
        else alert("Failed");
      }
    );
  });

  // Toggle admin flag
  $(document).on("change", ".ims-admin-toggle", function () {
    var id = $(this).data("id");
    var val = $(this).is(":checked") ? 1 : 0;
    $.post(
      ims_ajax.ajax_url,
      {
        action: "ims_set_user_admin",
        id: id,
        is_admin: val,
        nonce: ims_ajax.nonce,
      },
      function (resp) {
        if (!resp.success) alert("Failed to update admin flag");
      }
    );
  });

  setInterval(function () {
    if ($(".ims-dashboard").length && document.hasFocus()) location.reload();
  }, 30000);

  // better tables
  $("#table").DataTable({
    buttons: ["csv", "excel", "pdf"],
    layout: {
      topStart: "buttons",
    },
    order: [],
  });
  console.log("test");
});
