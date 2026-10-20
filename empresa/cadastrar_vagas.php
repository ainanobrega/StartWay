
<?php
require_once __DIR__ . '/../includes/csrf.php';
if (($_SERVER["REQUEST_METHOD"] ?? "GET") === "POST") { csrf_validar(); }
require_once __DIR__ . '/../includes/bootstrap.php';

if (!isset($_SESSION["id_usuario"])) {
    header("Location: ../login.php");
    exit;
}

if (($_SESSION["tipo"] ?? "") !== "empresa") {
    header("Location: ../home.php");
    exit;
}



$id_usuario = (int) $_SESSION["id_usuario"];
$mensagem = "";
$erro = "";

// Busca a empresa vinculada à conta logada
$sql_empresa = "SELECT id, nome_empresa FROM empresas WHERE usuario_id = ?";
$stmt_empresa = $conexao->prepare($sql_empresa);
$stmt_empresa->bind_param("i", $id_usuario);
$stmt_empresa->execute();

$resultado_empresa = $stmt_empresa->get_result();
$empresa = $resultado_empresa->fetch_assoc();

$stmt_empresa->close();

if (!$empresa) {
    $erro = "Não encontramos o cadastro da sua empresa. Verifique seu perfil empresarial.";
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && !$erro) {
    $titulo = trim($_POST["titulo"] ?? "");
    $descricao = trim($_POST["descricao"] ?? "");
    $tipo = $_POST["tipo"] ?? "";
    $modalidade = $_POST["modalidade"] ?? "";
    $cidade = trim($_POST["cidade"] ?? "");
    $salario_texto = trim($_POST["salario"] ?? "");

    // Valores permitidos pelo formulário
    $tipos_permitidos = [
        "estagio",
        "jovem_aprendiz",
        "efetivo",
        "freelancer"
    ];

    $modalidades_permitidas = [
        "presencial",
        "remoto",
        "hibrido"
    ];

    if (
        $titulo === "" ||
        $descricao === "" ||
        !in_array($tipo, $tipos_permitidos, true) ||
        !in_array($modalidade, $modalidades_permitidas, true)
    ) {
        $erro = "Preencha os campos obrigatórios corretamente.";
    } elseif (strlen($titulo) > 150) {
        $erro = "O título deve ter no máximo 150 caracteres.";
    } elseif (strlen($cidade) > 100) {
        $erro = "A cidade deve ter no máximo 100 caracteres.";
    } elseif (
        $salario_texto !== "" &&
        (!is_numeric($salario_texto) || (float) $salario_texto < 0)
    ) {
        $erro = "Informe um salário válido ou deixe o campo vazio.";
    } elseif (
        $salario_texto !== "" &&
        (float) $salario_texto > 99999999.99
    ) {
        $erro = "O valor do salário é muito alto.";
    } else {
        $empresa_id = (int) $empresa["id"];
        $salario = $salario_texto === ""
            ? null
            : (float) $salario_texto;

        $sql = "INSERT INTO vagas
                (empresa_id, titulo, descricao, tipo, modalidade,
                 cidade, salario, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, 'aberta')";

        $stmt = $conexao->prepare($sql);

        $stmt->bind_param(
            "isssssd",
            $empresa_id,
            $titulo,
            $descricao,
            $tipo,
            $modalidade,
            $cidade,
            $salario
        );

        if ($stmt->execute()) {
            $stmt->close();

            header("Location: cadastrar_vagas.php?sucesso=1");
            exit;
        } else {
            $erro = "Não foi possível cadastrar a vaga. Verifique os dados e tente novamente.";
            $stmt->close();
        }
    }
}

if (isset($_GET["sucesso"])) {
    $mensagem = "Vaga cadastrada com sucesso!";
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastrar vaga | StartWay</title>

    
<style>
:root {
    --roxo: #7138cf;
    --roxo-escuro: #4c218b;
    --lilas: #f1eaff;
    --texto: #292044;
    --texto-suave: #756b87;
    --borda: #e7def4;
    --branco: #ffffff;
}

body {
    margin: 0;
    font-family: Inter, system-ui, sans-serif;
    color: var(--texto);
    background:
        radial-gradient(ellipse at 15% 10%, #e8dcff 0, transparent 40%),
        linear-gradient(135deg, #faf8ff, #f3edff);
    min-height: 100vh;
}

.topo-empresa {
    min-height: 82px;
    padding: 15px 6%;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 22px;
    flex-wrap: wrap;
    background: rgba(255, 255, 255, 0.94);
    border-bottom: 1px solid var(--borda);
    box-shadow: 0 5px 22px #39205c0a;
}

.marca {
    display: inline-flex;
    align-items: center;
    gap: 11px;
    color: var(--roxo);
    text-decoration: none;
    font-size: 24px;
    font-weight: 850;
    letter-spacing: -0.7px;
}

.marca img {
    display: block;
    width: 43px;
    height: 43px;
    object-fit: contain;
}

.navegacao-empresa {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}

.navegacao-empresa a {
    padding: 11px 15px;
    border-radius: 11px;
    color: #57466f;
    font-size: 14px;
    font-weight: 700;
    text-decoration: none;
    transition: background .2s, color .2s;
}

.navegacao-empresa a:hover,
.navegacao-empresa a[aria-current="page"] {
    color: var(--roxo-escuro);
    background: var(--lilas);
}

.navegacao-empresa .link-sair {
    color: #8b3b62;
    background: #fff3f7;
}

.navegacao-empresa .link-sair:hover {
    background: #ffe5ee;
}

main {
    width: min(820px, 92%);
    margin: 42px auto 64px;
}

.introducao {
    margin-bottom: 26px;
    padding: 30px;
    border: 1px solid #e8dcfa;
    border-radius: 23px;
    background: linear-gradient(120deg, #f0e7ff, #ffffff);
}

.etiqueta {
    display: inline-block;
    margin-bottom: 12px;
    color: var(--roxo);
    font-size: 11px;
    font-weight: 850;
    letter-spacing: 1.5px;
}

.introducao h2 {
    margin: 0 0 12px;
    font-size: clamp(25px, 4vw, 34px);
    line-height: 1.2;
    letter-spacing: -0.8px;
}

.introducao p {
    margin: 0;
    color: var(--texto-suave);
    line-height: 1.8;
}

.formulario {
    padding: 32px;
    border: 1px solid var(--borda);
    border-radius: 22px;
    background: var(--branco);
    box-shadow: 0 16px 45px #38234d0d;
}

.formulario h2 {
    margin: 0 0 8px;
    font-size: 23px;
}

.formulario .descricao-formulario {
    margin: 0 0 26px;
    color: var(--texto-suave);
    font-size: 14px;
    line-height: 1.7;
}

.campo {
    margin-bottom: 22px;
}

label {
    display: block;
    margin-bottom: 9px;
    color: #392653;
    font-size: 14px;
    font-weight: 750;
}

input,
select,
textarea {
    width: 100%;
    padding: 14px 15px;
    border: 1px solid #ded3ee;
    border-radius: 12px;
    background: #fdfcff;
    color: var(--texto);
    font: inherit;
    transition: border-color .2s, box-shadow .2s;
}

input:focus,
select:focus,
textarea:focus {
    outline: none;
    border-color: #9665e4;
    box-shadow: 0 0 0 4px #8b50e81a;
}

textarea {
    min-height: 155px;
    resize: vertical;
    line-height: 1.7;
}

.dica {
    display: block;
    margin-top: 8px;
    color: var(--texto-suave);
    font-size: 12px;
    line-height: 1.6;
}

.botao {
    display: block;
    width: 100%;
    padding: 15px 18px;
    border: 0;
    border-radius: 13px;
    background: linear-gradient(120deg, #8b50e8, #6428bd);
    color: white;
    font-size: 15px;
    font-weight: 800;
    cursor: pointer;
    text-align: center;
    text-decoration: none;
    box-shadow: 0 8px 20px #6428bd20;
    transition: transform .2s, box-shadow .2s;
}

.botao:hover {
    transform: translateY(-2px);
    box-shadow: 0 12px 25px #6428bd30;
}

.alerta {
    padding: 15px 17px;
    margin-bottom: 20px;
    border-radius: 12px;
    line-height: 1.6;
}

.sucesso {
    border: 1px solid #bde8cc;
    background: #e8f8ee;
    color: #176638;
}

.erro {
    border: 1px solid #f3caca;
    background: #fff0f0;
    color: #a32323;
}

.voltar {
    display: inline-flex;
    margin-top: 23px;
    color: var(--roxo);
    font-weight: 750;
    text-decoration: none;
}

.voltar:hover {
    text-decoration: underline;
}

@media (max-width: 600px) {
    .topo-empresa {
        padding: 15px 5%;
        align-items: flex-start;
    }

    .navegacao-empresa {
        width: 100%;
        gap: 5px;
    }

    .navegacao-empresa a {
        padding: 9px 10px;
        font-size: 12px;
    }

    main {
        margin-top: 25px;
    }

    .introducao {
        padding: 23px;
    }

    .formulario {
        padding: 22px 18px;
    }

    
.topo-empresa {
    min-height: 82px;
    padding: 15px 6%;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 22px;
    flex-wrap: wrap;
    background: #fff;
    border-bottom: 1px solid #e7def4;
    box-shadow: 0 5px 22px #39205c0a;
}

.marca {
    display: inline-flex;
    align-items: center;
    gap: 11px;
    color: #7138cf;
    text-decoration: none;
    font-size: 24px;
    font-weight: 850;
}

.marca img {
    width: 43px;
    height: 43px;
    object-fit: contain;
}

.navegacao-empresa {
    display: flex;
    align-items: center;
}

.botao-voltar {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 11px 17px;
    border: 1px solid #e3d5f8;
    border-radius: 12px;
    background: #f5efff;
    color: #6428bd;
    font-weight: 750;
    text-decoration: none;
    transition: background .2s, transform .2s;
}

.botao-voltar:hover {
    background: #e9dcff;
    transform: translateY(-1px);
}

@media (max-width: 600px) {
    .topo-empresa {
        padding: 15px 5%;
    }
}

}
</style>
</head>

<body>



<header class="topo-empresa">
    <a href="painel.php" class="marca">
        <img src="../img/logo.png" alt="Logo StartWay">
        <span>StartWay</span>
    </a>

    <nav class="navegacao-empresa">
        <a href="painel.php" class="botao-voltar">
            <span aria-hidden="true">←</span>
            Voltar ao painel
        </a>
    </nav>
</header>



<main>
    <section class="introducao">
        <h2>Publique uma oportunidade 💜</h2>
        <p>
            Olá, <?= htmlspecialchars($empresa["nome_empresa"] ?? $_SESSION["nome"] ?? "empresa", ENT_QUOTES, "UTF-8") ?>!
            Preencha os dados abaixo para encontrar novos talentos.
        </p>
    </section>

    <section class="formulario">
        <h2>Cadastrar vaga</h2>

        <?php if ($mensagem): ?>
            <div class="alerta sucesso">
                <?= htmlspecialchars($mensagem, ENT_QUOTES, "UTF-8") ?>
            </div>
        <?php endif; ?>

        <?php if ($erro): ?>
            <div class="alerta erro">
                <?= htmlspecialchars($erro, ENT_QUOTES, "UTF-8") ?>
            </div>
        <?php endif; ?>

        <?php if ($empresa): ?>
        <form method="POST" action="cadastrar_vagas.php"><?php csrf_campo(); ?>

            <div class="campo">
                <label for="titulo">Título da vaga *</label>
                <input
                    type="text"
                    id="titulo"
                    name="titulo"
                    maxlength="150"
                    placeholder="Ex.: Estágio em Desenvolvimento Web"
                    value="<?= htmlspecialchars($_POST["titulo"] ?? "", ENT_QUOTES, "UTF-8") ?>"
                    required
                >
            </div>

            <div class="campo">
                <label for="descricao">Descrição da vaga *</label>
                <textarea
                    id="descricao"
                    name="descricao"
                    placeholder="Descreva as atividades, os requisitos e os benefícios..."
                    required
                ><?= htmlspecialchars($_POST["descricao"] ?? "", ENT_QUOTES, "UTF-8") ?></textarea>
            </div>

            <div class="campo">
                <label for="tipo">Tipo de oportunidade *</label>
                <select id="tipo" name="tipo" required>
                    <option value="">Selecione</option>
                    <option value="estagio">Estágio</option>
                    <option value="jovem_aprendiz">Jovem aprendiz</option>
                    <option value="efetivo">Emprego efetivo</option>
                    <option value="freelancer">Freelancer</option>
                </select>
            </div>

            <div class="campo">
                <label for="modalidade">Modalidade *</label>
                <select id="modalidade" name="modalidade" required>
                    <option value="">Selecione</option>
                    <option value="presencial">Presencial</option>
                    <option value="remoto">Remoto</option>
                    <option value="hibrido">Híbrido</option>
                </select>
            </div>

            <div class="campo">
                <label for="cidade">Cidade</label>
                <input
                    type="text"
                    id="cidade"
                    name="cidade"
                    maxlength="100"
                    placeholder="Ex.: Campina Grande - PB"
                    value="<?= htmlspecialchars($_POST["cidade"] ?? "", ENT_QUOTES, "UTF-8") ?>"
                >
            </div>

            <div class="campo">
                <label for="salario">Salário mensal (R$)</label>
                <input
                    type="number"
                    id="salario"
                    name="salario"
                    min="0"
                    max="99999999.99"
                    step="0.01"
                    placeholder="Ex.: 1500,00"
                    value="<?= htmlspecialchars($_POST["salario"] ?? "", ENT_QUOTES, "UTF-8") ?>"
                >
                <span class="dica">
                    Se não houver salário definido, você pode deixar vazio.
                </span>
            </div>

            <button type="submit" class="botao">
                Publicar vaga
            </button>

        </form>
        <?php endif; ?>
    </section>

    <a class="voltar" href="painel.php">← Voltar ao painel</a>
</main>

</body>
</html>
            