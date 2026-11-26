
<?php
require_once "config/conexao.php";

session_start();

if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

$erro = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $nome = trim($_POST["nome"] ?? "");
    $codigo = trim($_POST["codigo"] ?? "");
    $fabricante = trim($_POST["fabricante"] ?? "");
    $acabamento = $_POST["acabamento"] ?? "";
    $tipo = $_POST["tipo"] ?? "";
    $quantidade = $_POST["quantidade"] ?? "";
    $estoque_minimo = $_POST["estoque_minimo"] ?? "";
    $lote = trim($_POST["lote"] ?? "");
    $validade = ($_POST["validade"] ?? "") ?: null;
    $localizacao = trim($_POST["localizacao"] ?? "");
    $cor_hex = $_POST["cor_hex"] ?? "#000000";
    $valor_kg = $_POST["valor_kg"] ?? "";

    // Valores padrão para campos vazios

    if ($quantidade === "") {
        $quantidade = 0;
    }

    if ($estoque_minimo === "") {
        $estoque_minimo = 5;
    }

    if (!preg_match('/^#[0-9a-fA-F]{6}$/', $cor_hex)) {
        $cor_hex = "#000000";
    }

    // Validação dos dados

    if (!hash_equals($_SESSION['csrf'], $_POST['csrf'] ?? '')) {

        $erro = "Solicitação inválida.";

    } elseif ($nome === "") {

        $erro = "Informe o nome da tinta.";

    } elseif (
        !is_numeric($quantidade) ||
        !is_numeric($estoque_minimo) ||
        $quantidade < 0 ||
        $estoque_minimo < 0 ||
        ($valor_kg !== "" && 
        (!is_numeric($valor_kg) || $valor_kg < 0))
    ) {

        $erro = "Informe valores numéricos válidos.";

    } else {

        try {

            $pdo->beginTransaction();

            $sql = "INSERT INTO tintas (
                nome,
                codigo,
                fabricante,
                acabamento,
                tipo,
                quantidade,
                estoque_minimo,
                lote,
                validade,
                localizacao,
                cor_hex,
                valor_kg
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

            $stmt = $pdo->prepare($sql);

            $stmt->execute([
                $nome,
                $codigo,
                $fabricante,
                $acabamento,
                $tipo,
                $quantidade,
                $estoque_minimo,
                $lote,
                $validade,
                $localizacao,
                $cor_hex,
                $valor_kg === "" ? null : $valor_kg
            ]);

            $tinta_id = $pdo->lastInsertId();

            // Registra a entrada inicial no estoque

            if ($quantidade > 0) {

                $stmt = $pdo->prepare(
                    "INSERT INTO movimentacoes
                    (tinta_id, tipo, quantidade, observacao)
                    VALUES (?, 'entrada', ?, ?)"
                );

                $stmt->execute([
                    $tinta_id,
                    $quantidade,
                    "Cadastro inicial da tinta"
                ]);
            }

            $pdo->commit();

            header("Location: index.php");
            exit;

        } catch (PDOException $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $erro = "Não foi possível cadastrar a tinta.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>AluStock | Cadastrar tinta</title>

    <link rel="stylesheet" href="/site-fast/estoque-tintas/css/style.css?v=4">

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
            <a href="estoque.php">Estoque de tintas</a>
            <a href="cadastrar.php" class="ativo">Cadastrar tinta</a>
            <a href="movimentacoes.php">Movimentações</a>
        </nav>

    </aside>

    <main class="conteudo">

        <header class="cabecalho">

            <div>
                <h1>Cadastrar tinta</h1>
                <p>Adicione uma nova tinta ao estoque.</p>
            </div>

            <a href="index.php" class="botao-secundario">
                Voltar
            </a>

        </header>

        <section class="painel">

            <h2>Informações da tinta</h2>

            <p style="color: #64748b; margin-bottom: 25px;">
                Apenas o nome da tinta é obrigatório.
                Os demais campos são opcionais.
            </p>

            <?php if ($erro): ?>

                <div class="mensagem-erro">
                    <?= htmlspecialchars($erro) ?>
                </div>

            <?php endif; ?>

            <form method="POST">

                <input type="hidden" name="csrf"
                    value="<?= htmlspecialchars($_SESSION['csrf']) ?>">

                <div class="form-grid">

                    <!-- NOME -->

                    <div class="campo">

                        <label>Nome da tinta *</label>

                        <input type="text"
                            name="nome"
                            placeholder="Ex: Preto Fosco"
                            required>

                    </div>

                    <!-- CÓDIGO -->

                    <div class="campo">

                        <label>Código da tinta</label>

                        <input type="text"
                            name="codigo"
                            placeholder="Ex: RAL 9005">

                    </div>

                    <!-- FABRICANTE -->

                    <div class="campo">

                        <label>Fabricante</label>

                        <input type="text"
                            name="fabricante"
                            placeholder="Nome do fabricante">

                    </div>

                    <!-- ACABAMENTO -->

                    <div class="campo">

                        <label>Acabamento</label>

                        <select name="acabamento">

                            <option value="">Selecione</option>
                            <option>Fosco</option>
                            <option>Brilhante</option>
                            <option>Semibrilho</option>
                            <option>Acetinado</option>
                            <option>Texturizado</option>
                            <option>Metalizado</option>
                            <option>Outro</option>

                        </select>

                    </div>

                    <!-- TIPO -->

                    <div class="campo">

                        <label>Tipo de tinta</label>

                        <select name="tipo">

                            <option value="">Selecione</option>
                            <option>Poliéster</option>
                            <option>Epóxi</option>
                            <option>Híbrida</option>
                            <option>Outro</option>

                        </select>

                    </div>

                    <!-- COR -->

                    <div class="campo">

                        <label>Cor aproximada</label>

                        <input type="color"
                            name="cor_hex"
                            value="#000000">

                    </div>

                    <!-- QUANTIDADE -->

                    <div class="campo">

                        <label>Quantidade em estoque (kg)</label>

                        <input type="number"
                            name="quantidade"
                            step="0.001"
                            min="0"
                            placeholder="Ex: 25">

                    </div>

                    <!-- ESTOQUE MÍNIMO -->

                    <div class="campo">

                        <label>Estoque mínimo (kg)</label>

                        <input type="number"
                            name="estoque_minimo"
                            step="0.001"
                            min="0"
                            placeholder="Padrão: 5 kg">

                    </div>

                    <!-- LOTE -->

                    <div class="campo">

                        <label>Número do lote</label>

                        <input type="text"
                            name="lote"
                            placeholder="Código do lote">

                    </div>

                    <!-- VALIDADE -->

                    <div class="campo">

                        <label>Data de validade</label>

                        <input type="date"
                            name="validade">

                    </div>

                    <!-- LOCALIZAÇÃO -->

                    <div class="campo">

                        <label>Localização no estoque</label>

                        <input type="text"
                            name="localizacao"
                            placeholder="Ex: Prateleira A3">

                    </div>

                    <!-- VALOR -->

                    <div class="campo">

                        <label>Valor por kg (R$)</label>

                        <input type="number"
                            name="valor_kg"
                            step="0.01"
                            min="0"
                            placeholder="Ex: 35.90">

                    </div>

                </div>

                <div class="form-acoes">

                    <a href="index.php" class="botao-secundario">
                        Cancelar
                    </a>

                    <button type="submit" class="botao">
                        Cadastrar tinta
                    </button>

                </div>

            </form>

        </section>

    </main>

</body>

</html>
