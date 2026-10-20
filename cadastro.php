
<?php
require_once __DIR__ . '/includes/csrf.php';
if (($_SERVER["REQUEST_METHOD"] ?? "GET") === "POST") { csrf_validar(); }
require_once __DIR__ . "/conexao.php";

$mensagem = "";
$tipoMensagem = "sucesso";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $tipo = $_POST["tipo"] ?? "jovem";
    $email = trim($_POST["email"] ?? "");
    $senha = $_POST["senha"] ?? "";

    if (!in_array($tipo, ["jovem", "empresa"], true)) {
        $tipo = "jovem";
    }

    if ($email === "" || $senha === "") {
        $mensagem = "Preencha o e-mail e a senha.";
        $tipoMensagem = "erro";
    } elseif (strlen($senha) < 8) {
        $mensagem = "A senha deve ter pelo menos 8 caracteres.";
        $tipoMensagem = "erro";
    } else {
        $nome = "";
        $cnpj = "";

        if ($tipo === "jovem") {
            $nome = trim($_POST["nome"] ?? "");

            if ($nome === "") {
                $mensagem = "Informe seu nome completo.";
                $tipoMensagem = "erro";
            }
        } else {
            $nome = trim($_POST["nome_empresa"] ?? "");
            $cnpj = preg_replace('/\D/', '', $_POST["cnpj"] ?? "");

            if ($nome === "" || $cnpj === "") {
                $mensagem = "Preencha o nome da empresa e o CNPJ.";
                $tipoMensagem = "erro";
            } elseif (strlen($cnpj) !== 14) {
                $mensagem = "O CNPJ deve conter 14 números.";
                $tipoMensagem = "erro";
            }
        }

        if ($mensagem === "") {
            $conexao->begin_transaction();

            try {
                $senhaHash = password_hash($senha, PASSWORD_DEFAULT);

                // Primeiro, cria a conta de acesso.
                $stmt = $conexao->prepare(
                    "INSERT INTO usuarios (nome, email, senha, tipo)
                     VALUES (?, ?, ?, ?)"
                );

                $stmt->bind_param(
                    "ssss",
                    $nome,
                    $email,
                    $senhaHash,
                    $tipo
                );

                $stmt->execute();
                $novoUsuarioId = $conexao->insert_id;
                $stmt->close();

                if ($tipo === "jovem") {
                    // Cria o perfil do jovem.
                    $stmtPerfil = $conexao->prepare(
                        "INSERT INTO jovens (usuario_id) VALUES (?)"
                    );

                    $stmtPerfil->bind_param("i", $novoUsuarioId);
                    $stmtPerfil->execute();
                    $stmtPerfil->close();

                } else {
                    // Cria o perfil empresarial vinculado à conta.
                    $stmtEmpresa = $conexao->prepare(
                        "INSERT INTO empresas
                         (usuario_id, nome_empresa, cnpj)
                         VALUES (?, ?, ?)"
                    );

                    $stmtEmpresa->bind_param(
                        "iss",
                        $novoUsuarioId,
                        $nome,
                        $cnpj
                    );

                    $stmtEmpresa->execute();
                    $stmtEmpresa->close();
                }

                $conexao->commit();

                $mensagem = "Cadastro realizado com sucesso! <a href='login.php'>Faça login</a>";

            } catch (mysqli_sql_exception $e) {
                $conexao->rollback();

                if ($e->getCode() === 1062) {
                    $mensagem = "Este e-mail ou CNPJ já está cadastrado.";
                } else {
                    error_log("Erro no cadastro StartWay: " . $e->getMessage());
                    $mensagem = "Não foi possível concluir o cadastro. Confira os dados e a estrutura do banco.";
                }

                $tipoMensagem = "erro";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Criar conta | StartWay</title>
    <link rel="stylesheet" href="css/login.css">
</head>
<body>
<main class="auth-wrapper">
    <section class="auth-lado-esquerdo">
        <header class="brand-container"><img src="img/logo.png" alt="Logo StartWay" class="logo-img"><span class="logo-texto">StartWay</span></header>
        <div class="mensagem-banner">
            <span class="etiqueta-banner">✦ O SEU PRÓXIMO PASSO</span>
            <h1>Seu talento merece <span>uma oportunidade.</span></h1>
            <p>Aprenda, desenvolva suas habilidades e encontre caminhos para crescer na tecnologia.</p>
        </div>
        <?php include __DIR__ . "/painel_oportunidades.php"; ?>
        <footer class="rodape-banner">© 2026 StartWay · Oportunidade, crescimento e conexão</footer>
    </section>

    <section class="auth-lado-direito" aria-label="Formulário de cadastro">
        <div class="form-box">
            <div class="form-topo"><span class="form-etiqueta">VAMOS COMEÇAR?</span><h2>Crie sua conta</h2><p>Descubra cursos e oportunidades para o seu futuro.</p></div>

            <?php if ($mensagem !== ""): ?><div class="mensagem <?= $tipoMensagem === "erro" ? "erro" : "" ?>" role="status"><?= $mensagem ?></div><?php endif; ?>

            <form method="POST" action="cadastro.php"><?php csrf_campo(); ?>
                <input type="hidden" name="tipo" id="inputTipo" value="<?= htmlspecialchars($_POST["tipo"] ?? "jovem", ENT_QUOTES, "UTF-8") ?>">
                <div class="tipo-selector" role="group" aria-label="Tipo de conta">
                    <button type="button" class="tipo-card ativo" id="cardJovem" onclick="selecionarTipo('jovem')">Sou jovem</button>
                    <button type="button" class="tipo-card" id="cardEmpresa" onclick="selecionarTipo('empresa')">Sou empresa</button>
                </div>
                <div id="camposJovem" class="bloco-campos">
                    <div class="grupo-form"><label for="nome">Nome completo</label><input type="text" name="nome" id="nome" autocomplete="name" placeholder="Como podemos te chamar?" required></div>
                </div>
                <div id="camposEmpresa" class="bloco-campos" hidden>
                    <div class="grupo-form"><label for="nome_empresa">Nome da empresa</label><input type="text" name="nome_empresa" id="nome_empresa" autocomplete="organization" placeholder="Nome da sua empresa" disabled></div>
                    <div class="grupo-form"><label for="cnpj">CNPJ</label><input type="text" name="cnpj" id="cnpj" inputmode="numeric" placeholder="00.000.000/0001-00" disabled></div>
                </div>
                <div class="grupo-form"><label for="email">E-mail</label><input type="email" name="email" id="email" autocomplete="email" placeholder="voce@email.com" required></div>
                <div class="grupo-form"><label for="senha">Senha</label><input type="password" name="senha" id="senha" autocomplete="new-password" minlength="8" placeholder="Crie uma senha (mín. 8 caracteres)" required></div>
                <button type="submit" class="botao-principal">Criar conta <span>→</span></button>
            </form>
            <p class="link-login">Já tem uma conta? <a href="login.php">Entrar</a></p>
            <p class="termos">Oportunidade, crescimento e conexão.</p>
        </div>
    </section>
</main>
<script src="js/oportunidades.js"></script>
<script>
function selecionarTipo(tipo) {
    const jovem = tipo === "jovem";
    document.getElementById("inputTipo").value = tipo;
    document.getElementById("cardJovem").classList.toggle("ativo", jovem);
    document.getElementById("cardEmpresa").classList.toggle("ativo", !jovem);
    document.getElementById("cardJovem").setAttribute("aria-pressed", String(jovem));
    document.getElementById("cardEmpresa").setAttribute("aria-pressed", String(!jovem));
    document.getElementById("camposJovem").hidden = !jovem;
    document.getElementById("camposEmpresa").hidden = jovem;
    document.getElementById("nome").disabled = !jovem;
    document.getElementById("nome").required = jovem;
    document.getElementById("nome_empresa").disabled = jovem;
    document.getElementById("nome_empresa").required = !jovem;
    document.getElementById("cnpj").disabled = jovem;
    document.getElementById("cnpj").required = !jovem;
}
selecionarTipo(document.getElementById("inputTipo").value === "empresa" ? "empresa" : "jovem");
</script>
</body>
</html>