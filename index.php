<?php
// ===== Kết nối CSDL =====
$conn = new mysqli("localhost", "root", "", "QLHocPhan");
$conn->set_charset("utf8mb4");
if ($conn->connect_error) die("Lỗi kết nối: " . $conn->connect_error);

$uploadDir = __DIR__ . "/uploads/";
if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

// Xử lý upload ảnh, trả về đường dẫn hoặc null
function uploadImage($uploadDir) {
    if (!empty($_FILES["image"]["name"]) && $_FILES["image"]["error"] === 0) {
        $ext = strtolower(pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION));
        if (in_array($ext, ["jpg", "jpeg", "png", "gif", "webp"])) {
            $name = uniqid("gv_") . "." . $ext;
            if (move_uploaded_file($_FILES["image"]["tmp_name"], $uploadDir . $name))
                return "uploads/" . $name;
        }
    }
    return null;
}

// ===== Thêm / Sửa / Xóa =====
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action   = $_POST["action"] ?? "";
    $idgv     = trim($_POST["idgv"] ?? "");      // Mã giảng viên do người dùng nhập
    $oldId    = trim($_POST["old_idgv"] ?? "");  // Mã cũ (dùng khi sửa)
    $fullname = trim($_POST["fullname"] ?? "");
    $sum      = (int)($_POST["sumclass"] ?? 0);
    $img      = uploadImage($uploadDir);
    $error    = "";

    if ($action === "add" && $idgv !== "" && $fullname !== "") {
        $chk = $conn->prepare("SELECT 1 FROM GiangVien WHERE IDGV=?");
        $chk->bind_param("s", $idgv);
        $chk->execute();
        if ($chk->get_result()->num_rows > 0) {
            $error = "Mã giảng viên '$idgv' đã tồn tại, vui lòng nhập mã khác.";
        } else {
            $stmt = $conn->prepare("INSERT INTO GiangVien (IDGV, Fullname, Image, SumClass) VALUES (?,?,?,?)");
            $stmt->bind_param("sssi", $idgv, $fullname, $img, $sum);
            $stmt->execute();
        }
    } elseif ($action === "edit" && $idgv !== "" && $fullname !== "") {
        if ($idgv !== $oldId) {
            $chk = $conn->prepare("SELECT 1 FROM GiangVien WHERE IDGV=?");
            $chk->bind_param("s", $idgv);
            $chk->execute();
            if ($chk->get_result()->num_rows > 0) {
                $error = "Mã giảng viên '$idgv' đã tồn tại, vui lòng nhập mã khác.";
            }
        }
        if ($error === "") {
            if ($img) {
                $stmt = $conn->prepare("UPDATE GiangVien SET IDGV=?, Fullname=?, Image=?, SumClass=? WHERE IDGV=?");
                $stmt->bind_param("sssis", $idgv, $fullname, $img, $sum, $oldId);
            } else {
                $stmt = $conn->prepare("UPDATE GiangVien SET IDGV=?, Fullname=?, SumClass=? WHERE IDGV=?");
                $stmt->bind_param("ssis", $idgv, $fullname, $sum, $oldId);
            }
            $stmt->execute();
        }
    } elseif ($action === "delete") {
        $stmt = $conn->prepare("DELETE FROM GiangVien WHERE IDGV=?");
        $stmt->bind_param("s", $idgv);
        $stmt->execute();
    }
    if ($error !== "") {
        header("Location: index.php?error=" . urlencode($error) . ($action === "edit" ? "&edit=" . urlencode($oldId) : ""));
    } else {
        header("Location: index.php");
    }
    exit;
}

// ===== Lấy dữ liệu =====
$editRow = null;
if (isset($_GET["edit"])) {
    $stmt = $conn->prepare("SELECT * FROM GiangVien WHERE IDGV=?");
    $stmt->bind_param("s", $_GET["edit"]);
    $stmt->execute();
    $editRow = $stmt->get_result()->fetch_assoc();
}
$errorMsg = $_GET["error"] ?? "";
$list = $conn->query("SELECT * FROM GiangVien ORDER BY IDGV ASC");
function e($s) { return htmlspecialchars((string)$s, ENT_QUOTES, "UTF-8"); }
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Quản lý Giảng viên</title>
<style>
  * { box-sizing: border-box; }
  body { font-family: "Segoe UI", Arial, sans-serif; background: #f2f5fa; margin: 0; color: #222; }
  header { background: linear-gradient(135deg, #2563eb, #7c3aed); color: #fff; padding: 22px 16px; text-align: center; }
  header h1 { margin: 0; font-size: 1.6rem; }
  .container { max-width: 1000px; margin: 24px auto; padding: 0 16px; }
  .card { background: #fff; border-radius: 12px; padding: 20px; box-shadow: 0 2px 10px rgba(0,0,0,.08); margin-bottom: 24px; }
  .card h2 { margin-top: 0; font-size: 1.2rem; }
  form .row { display: flex; flex-wrap: wrap; gap: 14px; }
  .field { flex: 1 1 220px; display: flex; flex-direction: column; }
  label { font-weight: 600; margin-bottom: 4px; font-size: .9rem; }
  input[type=text], input[type=number], input[type=file] { padding: 9px 10px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 1rem; }
  .btn { padding: 9px 16px; border: 0; border-radius: 8px; cursor: pointer; font-size: .95rem; color: #fff; text-decoration: none; display: inline-block; }
  .btn-primary { background: #2563eb; } .btn-warning { background: #f59e0b; }
  .btn-danger { background: #dc2626; } .btn-gray { background: #64748b; }
  .actions { margin-top: 16px; display: flex; gap: 8px; }
  .table-wrap { overflow-x: auto; }
  table { width: 100%; border-collapse: collapse; min-width: 560px; }
  th, td { padding: 10px; text-align: left; border-bottom: 1px solid #e5e7eb; }
  th { background: #eef2ff; }
  td img { width: 56px; height: 56px; object-fit: cover; border-radius: 50%; }
  .no-img { width: 56px; height: 56px; border-radius: 50%; background: #e2e8f0; display: flex; align-items: center; justify-content: center; color: #94a3b8; font-size: .7rem; }
  td form { display: inline; }
  @media (max-width: 600px) { header h1 { font-size: 1.25rem; } }
</style>
</head>
<body>
<header><h1>QLHocPhan – Quản lý Giảng viên</h1></header>
<div class="container">

  <!-- Form thêm / sửa -->
  <div class="card">
    <h2><?= $editRow ? "Sửa giảng viên " . e($editRow["IDGV"]) : "Thêm giảng viên mới" ?></h2>
    <?php if ($errorMsg): ?><div style="background:#fee2e2;color:#991b1b;padding:10px 12px;border-radius:8px;margin-bottom:14px;"><?= e($errorMsg) ?></div><?php endif; ?>
    <form method="post" enctype="multipart/form-data">
      <input type="hidden" name="action" value="<?= $editRow ? "edit" : "add" ?>">
      <?php if ($editRow): ?><input type="hidden" name="old_idgv" value="<?= e($editRow["IDGV"]) ?>"><?php endif; ?>
      <div class="row">
        <div class="field">
          <label>Mã giảng viên (IDGV)</label>
          <input type="text" name="idgv" required maxlength="20" placeholder="VD: GV01" value="<?= e($editRow["IDGV"] ?? "") ?>">
        </div>
        <div class="field">
          <label>Họ và tên</label>
          <input type="text" name="fullname" required value="<?= e($editRow["Fullname"] ?? "") ?>">
        </div>
        <div class="field">
          <label>Tổng số lớp</label>
          <input type="number" name="sumclass" min="0" required value="<?= e($editRow["SumClass"] ?? 0) ?>">
        </div>
        <div class="field">
          <label>Hình ảnh <?= $editRow ? "(bỏ trống nếu giữ ảnh cũ)" : "" ?></label>
          <input type="file" name="image" accept="image/*">
        </div>
      </div>
      <div class="actions">
        <button class="btn btn-primary" type="submit"><?= $editRow ? "Cập nhật" : "Thêm mới" ?></button>
        <?php if ($editRow): ?><a class="btn btn-gray" href="index.php">Hủy</a><?php endif; ?>
      </div>
    </form>
  </div>

  <!-- Bảng hiển thị -->
  <div class="card">
    <h2>Danh sách giảng viên</h2>
    <div class="table-wrap">
      <table>
        <thead><tr><th>IDGV</th><th>Hình ảnh</th><th>Họ và tên</th><th>Tổng số lớp</th><th>Thao tác</th></tr></thead>
        <tbody>
        <?php while ($r = $list->fetch_assoc()): ?>
          <tr>
            <td><?= e($r["IDGV"]) ?></td>
            <td><?php if ($r["Image"]): ?><img src="<?= e($r["Image"]) ?>" alt=""><?php else: ?><div class="no-img">Không ảnh</div><?php endif; ?></td>
            <td><?= e($r["Fullname"]) ?></td>
            <td><?= e($r["SumClass"]) ?></td>
            <td>
              <a class="btn btn-warning" href="?edit=<?= e($r["IDGV"]) ?>">Sửa</a>
              <form method="post" onsubmit="return confirm('Bạn chắc chắn muốn xóa giảng viên này?')">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="idgv" value="<?= e($r["IDGV"]) ?>">
                <button class="btn btn-danger" type="submit">Xóa</button>
              </form>
            </td>
          </tr>
        <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
</body>
</html>
