<?php require_once 'api.php'; ?>
<!DOCTYPE html>
<html lang="vi">

<head>
  <meta charset="UTF-8">
  <title><?php echo htmlspecialchars($fullname); ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="style.css">
</head>

<body>

  <div class="header">
    <img class="avatar" src="<?php echo htmlspecialchars($avatar); ?>" alt="Avatar">
    <h2><?php echo htmlspecialchars($fullname); ?></h2>
    <div class="uptext"><?php echo htmlspecialchars($membershipName); ?></div>
  </div>

  <div class="card">
    <div class="row">
      <div class="label">Số điện thoại</div>
      <div class="value uptext"><?php echo htmlspecialchars($phone); ?></div>
    </div>

    <div class="row">
      <div class="label">Gói hội viên Arena</div>
      <div class="value uptext"><?php echo htmlspecialchars($arenaMember); ?></div>
    </div>

    <div class="row">
      <div class="label">Ngày hết hạn</div>
      <div class="value uptext"><?php echo htmlspecialchars($arenaMemberExp); ?></div>
    </div>
  </div>

</body>

</html>