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


if (!isset($_GET['id'])) exit("Invalid request");

$id = intval($_GET['id']);

$stmt = $conn->prepare("SELECT * FROM head_of_account_master WHERE Id = ?");
$stmt->execute([$id]);

$hoa = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$hoa) exit("HOA not found");
?>

<form id="updateHOAForm">

    <input type="hidden" name="id" value="<?= $hoa['Id'] ?>">

    <div class="row g-3">

      <div class="col-md-4">
    <label class="form-label">Financial Year</label>
    <select name="financial_year" id="financial_year" class="form-select" required>
        <?php
        $currentYear = date('Y');
        $years = [
            ($currentYear-1).'-'.substr($currentYear, 2),
            $currentYear.'-'.substr($currentYear+1, 2),
            ($currentYear+1).'-'.substr($currentYear+2, 2)
        ];
        foreach($years as $fy) {
            $selected = ($hoa['FinancialYear'] == $fy) ? 'selected' : '';
            echo "<option value='$fy' $selected>$fy</option>";
        }
        ?>
    </select>
</div>


        <div class="col-md-4">
            <label class="form-label">Major Head</label>
            <input type="text" name="major" class="form-control"
                   value="<?= $hoa['MajorHead'] ?>">
        </div>

        <div class="col-md-4">
            <label class="form-label">Sub Major Head</label>
            <input type="text" name="submajor" class="form-control"
                   value="<?= $hoa['SubMajorHead'] ?>">
        </div>

        <div class="col-md-4">
            <label class="form-label">Minor Head</label>
            <input type="text" name="minor" class="form-control"
                   value="<?= $hoa['MinorHead'] ?>">
        </div>

        <div class="col-md-4">
            <label class="form-label">Sub Minor Head</label>
            <input type="text" name="subminor" class="form-control"
                   value="<?= $hoa['SubMinorHead'] ?>">
        </div>

        <div class="col-md-4">
            <label class="form-label">Detail Head</label>
            <input type="text" name="detail" class="form-control"
                   value="<?= $hoa['DetailHead'] ?>">
        </div>

        <div class="col-md-4">
            <label class="form-label">Object Head</label>
            <input type="text" name="object" class="form-control"
                   value="<?= $hoa['ObjectHead'] ?>">
        </div>

        <div class="col-md-12">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-control" rows="3"><?= $hoa['Description'] ?></textarea>
        </div>

    </div>

    <div class="mt-3">
        <button type="submit" class="btn btn-success">Update</button>
    </div>

</form>

<script>
$("#updateHOAForm").on("submit", function(e){
    e.preventDefault();

    $.ajax({
        url: "hoa_edit_ajax.php",
        type: "POST",
        data: $(this).serialize(),
        dataType: "json", // important!
        success: function(res){
            if(res.status === "success"){

                Swal.fire({
                    icon: "success",
                    title: "HOA Updated",
                    text: res.message,
                    timer: 1500,
                    showConfirmButton: false
                });

                $("#editHoaModal").modal("hide");

                // Reload DataTable row
                hoaTable.ajax.reload(null, false); 

            } else {
                Swal.fire("Error", res.message, "error");
            }
        },
        error: function(xhr, status, error){
            Swal.fire("Error", "Server error: " + error, "error");
        }
    });
});
</script>
