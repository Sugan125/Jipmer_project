<?php
include '../../config/db.php';
include '../../includes/auth.php';
require_role(4);

// Fetch HOA list
$hoas = $conn->query("
    SELECT h.*, u.EmployeeName AS CreatedByName
    FROM head_of_account_master h
    LEFT JOIN employee_master u ON h.CreatedBy = u.Id
    ORDER BY h.Id DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>

<?php include '../../header/header_admin.php'; ?>

<link rel="stylesheet" href="../../css/bootstrap.min.css">
<link rel="stylesheet" href="../../css/all.min.css">
<link rel="stylesheet" href="../../js/datatables/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="../../css/style.css">


<style>
.table-hover tbody tr:hover {
    background-color: #f1f8ff !important;
}
.action-btns button {
    margin-right: 4px;
}
</style>

<div class="container" style="margin-top:120px">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="fw-bold">Head of Account - Master List</h3>
          <a href="hoa_add.php" class="btn btn-outline-primary btn-sm">
    <i class="fa-solid fa-plus me-1"></i> Add New HOA
</a>
    </div>

    <div class="card shadow-lg border-0">
        <div class="card-body">
            <table id="hoaTable" class="table table-striped table-hover table-bordered" style="width:100%">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Financial Year</th>
                        <th>Full HOA</th>
                        <th>Description</th>
                        <th>Created By</th>
                        <th>Created Date</th>
                        <th style="width:130px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                
                </tbody>
            </table>
        </div>
    </div>

</div>
<div class="modal fade" id="editHoaModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">

            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">Edit Head of Account</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body" id="editHoaContent">
                <!-- form loads here via AJAX -->
                <div class="text-center p-3">
                    <div class="spinner-border"></div>
                    <p>Loading...</p>
                </div>
            </div>

        </div>
    </div>
</div>
<!-- Scripts -->
   <script src="../../js/jquery-3.7.1.min.js"></script>
    <script src="../../js/bootstrap/bootstrap.bundle.min.js"></script>
    <script src="../../js/sweetalert2.all.min.js"></script>

<!-- DATATABLES -->

<script src="../../js/datatables/jquery.dataTables.min.js"></script>
<script src="../../js/datatables/dataTables.bootstrap5.min.js"></script>

<script>
$(document).ready(function(){

    // Initialize DataTable
    window.hoaTable = $('#hoaTable').DataTable({
        ajax: 'hoa_list_ajax.php',
        columns: [
            { data: 'Id' },
            { data: 'FinancialYear' },
            { data: 'FullHOA' },
            { data: 'Description' },
            { data: 'CreatedByName' },
            { data: 'CreatedDate' },
            { data: 'Actions', orderable: false, searchable: false }
        ],
        responsive: true,
        pageLength: 10,
        order: [[0,'desc']]
    });

    // Delegate Edit button click
    $('#hoaTable tbody').on('click', '.edit-btn', function(){
        var id = $(this).data('id');
        openEditModal(id);
    });

    // Delegate Delete button click
    $('#hoaTable tbody').on('click', '.delete-btn', function(){
        var id = $(this).data('id');
        deleteHOA(id);
    });

});

// Open edit modal
function openEditModal(id) {

    $("#editHoaContent").html(`
        <div class="text-center p-3">
            <div class="spinner-border"></div>
            <p>Loading...</p>
        </div>
    `);

    $("#editHoaModal").modal("show");

    $.ajax({
        url: "hoa_edit_modal.php",
        type: "GET",
        data: { id: id },
        success: function(response) {
            $("#editHoaContent").html(response);
        }
    });

}

// Delete HOA function
function deleteHOA(id) {
    Swal.fire({
        title: "Are you sure?",
        text: "This HOA will be permanently deleted!",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#d33",
        cancelButtonColor: "#3085d6",
        confirmButtonText: "Yes, delete it!"
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: "hoa_delete_ajax.php",
                type: "POST",
                data: { id: id },
                success: function(res) {
                    if(res.trim() === "success"){
                        hoaTable.ajax.reload(null, false); // reload table after delete
                        Swal.fire("Deleted!", "HOA has been deleted.", "success");
                    } else {
                        Swal.fire("Error!", "Unable to delete HOA.", "error");
                    }
                }
            });
        }
    });
}

</script>
