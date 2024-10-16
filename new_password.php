<?php
require('./php/conectar.php');
require("./php/functions.php");

// Inicializar variáveis para mensagens de erro e sucesso
$errorMessage = '';
$successMessage = '';

// Verificar se o usuário está autenticado para redefinição de senha
session_start();
if (!isset($_SESSION['resetUserID'])) {
    header('Location: ../login.php'); 
    exit();
}

// Obter a dica de senha atual
$userID = $_SESSION['resetUserID'];
$sql = "SELECT dicaSenha FROM gerenciadorsenhas.users WHERE userID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userID);
$stmt->execute();
$stmt->bind_result($dicaSenhaAtual);
$stmt->fetch();
$stmt->close();

// Processar a redefinição de senha
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $newPassword = $_POST['newPassword'];
    $confirmPassword = $_POST['confirmPassword'];
    $dicaSenha = $_POST['dicaSenha'] ?: $dicaSenhaAtual; // Se não preencher, usar a dica atual

    // Validar as senhas
    if (empty($newPassword) || empty($confirmPassword)) {
        $errorMessage = 'As senhas são obrigatórias.';
    } elseif ($newPassword !== $confirmPassword) {
        $errorMessage = 'As senhas não correspondem.';
    } else {
        // Hash da nova senha
        $hashedPassword = md5($newPassword);

        // Atualizar a senha e a dica no banco de dados
        $sql = "UPDATE gerenciadorsenhas.users SET userPassword = ?, dicaSenha = ? WHERE userID = ?";
        
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("ssi", $hashedPassword, $dicaSenha, $userID);
            if ($stmt->execute()) {
                // Deletar o código utilizado
                $deleteCodeSql = "DELETE FROM verification_codes WHERE user_id = ?";
                $deleteStmt = $conn->prepare($deleteCodeSql);
                $deleteStmt->bind_param("i", $userID);
                $deleteStmt->execute();
                $deleteStmt->close();

// Obter o nome do usuário com base no userID se não estiver na sessão
if (!isset($_SESSION['userNome'])) {
    $userID = $_SESSION['resetUserID'];
    
    $sql = "SELECT userNome FROM gerenciadorsenhas.users WHERE userID = ?";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("i", $userID);
        $stmt->execute();
        $stmt->bind_result($userNome);
        
        if ($stmt->fetch()) {
            // Definir o nome do usuário na sessão
            $_SESSION['userNome'] = $userNome;
        }

        // Agora podemos fechar o stmt após usar todos os resultados
        $stmt->close();
    }
}

// Agora você pode fazer o log da ação de redefinição de senha
logAction($conn, $userID, 'Redefinição de Senha', 'Redefiniu a senha:' . $_SESSION['userNome'] . ') ');


                // Limpar a sessão após redefinição da senha
                unset($_SESSION['resetUserID']);
                $successMessage = 'Senha redefinida com sucesso. Direcionando para o login em 3 segundos.';

                // Esperar 3 segundos antes de redirecionar
                echo "<script>
                    setTimeout(function() {
                        window.location.href = '../login.php';
                    }, 4000);
                </script>";
            } else {
                $errorMessage = 'Erro ao atualizar a senha.';
            }
        } else {
            $errorMessage = 'Erro ao preparar a consulta SQL.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="icon" href="./img/ICON-prokey.ico">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@100..900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="./style/styles.css">
    <link rel="stylesheet" href="./style/styles-loginReg.css">
    <title>Redefinir Senha</title>
    <script>
        function validatePasswords() {
            const newPassword = document.getElementById('newPassword').value;
            const confirmPassword = document.getElementById('confirmPassword').value;
            const message = document.getElementById('passwordMessage');

            if (newPassword === confirmPassword) {
                message.textContent = 'As senhas coincidem.';
                message.style.color = 'green';
            } else {
                message.textContent = 'As senhas não coincidem.';
                message.style.color = 'red';
            }
        }
    </script>
</head>
<body>
    <header class="header">
        <nav class="navbar">
            <div class="navbar-container">
                <div class="navbar-left">
                    <div class="logo-container">
                        <a href="index.php"><img src="./img/ProtectKey-LOGOW.png" alt="Protect Key Logo" class="logo"></a>
                        <a href="index.php"><img src="./img/ProtectKey-LOGOB.png" alt="Protect Key Logo Hover" class="logo-hover"></a>
                    </div>
                    <button class="hamburger" id="hamburger">&#9776;</button>
                    <div class="navbar-menu" id="navbarMenu">
                        <a href="store_password.php" class="navbar-item">Senhas</a>
                        <a href="planos.php" class="navbar-item">Planos</a>
                        <a href="#" class="navbar-item">Contate-nos</a>
                    </div>
                </div>

                <div class="navbar-right">
                    <details class="dropdown">
                        <summary class="profile-icon">
                            <img src="./img/user.png" alt="Profile">
                        </summary>
                        <div class="dropdown-content">
                            <?php if (isset($_SESSION['userNome'])): ?>
                                <p>Bem-vindo, <?php echo $_SESSION['userNome']; ?></p>
                                <a href="conta.php">Detalhes</a>
                                <a href="./php/logout.php" style="border-bottom: none;">Sair da Conta</a>
                            <?php else: ?>
                                <p>Bem-vindo!</p>
                                <a href="register.php">Registrar</a>
                                <a href="login.php" style="border-bottom: none;">Login</a>
                            <?php endif; ?>
                        </div>
                    </details>
                </div>
            </div>
        </nav>
    </header>

    <main class="main-content">
        <section class="hero" style="height: 100vh;">
            <div class="wrapper" style="height: 70%;">
                <form action="" method="post">
                    <h1>Redefinir Senha</h1>

                    <div class="input-box">
                        <input type="password" id="newPassword" name="newPassword" placeholder="Nova Senha" required oninput="validatePasswords()">
                    </div>

                    <div class="input-box">
                        <input type="password" id="confirmPassword" name="confirmPassword" placeholder="Confirmar Senha" required oninput="validatePasswords()">
                    </div>

                    <div id="passwordMessage"></div>

                    <div class="input-box">
                        <input type="text" id="dicaSenha" name="dicaSenha" placeholder="Dica de Senha (opcional)">
                        
                    </div>

                    <?php if ($errorMessage): ?>
                        <p class='message error'><?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?></p>
                    <?php endif; ?>

                    <?php if ($successMessage): ?>
                        <p class="message success"><?= htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8') ?></p>
                    <?php endif; ?>

                    <p style="font-size: 16px; margin:10px 0 20px 0;">Recomendamos que salve uma nova dica da sua senha caso esqueça sua nova senha.</p>

                    <button type="submit" class="btn">Redefinir Senha</button>
                </form>
            </div>
        </section>
    </main>

    <!--FOOTER-->
    <footer>
        <div class="content">
            <div class="top">
                <div class="logo-details">
                    <a href="#"><img class="logo-footer" src="./img/ProtectKey-LOGOW.png" alt="logo icon"></a>
                </div>
                <div class="media-icons">
                    <a href="#"><i class="fab fa-facebook-f"></i></a>
                    <a href="#"><i class="fab fa-twitter"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                    <a href="#"><i class="fab fa-linkedin-in"></i></a>
                    <a href="#"><i class="fab fa-youtube"></i></a>
                </div>
            </div>
            <div class="link-boxes">
                <ul class="box">
                    <li class="link_name">Companhia</li>
                    <li><a href="#">Página Inicial</a></li>
                    <li><a href="#">Entre em Contato</a></li>
                    <li><a href="#">Sobre</a></li>
                    <li><a href="#">Começar Agora</a></li>
                </ul>
                <ul class="box">
                    <li class="link_name">Services</li>
                    <li><a href="#">App design</a></li>
                    <li><a href="#">Web design</a></li>
                    <li><a href="#">Logo design</a></li>
                    <li><a href="#">Banner design</a></li>
                </ul>
                <ul class="box">
                    <li class="link_name">Account</li>
                    <li><a href="#">Profile</a></li>
                    <li><a href="#">My account</a></li>
                    <li><a href="#">Prefrences</a></li>
                    <li><a href="#">Purchase</a></li>
                </ul>
                <ul class="box">
                    <li class="link_name">Courses</li>
                    <li><a href="#">HTML & CSS</a></li>
                    <li><a href="#">JavaScript</a></li>
                    <li><a href="#">Photography</a></li>
                    <li><a href="#">Photoshop</a></li>
                </ul>
                <ul class="box input-box">
                    <li class="link_name">Subscribe</li>
                    <li><input type="text" placeholder="Enter your email"></li>
                    <li><input type="button" value="Subscribe"></li>
                </ul>
            </div>
        </div>
        <div class="bottom-details">
            <div class="bottom_text">
                <span class="copyright_text">Copyright © 2021 <a href="#">CodingLab.</a>All rights reserved</span>
                <span class="policy_terms">
                    <a href="#">Privacy policy</a>
                    <a href="#">Terms & condition</a>
                </span>
            </div>
        </div>
    </footer>

    <script src=""></script>
</body>
</html>
