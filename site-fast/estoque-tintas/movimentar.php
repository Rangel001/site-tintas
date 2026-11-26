
<?php

require_once "config/conexao.php";

session_start();

if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

date_default_timezone_set("America/Sao_Paulo");

if (!in_array($tipoMovimentacao ?? "", ["entrada", "saida"], true)) {
    header("Location: estoque.php");
    exit;
}

$id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

if (!$id) {
    header("Location: estoque.php");
    exit;
}

$entrada = $tipoMovimentacao === "entrada";

$titulo = $entrada
    ? "Entrada de estoque"
    : "Saída de estoque";

$erro = "";

// Busca a tinta

$stmt = $pdo->prepare(
    "SELECT * FROM tintas WHERE id = ?"
);

$stmt->execute([$id]);

$tinta = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$tinta) {
    header("Location: estoque.php");
    exit;
}

// Registra a movimentação

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $quantidade = str_replace(
        ",", ".", trim($_POST["quantidade"] ?? "")
    );

    $observacao = trim($_POST["observacao"] ?? "");

    if (!hash_equals($_SESSION['csrf'], $_POST['csrf'] ?? '')) {

        $erro = "Solicitação inválida.";

    } elseif (
        !preg_match('/^\d{1,9}(\.\d{1,3})?$/', $quantidade) ||
        (float) $quantidade <= 0
    ) {

        $erro = "Informe uma quantidade válida maior que zero.";

    } else {

        $quantidade = round((float) $quantidade, 3);

        try {

            $pdo->beginTransaction();

            if ($entrada) {

                $sql = "UPDATE tintas SET
                    quantidade = ROUND(quantidade + ?, 3)
                    WHERE id = ?";

                $stmt = $pdo->prepare($sql);

                $stmt->execute([$quantidade, $id]);

            } else {

                $sql = "UPDATE tintas SET
                    quantidade = ROUND(quantidade - ?, 3)
                    WHERE id = ?
                    AND quantidade >= CAST(? AS REAL)";

                $stmt = $pdo->prepare($sql);

                $stmt->execute([
                    $quantidade,
                    $id,
                    $quantidade
                ]);
            }

            if ($stmt->rowCount() !== 1) {

                throw new RuntimeException(
                    "Estoque insuficiente ou tinta não encontrada."
                );
            }

            // Salva no histórico

            $sql = "INSERT INTO movimentacoes (
                tinta_id,
                tipo,
                quantidade,
                observacao,
                data_movimentacao
            ) VALUES (?, ?, ?, ?, ?)";

            $stmt = $pdo->prepare($sql);

            $stmt->execute([
                $id,
                $tipoMovimentacao,
                $quantidade,
                $observacao,
                date("Y-m-d H:i:s")
            ]);

            $pdo->commit();

            header("Location: estoque.php");
            exit;

        } catch (RuntimeException $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $erro = $e instanceof PDOException
                ? "Não foi possível registrar a movimentação."
                : $e->getMessage();

        }
    }

    // Atualiza o saldo exibido em caso de erro

    $stmt = $pdo->prepare(
        "SELECT * FROM tintas WHERE id = ?"
    );

    $stmt->execute([$id]);

    $tinta = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$tinta) {
        header("Location: estoque.php");
        exit;
    }
}

function texto($valor) {
    return htmlspecialchars(
        (string) ($valor ?? ""),
        ENT_QUOTES,
        "UTF-8"
    );
}

$cor = preg_match(
    '/^#[0-9a-fA-F]{6}$/',
    $tinta["cor_hex"] ?? ""
) ? $tinta["cor_hex"] : "#FFFFFF";

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>AluStock | <?= texto($titulo) ?></title>

    <link rel="stylesheet"
        href="/site-fast/estoque-tintas/css/style.css?v=7">

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
                <h1><?= texto($titulo) ?></h1>

                <p>
                    <?= $entrada
                        ? "Adicione tinta ao estoque."
                        : "Registre a quantidade utilizada." ?>
                </p>
            </div>

            <a href="estoque.php" class="botao-secundario">
                Voltar
            </a>

        </header>

        <section class="painel painel-movimentacao">

            <div class="mov-tinta">

                <div class="cor-grande"
                    style="background-color: <?= $cor ?>">
                </div>

                <div>

                    <h3><?= texto($tinta["nome"]) ?></h3>

                    <p><?= texto($tinta["codigo"] ?: "Sem código") ?></p>

                </div>

            </div>

            <div class="saldo-atual">

                <p>Quantidade atual em estoque</p>

                <h2 id="saldo-atual"
                    data-saldo="<?= texto($tinta["quantidade"]) ?>">

                    <?= number_format(
                        $tinta["quantidade"], 3, ",", "."
                    ) ?> kg

                </h2>

            </div>

            <?php if ($erro): ?>

                <div class="mensagem-erro">
                    <?= texto($erro) ?>
                </div>

            <?php endif; ?>

            <form method="POST">

                <input type="hidden" name="csrf"
                    value="<?= texto($_SESSION["csrf"]) ?>">

                <div class="campo">

                    <label>Quantidade em kg *</label>

                    <input type="number"
                        name="quantidade"
                        id="quantidade-mov"
                        min="0.001"
                        step="0.001"
                        placeholder="Ex: 5"
                        value="<?= texto($_POST["quantidade"] ?? "") ?>"
                        required>

                </div>

                <div class="campo campo-observacao">

                    <label>Observação (opcional)</label>

                    <textarea
                        name="observacao"
                        rows="4"
                        placeholder="Ex: Compra de tinta ou ordem de produção"><?= texto($_POST["observacao"] ?? "") ?></textarea>

                </div>

                <div class="saldo-previsto">

                    <p>Estoque após a movimentação</p>

                    <h2 id="novo-saldo">
                        <?= number_format(
                            $tinta["quantidade"], 3, ",", "."
                        ) ?> kg
                    </h2>

                    <small id="aviso-saldo" hidden>
                        Quantidade insuficiente em estoque.
                    </small>

                </div>

                <div class="form-acoes">

                    <a href="estoque.php" class="botao-secundario">
                        Cancelar
                    </a>

                    <button type="submit"
                        id="confirmar-mov"
                        class="botao <?= $entrada
                            ? 'botao-entrada'
                            : 'botao-saida' ?>">

                        <?= $entrada
                            ? "Confirmar entrada"
                            : "Confirmar saída" ?>

                    </button>

                </div>

            </form>

        </section>

    </main>

    <script>

        const quantidade = document.getElementById("quantidade-mov");
        const novoSaldo = document.getElementById("novo-saldo");
        const aviso = document.getElementById("aviso-saldo");
        const botao = document.getElementById("confirmar-mov");

        const saldo = Number(
            document.getElementById("saldo-atual").dataset.saldo
        );

        const entrada = <?= $entrada ? "true" : "false" ?>;

        function atualizarSaldo() {

            const valor = Number(quantidade.value) || 0;

            const resultado = Math.round(
                (entrada ? saldo + valor : saldo - valor) * 1000
            ) / 1000;

            novoSaldo.textContent = resultado.toLocaleString(
                "pt-BR", {
                    minimumFractionDigits: 3,
                    maximumFractionDigits: 3
                }
            ) + " kg";

            if (resultado < 0) {

                aviso.hidden = false;
                novoSaldo.style.color = "#dc2626";
                botao.disabled = true;

            } else {

                aviso.hidden = true;
                novoSaldo.style.color = "#2563eb";
                botao.disabled = valor <= 0;

            }
        }

        quantidade.addEventListener("input", atualizarSaldo);

        atualizarSaldo();

    </script>

</body>

</html>
