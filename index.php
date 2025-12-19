<?php
session_start();

// LDAPサーバの設定
$ldap_server = "ldap://ldapserveraddres.example.jp";
$ldap_dn = "ou=People,dc=example,dc=jp";
$ldap_user = "uid=%s,$ldap_dn";

$message = "";
$username = "";

// 賞品リストを読み込み
function loadPrizes() {
    $prizes = [];
    if (file_exists('prizes.csv')) {
        $file = fopen('prizes.csv', 'r');
        $header = fgetcsv($file); // ヘッダー行をスキップ
        while (($row = fgetcsv($file)) !== FALSE) {
            $prizes[$row[0]] = $row[1];
        }
        fclose($file);
    }
    return $prizes;
}

$prizes = loadPrizes();

// ログイン処理
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $ldap_connection = ldap_connect($ldap_server);
    ldap_set_option($ldap_connection, LDAP_OPT_PROTOCOL_VERSION, 3);

    if (ldap_bind($ldap_connection, sprintf($ldap_user, $username), $password)) {
        $message = "ログイン成功: " . htmlspecialchars($username);
        $_SESSION['username'] = $username;
        $_SESSION['last_activity'] = time();
    } else {
        $message = "ログイン失敗";
    }
} elseif ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['lottery_form'])) {
    // 抽選申込処理
    if (isset($_SESSION['username'])) {
        $username = $_SESSION['username'];
        $choices = [
            $_POST['choice_1'],
            $_POST['choice_2'],
            $_POST['choice_3'],
            $_POST['choice_4'],
            $_POST['choice_5']
        ];
        
        // 重複チェック
        $unique_choices = array_unique(array_filter($choices));
        if (count($unique_choices) != count(array_filter($choices))) {
            $message = "エラー: 同じ賞品を重複して選択することはできません";
        } else {
            // 申込データをセッションに保存
            $_SESSION['lottery_data'] = [
                'username' => $username,
                'choices' => $choices
            ];
            header("Location: review.php");
            exit();
        }
    }
}

// セッションの有効性チェック
if (isset($_SESSION['username'])) {
    if (time() - $_SESSION['last_activity'] > 1800) {
        session_unset();
        session_destroy();
    } else {
        $_SESSION['last_activity'] = time();
    }
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>抽選申込フォーム</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            line-height: 1.6; 
            max-width: 800px; 
            margin: 0 auto; 
            padding: 20px; 
        }
        label { 
            display: block; 
            margin-bottom: 10px; 
            font-weight: bold;
        }
        input[type="text"], input[type="password"], select { 
            width: 100%; 
            padding: 8px; 
            margin-bottom: 10px; 
        }
        select {
            height: 40px;
        }
        input[type="submit"], button { 
            padding: 10px 15px; 
            margin-top: 10px; 
            background-color: #4CAF50;
            color: white;
            border: none;
            cursor: pointer;
        }
        input[type="submit"]:hover, button:hover {
            background-color: #45a049;
        }
        .choice-section {
            background-color: #f9f9f9;
            padding: 15px;
            margin: 10px 0;
            border-radius: 5px;
        }
        .error {
            color: red;
            font-weight: bold;
        }
        .success {
            color: green;
            font-weight: bold;
        }
    </style>
    <script>
        function validateChoices() {
            var choices = [];
            var selects = document.querySelectorAll('select[name^="choice_"]');
            
            for (var i = 0; i < selects.length; i++) {
                var value = selects[i].value;
                if (value && choices.includes(value)) {
                    alert('同じ賞品を重複して選択することはできません');
                    return false;
                }
                if (value) {
                    choices.push(value);
                }
            }
            
            if (choices.length === 0) {
                alert('少なくとも第1希望は選択してください');
                return false;
            }
            
            return true;
        }
        
        function updateChoices(changedSelect) {
            var selects = document.querySelectorAll('select[name^="choice_"]');
            var selectedValues = [];
            
            // 現在選択されている値を収集
            for (var i = 0; i < selects.length; i++) {
                if (selects[i].value) {
                    selectedValues.push(selects[i].value);
                }
            }
            
            // 各selectの選択肢を更新
            for (var i = 0; i < selects.length; i++) {
                var currentValue = selects[i].value;
                var options = selects[i].querySelectorAll('option');
                
                for (var j = 1; j < options.length; j++) { // 最初の空オプションをスキップ
                    var optionValue = options[j].value;
                    if (selectedValues.includes(optionValue) && optionValue !== currentValue) {
                        options[j].style.display = 'none';
                    } else {
                        options[j].style.display = 'block';
                    }
                }
            }
        }
    </script>
</head>
<body>
    <h1>抽選申込フォーム</h1>
    
    <?php if (!isset($_SESSION['username'])): ?>
        <h2>ログイン</h2>
        <form method="post">
            <label for="username">ユーザー名:</label>
            <input type="text" name="username" required>
            
            <label for="password">パスワード:</label>
            <input type="password" name="password" required>
            
            <input type="submit" name="login" value="ログイン">
        </form>
        
    <?php else: ?>
        <h2>こんにちは, <?php echo htmlspecialchars($_SESSION['username']); ?> さん</h2>
        <p>希望する賞品を第1希望から第5希望まで選択してください。（重複選択不可）</p>
        
        <form method="post" onsubmit="return validateChoices()">
            <?php for ($i = 1; $i <= 5; $i++): ?>
                <div class="choice-section">
                    <label for="choice_<?php echo $i; ?>">第<?php echo $i; ?>希望:</label>
                    <select name="choice_<?php echo $i; ?>" onchange="updateChoices(this)" <?php echo $i == 1 ? 'required' : ''; ?>>
                        <option value="">-- 選択してください --</option>
                        <?php foreach ($prizes as $prize_id => $prize_name): ?>
                            <option value="<?php echo htmlspecialchars($prize_id); ?>">
                                <?php echo htmlspecialchars($prize_name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endfor; ?>
            
            <p><strong>注意事項:</strong></p>
            <ul>
                <li>第1希望は必須です</li>
                <li>同じ賞品を重複して選択することはできません</li>
                <li>抽選は第1希望から順番に実施されます</li>
                <li>一度当選した方は、以降の希望での抽選対象外となります</li>
            </ul>
            
            <input type="submit" name="lottery_form" value="申込内容を確認">
        </form>
    <?php endif; ?>
    
    <?php if ($message): ?>
        <p class="<?php echo strpos($message, 'エラー') !== false ? 'error' : 'success'; ?>">
            <?php echo $message; ?>
        </p>
    <?php endif; ?>
</body>
</html>
