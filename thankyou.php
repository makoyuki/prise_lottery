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

$username = $_POST['username'];
$choices = [
    $_POST['choice_1'] ?? '',
    $_POST['choice_2'] ?? '',
    $_POST['choice_3'] ?? '',
    $_POST['choice_4'] ?? '',
    $_POST['choice_5'] ?? ''
];

// applicants.csv形式で保存
$csv_data = [
    $username, // applicant_id
    $username, // name（ここでは同じ値を使用）
    $choices[0], // choice_1
    $choices[1], // choice_2  
    $choices[2], // choice_3
    $choices[3], // choice_4
    $choices[4], // choice_5
    date('Y-m-d H:i:s') // 申込日時（参考用）
];

// ファイルが存在しない場合はヘッダーを作成
if (!file_exists('applicants.csv')) {
    $header_file = fopen('applicants.csv', 'w');
    fputcsv($header_file, ['applicant_id', 'name', 'choice_1', 'choice_2', 'choice_3', 'choice_4', 'choice_5', 'applied_at']);
    fclose($header_file);
}

// データを追記
$file = fopen('applicants.csv', 'a');
fputcsv($file, $csv_data);
fclose($file);

// セッションを破棄（ログアウト）
session_destroy();
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>申込完了</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            line-height: 1.6; 
            max-width: 600px; 
            margin: 0 auto; 
            padding: 20px; 
            text-align: center;
        }
        button { 
            padding: 10px 15px; 
            margin-top: 10px; 
            background-color: #4CAF50;
            color: white;
            border: none;
            cursor: pointer;
            border-radius: 3px;
        }
        button:hover {
            background-color: #45a049;
        }
        .success-message {
            background-color: #dff0d8;
            border: 1px solid #d6e9c6;
            color: #3c763d;
            padding: 20px;
            border-radius: 5px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <h1>🎉 申込完了 🎉</h1>
    
    <div class="success-message">
        <p><strong><?php echo htmlspecialchars($username); ?>さん</strong>の抽選申込を受け付けました。</p>
        <p>申込日時: <?php echo date('Y年m月d日 H:i'); ?></p>
    </div>
    
    <h3>今後の予定</h3>
    <ul style="text-align: left; max-width: 400px; margin: 0 auto;">
        <li>申込期間: 2024年XX月XX日まで</li>
        <li>抽選実施: 2024年XX月XX日</li>
        <li>結果発表: 2024年XX月XX日</li>
        <li>賞品配布: 2024年XX月XX日〜</li>
    </ul>
    
    <p>抽選結果は個別にご連絡いたします。</p>
    
    <button onclick="window.close();">ウィンドウを閉じる</button>
</body>
</html>
