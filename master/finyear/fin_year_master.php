<?php
include '../../config/db.php';
include '../../includes/auth.php';

$page = basename($_SERVER['PHP_SELF']);
$stmt = $conn->prepare("
    SELECT COUNT(*)
    FROM menu_master m
    JOIN role_menu_permission rmp ON m.MenuId = rmp.MenuId
    WHERE rmp.RoleId = ? AND m.PageUrl LIKE ? AND rmp.Status = 1
");
$stmt->execute([$_SESSION['role'], "%$page%"]);
if ($stmt->fetchColumn() == 0) {
    die("Unauthorized Access");
}


$years = $conn->query("SELECT * FROM fin_year_master ORDER BY FinYear DESC")
              ->fetchAll(PDO::FETCH_ASSOC);

// detect existing current year
$currentYearId = null;
foreach ($years as $y) {
    if ($y['IsCurrent']) {
        $currentYearId = $y['Id'];
        break;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Financial Year Master</title>

<link rel="stylesheet" href="../../css/bootstrap.min.css">
<link rel="stylesheet" href="../../css/all.min.css">
<style>
body { background:#f4f6fb; }

.card-box {
    background:#fff;
    border-radius:12px;
    box-shadow:0 8px 25px rgba(0,0,0,0.06);
    padding:20px;
}

.table th {
    background:#eef1f7;
}
</style>
</head>

<body>

<?php
$topbar = realpath(__DIR__ . '/../../layout/topbar.php')
       ?: realpath(__DIR__ . '/../layout/topbar.php')
       ?: realpath(__DIR__ . '/../../../layout/topbar.php')
       ?: realpath(__DIR__ . '/../../includes/topbar.php')
       ?: realpath(__DIR__ . '/../../includes/layout/topbar.php');

$sidebar = realpath(__DIR__ . '/../../layout/sidebar.php')
        ?: realpath(__DIR__ . '/../layout/sidebar.php')
        ?: realpath(__DIR__ . '/../../../layout/sidebar.php')
        ?: realpath(__DIR__ . '/../../includes/sidebar.php')
        ?: realpath(__DIR__ . '/../../includes/layout/sidebar.php');

if (!$topbar || !$sidebar) {
    die('Layout files not found. Please check folder structure.');
}

require $topbar;
require $sidebar;
?>

<div class="container mt-5">
<div class="card-box">

    <!-- HEADER -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="fw-semibold">
            <i class="fa-solid fa-calendar-days text-primary me-2"></i>
            Financial Year Master
        </h5>

        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addModal">
            <i class="fa-solid fa-plus me-1"></i> Add Financial Year
        </button>
    </div>

    <!-- TABLE -->
    <table class="table table-bordered table-hover align-middle">
        <thead>
            <tr>
                <th width="60">#</th>
                <th>Financial Year</th>
                <th width="120">Current</th>
                <th width="160">Action</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach($years as $i=>$y): ?>
            <tr>
                <td><?= $i+1 ?></td>
                <td><?= htmlspecialchars($y['FinYear']) ?></td>
                <td>
                    <?= $y['IsCurrent']
                        ? '<span class="badge bg-success">Yes</span>'
                        : '<span class="badge bg-secondary">No</span>' ?>
                </td>
                <td>
                    <button class="btn btn-sm btn-outline-warning editBtn"
                            data-id="<?= $y['Id'] ?>"
                            data-year="<?= htmlspecialchars($y['FinYear']) ?>"
                            data-current="<?= $y['IsCurrent'] ?>">
                        <i class="fa-solid fa-pen"></i>
                    </button>

                  
                    <button class="btn btn-sm btn-outline-danger deleteBtn"
                            data-id="<?= $y['Id'] ?>">
                        <i class="fa-solid fa-trash"></i>
                    </button>
                   
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

</div>
</div>

<!-- ================= ADD MODAL ================= -->
<div class="modal fade" id="addModal">
<div class="modal-dialog">
<form method="post" action="fin_year_save.php" class="modal-content">
    <div class="modal-header">
        <h5 class="modal-title">Add Financial Year</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
    </div>

    <div class="modal-body">
        <label class="form-label">Financial Year</label>
        <input type="text"
               name="finyear"
               class="form-control"
               placeholder="2024-25"
               pattern="^\d{4}-\d{2}$"
               title="Format must be YYYY-YY"
               required>

        <div class="form-check form-switch mt-3">
            <input class="form-check-input"
                   type="checkbox"
                   name="iscurrent"
                   id="add_current"
                   <?= $currentYearId ? 'disabled' : '' ?>>
            <label class="form-check-label" for="add_current">
                Set as Current Year
                <?php if($currentYearId): ?>
                    <small class="text-danger">(Already set)</small>
                <?php endif; ?>
            </label>
        </div>
    </div>

    <div class="modal-footer">
        <button class="btn btn-primary">Save</button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
    </div>
</form>
</div>
</div>

<!-- ================= EDIT MODAL ================= -->
<div class="modal fade" id="editModal">
<div class="modal-dialog">
<form method="post" action="fin_year_edit_save.php" class="modal-content">
    <div class="modal-header">
        <h5 class="modal-title">Edit Financial Year</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
    </div>

    <div class="modal-body">
        <input type="hidden" name="id" id="edit_id">

        <label class="form-label">Financial Year</label>
        <input type="text"
               name="finyear"
               id="edit_year"
               class="form-control"
               pattern="^\d{4}-\d{2}$"
               required>

        <div class="form-check form-switch mt-3">
            <input class="form-check-input" type="checkbox" name="iscurrent" id="edit_current">
            <label class="form-check-label">Set as Current Year</label>
        </div>
    </div>

    <div class="modal-footer">
        <button class="btn btn-primary">Update</button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
    </div>
</form>
</div>
</div>

<!-- JS -->
<script src="../../js/jquery-3.7.1.min.js"></script>
<script src="../../js/bootstrap/bootstrap.bundle.min.js"></script>
<script src="../../js/sweetalert2.all.min.js"></script>

<script>
const existingCurrentId = <?= $currentYearId ? $currentYearId : 'null' ?>;

// EDIT
$('.editBtn').on('click', function () {

    const id = $(this).data('id');
    const isCurrent = $(this).data('current') == 1;

    $('#edit_id').val(id);
    $('#edit_year').val($(this).data('year'));

    if (existingCurrentId && !isCurrent) {
        $('#edit_current').prop('checked', false).prop('disabled', true);
    } else {
        $('#edit_current').prop('checked', isCurrent).prop('disabled', false);
    }

    new bootstrap.Modal(document.getElementById('editModal')).show();
});

// DELETE (SweetAlert)
$('.deleteBtn').on('click', function () {
    const id = $(this).data('id');

    Swal.fire({
        title: 'Are you sure?',
        text: 'This financial year will be deleted permanently!',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        confirmButtonText: 'Yes, delete it'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = 'fin_year_delete.php?id=' + id;
        }
    });
});
</script>

</body>
</html>
