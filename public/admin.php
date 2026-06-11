<?php
session_start();
$db = new SQLite3('/opt/gurufix/db/gurufix.sqlite');
$db->enableExceptions(true);

// Логин
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $stmt = $db->prepare("SELECT password_hash FROM admin_users WHERE username = :user");
    $stmt->bindValue(':user', $username, SQLITE3_TEXT);
    $result = $stmt->execute();
    $row = $result->fetchArray(SQLITE3_ASSOC);
    if ($row && password_verify($password, $row['password_hash'])) {
        $_SESSION['admin'] = $username;
        header('Location: admin.php');
        exit;
    } else $error = "Неверный логин или пароль";
}
if (isset($_GET['logout'])) { session_destroy(); header('Location: admin.php'); exit; }
if (!isset($_SESSION['admin'])) {
    ?><!DOCTYPE html><html lang="ru"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Вход</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"><style>body{background:linear-gradient(135deg,#1a1a2e 0%,#16213e 100%);min-height:100vh;display:flex;align-items:center}</style></head><body><div class="container"><div class="row justify-content-center"><div class="col-md-5"><div class="card border-0 shadow-lg"><div class="card-body p-5"><h3 class="text-center mb-4">Вход</h3><?php if(isset($error)) echo "<div class='alert alert-danger'>$error</div>"; ?><form method="post"><input type="text" name="username" class="form-control mb-3" placeholder="Логин" required autofocus><input type="password" name="password" class="form-control mb-4" placeholder="Пароль" required><button type="submit" name="login" class="btn btn-primary w-100 py-2">Войти</button></form></div></div></div></div></div></body></html><?php exit;
}

// Обработка POST (CRUD)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['change_pass'])) { $new = trim($_POST['new_password']); if (!empty($new)) { $hash = password_hash($new, PASSWORD_DEFAULT); $stmt = $db->prepare("UPDATE admin_users SET password_hash = :hash WHERE username = :user"); $stmt->bindValue(':hash', $hash, SQLITE3_TEXT); $stmt->bindValue(':user', $_SESSION['admin'], SQLITE3_TEXT); $pass_msg = $stmt->execute() ? "<div class='alert alert-success'>Пароль изменён!</div>" : "<div class='alert alert-danger'>Ошибка записи</div>"; } else $pass_msg = "<div class='alert alert-danger'>Пароль не может быть пустым</div>"; }
    if (isset($_FILES['logo'])) { $uploadDir = __DIR__ . '/uploads/'; if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true); $fileName = 'logo_' . time() . '.png'; if (move_uploaded_file($_FILES['logo']['tmp_name'], $uploadDir . $fileName)) { $stmt = $db->prepare("UPDATE settings SET value = :url WHERE key = 'logo_url'"); $stmt->bindValue(':url', '/uploads/' . $fileName, SQLITE3_TEXT); $stmt->execute(); $logo_ok = true; } else $logo_error = true; }
    if (isset($_FILES['favicon'])) { $uploadDir = __DIR__ . '/uploads/'; if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true); $ext = pathinfo($_FILES['favicon']['name'], PATHINFO_EXTENSION); $fileName = 'favicon_' . time() . '.' . $ext; if (move_uploaded_file($_FILES['favicon']['tmp_name'], $uploadDir . $fileName)) { $stmt = $db->prepare("UPDATE settings SET value = :url WHERE key = 'favicon_url'"); $stmt->bindValue(':url', '/uploads/' . $fileName, SQLITE3_TEXT); $stmt->execute(); $favicon_ok = true; } else $favicon_error = true; }
    if (isset($_FILES['service_icon']) && isset($_POST['service_id_edit'])) { $sid = (int)$_POST['service_id_edit']; $uploadDir = __DIR__ . '/uploads/'; if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true); $ext = pathinfo($_FILES['service_icon']['name'], PATHINFO_EXTENSION); $fname = 'service_' . $sid . '_' . time() . '.' . $ext; if (move_uploaded_file($_FILES['service_icon']['tmp_name'], $uploadDir . $fname)) { $stmt = $db->prepare("UPDATE services SET icon = :icon WHERE id = :id"); $stmt->bindValue(':icon', '/uploads/' . $fname, SQLITE3_TEXT); $stmt->bindValue(':id', $sid, SQLITE3_INTEGER); $stmt->execute(); $icon_ok = true; } }
    if (isset($_POST['add_service'])) { $stmt = $db->prepare("INSERT INTO services (title, description, sort_order) VALUES (:title, :desc, 0)"); $stmt->bindValue(':title', trim($_POST['title']), SQLITE3_TEXT); $stmt->bindValue(':desc', trim($_POST['description']), SQLITE3_TEXT); $stmt->execute(); }
    if (isset($_POST['update_service'])) { $stmt = $db->prepare("UPDATE services SET title = :title, description = :desc WHERE id = :id"); $stmt->bindValue(':title', trim($_POST['title']), SQLITE3_TEXT); $stmt->bindValue(':desc', trim($_POST['description']), SQLITE3_TEXT); $stmt->bindValue(':id', (int)$_POST['id'], SQLITE3_INTEGER); $stmt->execute(); }
    if (isset($_POST['delete_service'])) $db->exec("DELETE FROM services WHERE id = ".(int)$_POST['id']);
    if (isset($_POST['add_review'])) { $stmt = $db->prepare("INSERT INTO reviews (author, text, rating, is_published) VALUES (:author, :text, :rating, :pub)"); $stmt->bindValue(':author', trim($_POST['author']), SQLITE3_TEXT); $stmt->bindValue(':text', trim($_POST['text']), SQLITE3_TEXT); $stmt->bindValue(':rating', (int)$_POST['rating'], SQLITE3_INTEGER); $stmt->bindValue(':pub', isset($_POST['is_published'])?1:0, SQLITE3_INTEGER); $stmt->execute(); }
    if (isset($_POST['update_review'])) { $stmt = $db->prepare("UPDATE reviews SET author=:author, text=:text, rating=:rating, is_published=:pub WHERE id=:id"); $stmt->bindValue(':author', trim($_POST['author']), SQLITE3_TEXT); $stmt->bindValue(':text', trim($_POST['text']), SQLITE3_TEXT); $stmt->bindValue(':rating', (int)$_POST['rating'], SQLITE3_INTEGER); $stmt->bindValue(':pub', isset($_POST['is_published'])?1:0, SQLITE3_INTEGER); $stmt->bindValue(':id', (int)$_POST['id'], SQLITE3_INTEGER); $stmt->execute(); }
    if (isset($_POST['delete_review'])) $db->exec("DELETE FROM reviews WHERE id = ".(int)$_POST['id']);
    if (isset($_POST['save_settings'])) { foreach ($_POST as $k => $v) if (strpos($k, 'setting_') === 0) { $realKey = substr($k, 8); $stmt = $db->prepare("UPDATE settings SET value = :val WHERE key = :key"); $stmt->bindValue(':val', $v, SQLITE3_TEXT); $stmt->bindValue(':key', $realKey, SQLITE3_TEXT); $stmt->execute(); } $saved = true; }
    if (isset($_POST['add_page'])) { $slug = trim($_POST['slug']); $title = trim($_POST['title']); $content = trim($_POST['content']); $status = isset($_POST['status']) ? 1 : 0; $stmt = $db->prepare("INSERT INTO pages (slug, title, content, status, created_at) VALUES (:slug, :title, :content, :status, datetime('now'))"); $stmt->bindValue(':slug', $slug, SQLITE3_TEXT); $stmt->bindValue(':title', $title, SQLITE3_TEXT); $stmt->bindValue(':content', $content, SQLITE3_TEXT); $stmt->bindValue(':status', $status, SQLITE3_INTEGER); $stmt->execute(); }
    if (isset($_POST['update_page'])) { $id = (int)$_POST['page_id']; $slug = trim($_POST['slug']); $title = trim($_POST['title']); $content = trim($_POST['content']); $status = (int)$_POST['status']; $stmt = $db->prepare("UPDATE pages SET slug = :slug, title = :title, content = :content, status = :status, updated_at = datetime('now') WHERE id = :id"); $stmt->bindValue(':slug', $slug, SQLITE3_TEXT); $stmt->bindValue(':title', $title, SQLITE3_TEXT); $stmt->bindValue(':content', $content, SQLITE3_TEXT); $stmt->bindValue(':status', $status, SQLITE3_INTEGER); $stmt->bindValue(':id', $id, SQLITE3_INTEGER); $stmt->execute(); }
    if (isset($_POST['delete_page'])) { $id = (int)$_POST['page_id']; $db->exec("DELETE FROM pages WHERE id = $id"); }
}

// Получение данных
$settings = []; $res = $db->query("SELECT key, value FROM settings"); while ($row = $res->fetchArray(SQLITE3_ASSOC)) $settings[$row['key']] = $row['value'];
$services = []; $res2 = $db->query("SELECT * FROM services ORDER BY sort_order, id"); while ($row = $res2->fetchArray(SQLITE3_ASSOC)) $services[] = $row;
$reviews = []; $res3 = $db->query("SELECT * FROM reviews ORDER BY id DESC"); while ($row = $res3->fetchArray(SQLITE3_ASSOC)) $reviews[] = $row;
$pages = []; $res4 = $db->query("SELECT * FROM pages ORDER BY id"); while ($row = $res4->fetchArray(SQLITE3_ASSOC)) $pages[] = $row;
$leads = []; $res5 = $db->query("SELECT * FROM leads ORDER BY id DESC LIMIT 100"); while ($row = $res5->fetchArray(SQLITE3_ASSOC)) $leads[] = $row;
?>
<!DOCTYPE html>
<html lang="ru">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>GuruFix Admin</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
<script src="https://cdn.ckeditor.com/ckeditor5/41.4.2/classic/ckeditor.js"></script>
<style>body{background:#f0f2f5;font-family:'Segoe UI',system-ui}.card{border:none;border-radius:20px;box-shadow:0 5px 15px rgba(0,0,0,0.05);margin-bottom:25px}.card-header{background:white;border-bottom:1px solid #eef2f6;font-weight:600;border-radius:20px 20px 0 0!important}.btn{border-radius:12px;padding:6px 14px;font-weight:500}.table{background:white;border-radius:16px;overflow:hidden}.table th{background:#f8f9fc}.icon-preview{max-width:50px;border-radius:10px;border:1px solid #ddd;padding:2px}.collapse form{background:#f8f9fc;border-radius:16px;padding:20px;margin-top:10px}</style>
</head>
<body>
<div class="container py-4">
<div class="d-flex justify-content-between align-items-center mb-4"><h1 class="h3 mb-0"><i class="bi bi-speedometer2 me-2" style="color:#ff6600;"></i> GuruFix Admin</h1><a href="?logout=1" class="btn btn-outline-danger btn-sm"><i class="bi bi-box-arrow-right"></i> Выйти</a></div>
<?php if (isset($saved)) echo "<div class='alert alert-success'>Настройки сохранены</div>"; if (isset($pass_msg)) echo $pass_msg; if (isset($logo_ok)) echo "<div class='alert alert-success'>Логотип обновлён</div>"; if (isset($logo_error)) echo "<div class='alert alert-danger'>Ошибка загрузки логотипа</div>"; if (isset($favicon_ok)) echo "<div class='alert alert-success'>Favicon обновлён</div>"; if (isset($favicon_error)) echo "<div class='alert alert-danger'>Ошибка загрузки favicon</div>"; if (isset($icon_ok)) echo "<div class='alert alert-success'>Иконка загружена</div>"; ?>

<div class="card"><div class="card-header">Настройки сайта</div><div class="card-body">
<form method="post" enctype="multipart/form-data" class="mb-4 p-3 bg-light rounded">
<div class="row align-items-end"><div class="col-md-4"><label>Логотип</label><input type="file" name="logo" class="form-control" accept="image/*"></div><div class="col-md-4"><label>Favicon</label><input type="file" name="favicon" class="form-control" accept="image/x-icon,image/png"></div><div class="col-md-4"><button type="submit" class="btn btn-secondary w-100"><i class="bi bi-upload"></i> Загрузить</button></div></div>
<?php if (!empty($settings['logo_url'])): ?><div class="mt-2"><img src="<?=htmlspecialchars($settings['logo_url'])?>" style="max-height:60px" class="border rounded p-1"></div><?php endif; ?>
<?php if (!empty($settings['favicon_url'])): ?><div class="mt-2"><img src="<?=htmlspecialchars($settings['favicon_url'])?>" style="height:32px"></div><?php endif; ?>
</form>
<form method="post"><div class="row">
<?php foreach ($settings as $k => $v): ?>
<div class="col-md-6 mb-3"><label class="fw-semibold"><?=htmlspecialchars($k)?></label><input type="text" name="setting_<?=htmlspecialchars($k)?>" value="<?=htmlspecialchars($v)?>" class="form-control"></div>
<?php endforeach; ?>
<div class="col-md-6 mb-3"><label>Телефон</label><input type="text" name="setting_phone" value="<?=htmlspecialchars($settings['phone']??'')?>" class="form-control"></div>
<div class="col-md-6 mb-3"><label>Email</label><input type="email" name="setting_email" value="<?=htmlspecialchars($settings['email']??'')?>" class="form-control"></div>
<div class="col-md-12 mb-3"><label>Адрес</label><input type="text" name="setting_address" value="<?=htmlspecialchars($settings['address']??'')?>" class="form-control"></div>
<div class="col-md-4 mb-3"><label>Facebook</label><input type="text" name="setting_facebook" value="<?=htmlspecialchars($settings['facebook']??'')?>" class="form-control"></div>
<div class="col-md-4 mb-3"><label>Instagram</label><input type="text" name="setting_instagram" value="<?=htmlspecialchars($settings['instagram']??'')?>" class="form-control"></div>
<div class="col-md-4 mb-3"><label>Telegram</label><input type="text" name="setting_telegram" value="<?=htmlspecialchars($settings['telegram']??'')?>" class="form-control"></div>
</div><button type="submit" name="save_settings" class="btn btn-primary"><i class="bi bi-save"></i> Сохранить настройки</button></form>
</div></div>

<div class="card"><div class="card-header">Услуги (перетаскивайте строки для сортировки)</div><div class="card-body">
<form method="post" class="row g-3 mb-4 p-3 bg-light rounded"><div class="col-md-5"><input type="text" name="title" class="form-control" placeholder="Название услуги" required></div><div class="col-md-5"><textarea name="description" class="form-control" placeholder="Описание"></textarea></div><div class="col-md-2"><button type="submit" name="add_service" class="btn btn-success w-100">Добавить</button></div></form>
<div class="table-responsive"><table class="table align-middle" id="servicesTable"><thead><tr><th>ID</th><th>Иконка</th><th>Название</th><th>Описание</th><th>Действие</th></tr></thead><tbody>
<?php foreach ($services as $s): ?>
<tr data-id="<?=$s['id']?>"><td><?=$s['id']?></td><td><?= $s['icon'] ? "<img src='".htmlspecialchars($s['icon'])."' class='icon-preview'>" : '—' ?></td><td><?=htmlspecialchars($s['title'])?></td><td><?=htmlspecialchars($s['description'])?></td><td><button class="btn btn-sm btn-info" data-bs-toggle="collapse" data-bs-target="#editService<?=$s['id']?>"><i class="bi bi-pencil"></i> Ред.</button> <form method="post" style="display:inline" onsubmit="return confirm('Удалить услугу?')"><input type="hidden" name="id" value="<?=$s['id']?>"><button type="submit" name="delete_service" class="btn btn-sm btn-danger"><i class="bi bi-trash"></i></button></form></td></tr>
<tr class="collapse" id="editService<?=$s['id']?>"><td colspan="5"><form method="post" enctype="multipart/form-data"><input type="hidden" name="id" value="<?=$s['id']?>"><div class="row"><div class="col-md-4"><input type="text" name="title" value="<?=htmlspecialchars($s['title'])?>" class="form-control"></div><div class="col-md-4"><textarea name="description" class="form-control"><?=htmlspecialchars($s['description'])?></textarea></div><div class="col-md-4"><input type="file" name="service_icon" accept="image/*" class="form-control"><button type="submit" name="update_service" class="btn btn-primary mt-2">Обновить</button></div></div></form></td></tr>
<?php endforeach; ?>
</tbody></table></div>
</div></div>

<div class="card"><div class="card-header">Отзывы</div><div class="card-body">
<form method="post" class="row g-3 mb-4 p-3 bg-light rounded"><div class="col-md-3"><input type="text" name="author" class="form-control" placeholder="Автор" required></div><div class="col-md-4"><textarea name="text" class="form-control" placeholder="Текст отзыва" required></textarea></div><div class="col-md-2"><select name="rating" class="form-select"><option value="5">5★</option><option value="4">4★</option><option value="3">3★</option><option value="2">2★</option><option value="1">1★</option></select></div><div class="col-md-2"><div class="form-check mt-2"><input class="form-check-input" type="checkbox" name="is_published" checked> <label>Опубликован</label></div></div><div class="col-md-1"><button type="submit" name="add_review" class="btn btn-warning w-100">Добавить</button></div></form>
<div class="table-responsive"><table class="table align-middle"><thead><tr><th>ID</th><th>Автор</th><th>Текст</th><th>Рейтинг</th><th>Опубл.</th><th>Действие</th></tr></thead><tbody>
<?php foreach ($reviews as $r): ?>
<tr><td><?=$r['id']?></td><td><?=htmlspecialchars($r['author'])?></td><td><?=htmlspecialchars($r['text'])?></td><td><?=$r['rating']?></td><td><?=$r['is_published']?'Да':'Нет'?></td><td><button class="btn btn-sm btn-info" data-bs-toggle="collapse" data-bs-target="#editReview<?=$r['id']?>"><i class="bi bi-pencil"></i> Ред.</button> <form method="post" style="display:inline" onsubmit="return confirm('Удалить отзыв?')"><input type="hidden" name="id" value="<?=$r['id']?>"><button type="submit" name="delete_review" class="btn btn-sm btn-danger"><i class="bi bi-trash"></i></button></form></td></tr>
<tr class="collapse" id="editReview<?=$r['id']?>"><td colspan="6"><form method="post"><input type="hidden" name="id" value="<?=$r['id']?>"><div class="row"><div class="col-md-3"><input type="text" name="author" value="<?=htmlspecialchars($r['author'])?>" class="form-control"></div><div class="col-md-4"><textarea name="text" class="form-control"><?=htmlspecialchars($r['text'])?></textarea></div><div class="col-md-2"><select name="rating" class="form-select"><option value="5" <?=$r['rating']==5?'selected':''?>>5★</option><option value="4" <?=$r['rating']==4?'selected':''?>>4★</option><option value="3" <?=$r['rating']==3?'selected':''?>>3★</option><option value="2" <?=$r['rating']==2?'selected':''?>>2★</option><option value="1" <?=$r['rating']==1?'selected':''?>>1★</option></select></div><div class="col-md-2"><div class="form-check mt-2"><input class="form-check-input" type="checkbox" name="is_published" value="1" <?=$r['is_published']?'checked':''?>> <label>Опубл.</label></div></div><div class="col-md-1"><button type="submit" name="update_review" class="btn btn-primary">Обновить</button></div></div></form></td></tr>
<?php endforeach; ?>
</tbody></table></div>
</div></div>

<div class="card"><div class="card-header">Страницы сайта (с визуальным редактором CKEditor)</div><div class="card-body">
<form method="post" class="row g-3 mb-4 p-3 bg-light rounded"><div class="col-md-2"><input type="text" name="slug" class="form-control" placeholder="slug (например, about)" required></div><div class="col-md-3"><input type="text" name="title" class="form-control" placeholder="Заголовок" required></div><div class="col-md-5"><textarea id="contentAdd" name="content" class="form-control" placeholder="HTML-содержимое" rows="3"></textarea></div><div class="col-md-1"><div class="form-check mt-2"><input class="form-check-input" type="checkbox" name="status" value="1" checked> <label>Опубл.</label></div></div><div class="col-md-1"><button type="submit" name="add_page" class="btn btn-info w-100">Добавить</button></div></form>
<div class="table-responsive"><table class="table align-middle"><thead><tr><th>ID</th><th>Slug</th><th>Заголовок</th><th>Статус</th><th>Действие</th></tr></thead><tbody>
<?php foreach ($pages as $p): ?>
<tr><td><?=$p['id']?></td><td><?=htmlspecialchars($p['slug'])?></td><td><?=htmlspecialchars($p['title'])?></td><td><?=$p['status']?'Опубликована':'Черновик'?></td><td><button class="btn btn-sm btn-warning" data-bs-toggle="collapse" data-bs-target="#editPage<?=$p['id']?>"><i class="bi bi-pencil"></i> Ред.</button> <form method="post" style="display:inline" onsubmit="return confirm('Удалить страницу?')"><input type="hidden" name="page_id" value="<?=$p['id']?>"><button type="submit" name="delete_page" class="btn btn-sm btn-danger"><i class="bi bi-trash"></i></button></form></td></tr>
<tr class="collapse" id="editPage<?=$p['id']?>"><td colspan="5"><form method="post"><input type="hidden" name="page_id" value="<?=$p['id']?>"><div class="row"><div class="col-md-2"><input type="text" name="slug" value="<?=htmlspecialchars($p['slug'])?>" class="form-control"></div><div class="col-md-3"><input type="text" name="title" value="<?=htmlspecialchars($p['title'])?>" class="form-control"></div><div class="col-md-5"><textarea id="editContent<?=$p['id']?>" name="content" class="form-control" rows="3"><?=htmlspecialchars($p['content'])?></textarea></div><div class="col-md-2"><select name="status" class="form-select"><option value="1" <?=$p['status']==1?'selected':''?>>Опубликована</option><option value="0" <?=$p['status']==0?'selected':''?>>Черновик</option></select><button type="submit" name="update_page" class="btn btn-primary mt-2">Обновить</button></div></div></form></td></tr>
<?php endforeach; ?>
</tbody></table></div>
</div></div>

<div class="card"><div class="card-header">Заявки (последние 100)</div><div class="card-body"><div class="table-responsive"><table class="table table-striped"><thead><tr><th>ID</th><th>Имя</th><th>Телефон</th><th>Услуга ID</th><th>Сообщение</th><th>Дата</th></table></thead><tbody>
<?php foreach ($leads as $lead): ?>
<tr><td><?=$lead['id']?></td><td><?=htmlspecialchars($lead['name'])?></td><td><?=htmlspecialchars($lead['phone'])?></td><td><?=$lead['service_id']?></td><td><?=htmlspecialchars($lead['message'])?></td><td><?=$lead['created_at']?></td></tr>
<?php endforeach; ?>
</tbody></table></div></div></div>

<div class="card"><div class="card-header">Сменить пароль</div><div class="card-body"><form method="post" class="row g-3"><div class="col-md-4"><input type="password" name="new_password" class="form-control" placeholder="Новый пароль" required autocomplete="off"></div><div class="col-md-2"><button type="submit" name="change_pass" class="btn btn-danger"><i class="bi bi-key"></i> Сменить</button></div></form></div></div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
  if(document.getElementById("contentAdd")) {
    ClassicEditor.create(document.getElementById("contentAdd")).catch(error=>console.error(error));
  }
  <?php foreach ($pages as $p): ?>
    if(document.getElementById("editContent<?=$p['id']?>")) {
      ClassicEditor.create(document.getElementById("editContent<?=$p['id']?>")).catch(error=>console.error(error));
    }
  <?php endforeach; ?>
});
var servicesBody = document.querySelector("#servicesTable tbody");
if(servicesBody) { new Sortable(servicesBody, { animation: 150, onEnd: function() { updateServicesOrder(); } }); }
function updateServicesOrder() {
    var rows = servicesBody.querySelectorAll("tr");
    var order = [];
    rows.forEach(function(row) { order.push(row.dataset.id); });
    fetch("/api/admin/services/order", { method: "POST", headers: { "Content-Type": "application/json" }, body: JSON.stringify({ order: order }) });
}
</script>
</body></html>
