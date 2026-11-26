<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once "config/conexao.php";
require_once "config/auth.php";

session_start();

if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

$id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

if (!$id) {
    header("Location: estoque.php");
    exit;
}

// Busca a tinta cadastrada

$stmt = $pdo->prepare("SELECT * FROM tintas WHERE id = ?");
$stmt->execute([$id]);

$tinta = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$tinta) {
    header("Location: estoque.php");
    exit;
}

$erro = "";

// Atualiza as informações

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $nome = trim($_POST["nome"] ?? "");
    $codigo = trim($_POST["codigo"] ?? "");
    $fabricante = trim($_POST["fabricante"] ?? "");
    $acabamento = $_POST["acabamento"] ?? "";
    $tipo = $_POST["tipo"] ?? "";
    $estoque_minimo = $_POST["estoque_minimo"] ?? "";
    $lote = trim($_POST["lote"] ?? "");
    $validade = ($_POST["validade"] ?? "") ?: null;
    $localizacao = trim($_POST["localizacao"] ?? "");
    $cor_hex = $_POST["cor_hex"] ?? "#000000";
    $valor_kg = $_POST["valor_kg"] ?? "";

    if ($estoque_minimo === "") {
        $estoque_minimo = 5;
    }

    if (!preg_match('/^#[0-9a-fA-F]{6}$/', $cor_hex)) {
        $cor_hex = "#000000";
    }

    if (!hash_equals($_SESSION['csrf'], $_POST['csrf'] ?? '')) {

        $erro = "Solicitação inválida.";

    } elseif ($nome === "") {

        $erro = "Informe o nome da tinta.";

    } elseif (
        !is_numeric($estoque_minimo) ||
        $estoque_minimo < 0 ||
        ($valor_kg !== "" &&
        (!is_numeric($valor_kg) || $valor_kg < 0))
    ) {

        $erro = "Informe valores numéricos válidos.";

    } else {

        try {

            $sql = "UPDATE tintas SET
                nome = ?,
                codigo = ?,
                fabricante = ?,
                acabamento = ?,
                tipo = ?,
                estoque_minimo = ?,
                lote = ?,
                validade = ?,
                localizacao = ?,
                cor_hex = ?,
                valor_kg = ?
                WHERE id = ?";

            $stmt = $pdo->prepare($sql);

            $stmt->execute([
                $nome,
                $codigo,
                $fabricante,
                $acabamento,
                $tipo,
                $estoque_minimo,
                $lote,
                $validade,
                $localizacao,
                $cor_hex,
                $valor_kg === "" ? null : $valor_kg,
                $id
            ]);

            header("Location: estoque.php");
            exit;

        } catch (PDOException $e) {

            $erro = "Não foi possível atualizar a tinta.";

        }
    }
}

// Proteção dos textos exibidos

function texto($valor) {
    return htmlspecialchars(
        (string) ($valor ?? ""),
        ENT_QUOTES,
        "UTF-8"
    );
}

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>AluStock | Editar tinta</title>

    <link rel="stylesheet"
        href="/site-fast/estoque-tintas/css/style.css?v=6">

        <script src="/site-fast/estoque-tintas/js/tema.js"></script>

</head>

<body>

    <aside class="sidebar">

        <div class="logo">
            <h2>AluStock</h2>
            <p>Pintura Eletrostática</p>
        </div>

        <nav>
            <a href="index.php">Dashboard</a>
            <a href="estoque.php" class="ativo">Estoque de tintas</a>
            <a href="cadastrar.php">Cadastrar tinta</a>
            <a href="movimentacoes.php">Movimentações</a>
        </nav>

    </aside>

    <main class="conteudo">

        <header class="cabecalho">

            <div>
                <h1>Editar tinta</h1>
                <p>Atualize as informações do cadastro.</p>
            </div>

            <a href="estoque.php" class="botao-secundario">
                Voltar
            </a>

        </header>

        <section class="painel">

            <h2>Informações da tinta</h2>

            <?php if ($erro): ?>

                <div class="mensagem-erro">
                    <?= texto($erro) ?>
                </div>

            <?php endif; ?>

            <form method="POST">

                <input type="hidden" name="csrf"
                    value="<?= texto($_SESSION['csrf']) ?>">

                <div class="form-grid">

                    <div class="campo">

                        <label>Nome da tinta *</label>

                        <input type="text" name="nome"
                            value="<?= texto($tinta['nome']) ?>"
                            required>

                    </div>

                    <div class="campo">

                        <label>Código da tinta</label>

                        <input type="text" name="codigo"
                            value="<?= texto($tinta['codigo']) ?>">

                    </div>

                    <div class="campo">

                        <label>Fabricante</label>

                        <input type="text" name="fabricante"
                            value="<?= texto($tinta['fabricante']) ?>">

                    </div>

                    <div class="campo">

                        <label>Acabamento</label>

                        <select name="acabamento">

                            <option value="">Selecione</option>

                            <?php
                            $acabamentos = [
                                "Fosco",
                                "Brilhante",
                                "Semibrilho",
                                "Acetinado",
                                "Texturizado",
                                "Metalizado",
                                "Outro"
                            ];

                            foreach ($acabamentos as $item):
                            ?>

                                <option value="<?= texto($item) ?>"
                                    <?= $tinta['acabamento'] === $item
                                        ? 'selected' : '' ?>>

                                    <?= texto($item) ?>

                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>

                    <div class="campo">

                        <label>Tipo de tinta</label>

                        <select name="tipo">

                            <option value="">Selecione</option>

                            <?php
                            $tipos = [
                                "Poliéster",
                                "Epóxi",
                                "Híbrida",
                                "Outro"
                            ];

                            foreach ($tipos as $item):
                            ?>

                                <option value="<?= texto($item) ?>"
                                    <?= $tinta['tipo'] === $item
                                        ? 'selected' : '' ?>>

                                    <?= texto($item) ?>

                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>

                    <div class="campo">

                        <label>Cor aproximada</label>

                        <input type="color" name="cor_hex"
                            value="<?= texto($tinta['cor_hex'] ?: '#000000') ?>">

                    </div>

                    <div class="campo">

                        <label>Quantidade atual (kg)</label>

                        <input type="number"
                            value="<?= texto($tinta['quantidade']) ?>"
                            readonly>

                        <small>
                            Utilize Entrada ou Saída para alterar o estoque.
                        </small>

                    </div>

                    <div class="campo">

                        <label>Estoque mínimo (kg)</label>

                        <input type="number" name="estoque_minimo"
                            step="0.001" min="0"
                            value="<?= texto($tinta['estoque_minimo']) ?>">

                    </div>

                    <div class="campo">

                        <label>Número do lote</label>

                        <input type="text" name="lote"
                            value="<?= texto($tinta['lote']) ?>">

                    </div>

                    <div class="campo">

                        <label>Data de validade</label>

                        <input type="date" name="validade"
                            value="<?= texto($tinta['validade']) ?>">

                    </div>

                    <div class="campo">

                        <label>Localização no estoque</label>

                        <input type="text" name="localizacao"
                            value="<?= texto($tinta['localizacao']) ?>">

                    </div>

                    <div class="campo">

                        <label>Valor por kg (R$)</label>

                        <input type="number" name="valor_kg"
                            step="0.01" min="0"
                            value="<?= texto($tinta['valor_kg']) ?>">

                    </div>

                </div>

                <div class="form-acoes">

                    <a href="estoque.php" class="botao-secundario">
                        Cancelar
                    </a>

                    <button type="submit" class="botao">
                        Salvar alterações
                    </button>

                </div>

            </form>

        </section>

    </main>

</body>

</html>
