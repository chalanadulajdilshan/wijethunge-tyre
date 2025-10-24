$(document).ready(function () {
    // Function to combine month and year into YYYY-MM format
    function combineMonthYear() {
        const month = $("#month").val();
        const year = $("#year").val();
        if (month && year) {
            $("#combined_month").val(year + '-' + month);
        } else {
            $("#combined_month").val('');
        }
    }

    // Update combined month when either select changes
    $("#month, #year").change(function() {
        combineMonthYear();
    });

    // Reset form
    $("#new").click(function () {
        $("#form-data")[0].reset();
        $("#target_id").val("");
        $("#update").hide();
        $("#create").show();
    });

    // Save
    $("#create").click(function (e) {
        e.preventDefault();
        combineMonthYear(); // Ensure combined month is updated
        let formData = $("#form-data").serialize();

        // Show loading state
        $("#create").prop("disabled", true).html('<i class="uil uil-save me-1"></i> Saving...');

        $.post("ajax/php/monthly-target.php", { action: "create", data: formData }, function (response) {
            $("#create").prop("disabled", false).html('<i class="uil uil-save me-1"></i> Save');

            if (response.status === 'success') {
                swal({
                    title: "Success!",
                    text: response.message,
                    icon: "success",
                    button: "OK"
                }).then(() => {
                    location.reload();
                });
            } else {
                swal({
                    title: "Error!",
                    text: response.message,
                    icon: "error",
                    button: "OK"
                });
            }
        }, "json").fail(function() {
            $("#create").prop("disabled", false).html('<i class="uil uil-save me-1"></i> Save');
            swal({
                title: "Error!",
                text: "An error occurred while saving the target.",
                icon: "error",
                button: "OK"
            });
        });
    });

    // Update
    $("#update").click(function (e) {
        e.preventDefault();
        combineMonthYear(); // Ensure combined month is updated
        let formData = $("#form-data").serialize();

        // Show loading state
        $("#update").prop("disabled", true).html('<i class="uil uil-edit me-1"></i> Updating...');

        $.post("ajax/php/monthly-target.php", { action: "update", data: formData }, function (response) {
            $("#update").prop("disabled", false).html('<i class="uil uil-edit me-1"></i> Update');

            if (response.status === 'success') {
                swal({
                    title: "Success!",
                    text: response.message,
                    icon: "success",
                    button: "OK"
                }).then(() => {
                    location.reload();
                });
            } else {
                swal({
                    title: "Error!",
                    text: response.message,
                    icon: "error",
                    button: "OK"
                });
            }
        }, "json").fail(function() {
            $("#update").prop("disabled", false).html('<i class="uil uil-edit me-1"></i> Update');
            swal({
                title: "Error!",
                text: "An error occurred while updating the target.",
                icon: "error",
                button: "OK"
            });
        });
    });

    // Delete
    $(".delete-target").click(function () {
        let id = $("#target_id").val();

        if (id) {
            swal({
                title: "Are you sure?",
                text: "You want to delete this monthly target. This action cannot be undone!",
                icon: "warning",
                buttons: {
                    cancel: {
                        text: "Cancel",
                        value: null,
                        visible: true,
                        className: "btn btn-secondary"
                    },
                    confirm: {
                        text: "Yes, Delete",
                        value: true,
                        visible: true,
                        className: "btn btn-danger"
                    }
                },
                dangerMode: true
            }).then((willDelete) => {
                if (willDelete) {
                    // Show loading state
                    $(".delete-target").prop("disabled", true).html('<i class="uil uil-trash-alt me-1"></i> Deleting...');

                    $.post("ajax/php/monthly-target.php", { action: "delete", id: id }, function (response) {
                        $(".delete-target").prop("disabled", false).html('<i class="uil uil-trash-alt me-1"></i> Delete');

                        if (response.status === 'success') {
                            swal({
                                title: "Deleted!",
                                text: response.message,
                                icon: "success",
                                button: "OK"
                            }).then(() => {
                                location.reload();
                            });
                        } else {
                            swal({
                                title: "Error!",
                                text: response.message,
                                icon: "error",
                                button: "OK"
                            });
                        }
                    }, "json").fail(function() {
                        $(".delete-target").prop("disabled", false).html('<i class="uil uil-trash-alt me-1"></i> Delete');
                        swal({
                            title: "Error!",
                            text: "An error occurred while deleting the target.",
                            icon: "error",
                            button: "OK"
                        });
                    });
                }
            });
        } else {
            swal({
                title: "Warning!",
                text: "Please select a target to delete.",
                icon: "warning",
                button: "OK"
            });
        }
    });

    // Select from modal
    $(".select-target").click(function () {
        $("#target_id").val($(this).data("id"));
        const monthValue = $(this).data("month");

        // Parse the YYYY-MM format and set individual selects
        if (monthValue && monthValue.includes('-')) {
            const parts = monthValue.split('-');
            const year = parts[0];
            const month = parts[1];
            $("#year").val(year);
            $("#month").val(month);
            $("#combined_month").val(monthValue);
        }

        $("#target").val($(this).data("target"));
        $("#target_commission").val($(this).data("target_commission"));
        $("#supper_target").val($(this).data("supper_target"));
        $("#supper_target_commission").val($(this).data("supper_target_commission"));
        $("#collection_target").val($(this).data("collection_target"));
        $("#sales_executive_id").val($(this).data("sales_executive_id"));
        $("#update").show();
        $("#create").hide();
        $("#target_master").modal("hide");
    });
});
