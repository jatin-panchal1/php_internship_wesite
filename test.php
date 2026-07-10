<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['test'])) {
    $upload_dir = __DIR__ . '/uploads/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true); // creates recursively
}
$target = __DIR__ . '/uploads/' . basename($_FILES['test']['name']);
    if (move_uploaded_file($_FILES['test']['tmp_name'], $target)) {
        echo "Upload successful!";
    } else {
        echo "Move failed. Error: " . $_FILES['test']['error'];
    }
    exit;
}
?>
<form method="post" enctype="multipart/form-data">
    <input type="file" name="test">
    <button type="submit">Upload</button>
</form>