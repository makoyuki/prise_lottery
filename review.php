<?php
session_start();

// ログインチェック
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

// セッションの有効性をチェック
if (time() - $_SESSION['last_activity'] > 1800) {
    session_unset();
    session_destroy();
    header("Location: index.php");
    exit();
} else {
    $_SESSION['last_activity'] = time();
}

// 申込データの確認
if (!isset($_SESSION['lottery_data'])) {
    header("Location: index.php");
    exit();
}

// 賞品リストを読み込み
function loadPrizes() {
    $prizes = [];
    if (file_exists('prizes.csv')) {
        $file = fopen('prizes.csv', 'r');
        $header = fgetcsv($file);
        while (($row = fgetcsv($file)) !== FALSE) {
            $prizes[$row[0]] = $row[1];
        }
        fclose($file);
    }
    return $prizes;
}

$prizes = loadPrizes();
$lottery_data = $_SESSION['lottery_data'];
$username = htmlspecialchars($lottery_data['username']);
$choices = $lottery_data['choices'];
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>申込内容確認</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            line-height: 1.6; 
            max-width: 600px; 
            margin: 0 auto; 
            padding: 20px; 
        }
        .choice-item {
            background-color: #f0f0f0;
            padding: 10px;
            margin: 5px 0;
            border-radius: 5px;
        }
        input[type="submit"], button { 
            padding: 10px 15px; 
            margin: 10px 5px; 
            border: none;
            cursor: pointer;
            border-radius: 3px;
        }
        .submit-btn {
            background-color: #4CAF50;
            color: white;
        }
        .back-btn {
            background-color: #f44336;
            color: white;
        }
        .submit-btn:hover {
            background-color: #45a049;
        }
        .back-btn:hover {
            background-color: #da190b;
        }
    </style>
</head>
<body>
    <h1>申込内容の確認</h1>
    
    <p><strong>申込者:</strong> <?php echo $username; ?></p>
    
    <h3>希望賞品:</h3>
    <?php for ($i = 0; $i < count($choices); $i++): ?>
        <?php if (!empty($choices[$i])): ?>
            <div class="choice-item">
                <strong>第<?php echo ($i + 1); ?>希望:</strong> 
                <?php echo htmlspecialchars($prizes[$choices[$i]] ?? $choices[$i]); ?>
                (ID: <?php echo htmlspecialchars($choices[$i]); ?>)
            </div>
        <?php endif; ?>
    <?php endfor; ?>
    
    <p><strong>注意:</strong> 送信後は内容の変更ができません。よろしいですか？</p>
    
    <form method="post" action="thankyou.php">
        <input type="hidden" name="username" value="<?php echo $username; ?>">
        <?php for ($i = 0; $i < count($choices); $i++): ?>
            <input type="hidden" name="choice_<?php echo ($i + 1); ?>" value="<?php echo htmlspecialchars($choices[$i]); ?>">
        <?php endfor; ?>
        
        <input type="submit" value="この内容で申し込む" class="submit-btn">
    </form>
    
    <button onclick="history.back()" class="back-btn">戻って修正する</button>
</body>
</html>
