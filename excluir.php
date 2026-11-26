
<?php

require_once "config/conexao.php";

session_start();

if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

$id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

if (!$id) {
    header("Location: estoque.php");
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM tintas WHERE id = ?");
$stmt->execute([$id]);

$tinta = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$tinta) {
    header("Location: estoque.php");
    exit;
}

// Verifica se existem movimentações

$stmt = $pdo->prepare(
    "SELECT COUNT(*) FROM movimentacoes WHERE tinta_id = ?"
);

$stmt->execute([$id]);

$totalMovimentacoes = $stmt->fetchColumn();

$erro = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (!hash_equals($_SESSION['csrf'], $_POST['csrf'] ?? '')) {

        $erro = "Solicitação inválida.";

    } else {

        try {

            $stmt = $pdo->prepare(
                "DELETE FROM tintas
                 WHERE id = ?
                 AND quantidade = 0
                 AND NOT EXISTS (
                     SELECT 1 FROM movimentacoes
                     WHERE tinta_id = tintas.id
                 )"
            );

            $stmt->execute([$id]);

            if ($stmt->rowCount() === 1) {

                header("Location: estoque.php");
                exit;

            } else {

                $erro = "Não é possível excluir uma tinta com estoque ou histórico de movimentações.";

            }

        } catch (PDOException $e) {

            $erro = "Não foi possível excluir a tinta.";

        }
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
    $tinta['cor_hex'] ?? ''
) ? $tinta['cor_hex'] : '#FFFFFF';

$podeExcluir = $totalMovimentacoes == 0
    && $tinta['quantidade'] == 0;

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>AluStock | Excluir tinta</title>

    <link rel="stylesheet"
        href="/site-fast/estoque-tintas/css/style.css?v=9">

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
                <h1>Excluir tinta</h1>
                <p>Confirme a exclusão do cadastro.</p>
            </div>

            <a href="estoque.php" class="botao-secundario">
                Voltar
            </a>

        </header>

        <section class="painel painel-exclusao">

            <div class="icone-exclusao">!</div>

            <h2>Deseja excluir esta tinta?</h2>

            <p class="descricao-exclusao">
                Confira as informações antes de continuar.
            </p>

            <div class="resumo-exclusao">

                <div class="mov-tinta">

                    <div class="cor-grande"
                        style="background-color: <?= $cor ?>">
                    </div>

                    <div>

                        <h3><?= texto($tinta['nome']) ?></h3>

                        <p>
                            <?= texto($tinta['codigo'] ?: 'Sem código') ?>
                        </p>

                    </div>

                </div>

                <p>
                    Fabricante:
                    <strong>
                        <?= texto($tinta['fabricante'] ?: 'Não informado') ?>
                    </strong>
                </p>

                <p>
                    Quantidade:
                    <strong>
                        <?= number_format($tinta['quantidade'], 3, ',', '.') ?> kg
                    </strong>
                </p>

                <p>
                    Movimentações registradas:
                    <strong><?= $totalMovimentacoes ?></strong>
                </p>

            </div>

            <?php if ($erro): ?>

                <div class="mensagem-erro">
                    <?= texto($erro) ?>
                </div>

            <?php endif; ?>

            <?php if ($podeExcluir): ?>

                <div class="aviso-exclusao">

                    Atenção! Esta ação é permanente
                    e não poderá ser desfeita.

                </div>

            <?php else: ?>

                <div class="aviso-exclusao">

                    Esta tinta possui estoque ou histórico
                    de movimentações. A exclusão foi bloqueada
                    para proteger os registros.

                </div>

            <?php endif; ?>

            <form method="POST">

                <input type="hidden" name="csrf"
                    value="<?= texto($_SESSION['csrf']) ?>">

                <div class="form-acoes">

                    <a href="estoque.php" class="botao-secundario">
                        Cancelar
                    </a>

                    <button type="submit"
                        class="botao botao-excluir"
                        <?= !$podeExcluir ? 'disabled' : '' ?>>

                        Confirmar exclusão

                    </button>

                </div>

            </form>

        </section>

    </main>

</body>

</html>
