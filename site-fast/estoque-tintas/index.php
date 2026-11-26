
<?php
require_once "config/conexao.php";

$totalTintas = $pdo->query(
    "SELECT COUNT(*) FROM tintas"
)->fetchColumn();

$totalKg = $pdo->query(
    "SELECT COALESCE(SUM(quantidade), 0) FROM tintas"
)->fetchColumn();

$estoqueBaixo = $pdo->query(
    "SELECT COUNT(*) FROM tintas 
     WHERE quantidade <= estoque_minimo 
     AND quantidade > 0"
)->fetchColumn();

$esgotadas = $pdo->query(
    "SELECT COUNT(*) FROM tintas WHERE quantidade = 0"
)->fetchColumn();

$tintas = $pdo->query(
    "SELECT * FROM tintas ORDER BY nome"
)->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>AluStock | Dashboard</title>

    <link rel="stylesheet" href="/site-fast/estoque-tintas/css/style.css?v=2">

    <script src="/site-fast/estoque-tintas/js/tema.js"></script>

</head>
<body>

    <aside class="sidebar">
        <div class="logo">
            <h2>AluStock</h2>
            <p>Pintura Eletrostática</p>
        </div>

        <nav>
            <a href="index.php" class="ativo">Dashboard</a>
            <a href="estoque.php">Estoque de tintas</a>
            <a href="cadastrar.php">Cadastrar tinta</a>
            <a href="movimentacoes.php">Movimentações</a>
        </nav>

        <form method="POST" action="backup.php">

         <input type="hidden" name="csrf"
        value="<?= htmlspecialchars($_SESSION['csrf']) ?>">

        <button type="submit" class="botao-backup">
        ↓ Baixar backup
    </button>

</form>
        
        
        <a href="exportar.php" class="botao-excel">
        ↓ Exportar Excel
        </a>

    </aside>

    <main class="conteudo">

        <header class="cabecalho">
            <div>
                <h1>Dashboard</h1>
                <p>Gerenciamento de estoque de tintas</p>
            </div>

            <a href="cadastrar.php" class="botao">
                + Nova tinta
            </a>
        </header>

        <section class="cards">

            <div class="card">
                <p>Tintas cadastradas</p>
                <h2><?= $totalTintas ?></h2>
            </div>

            <div class="card">
                <p>Total em estoque</p>
                <h2><?= number_format($totalKg, 2, ',', '.') ?> kg</h2>
            </div>

            <div class="card alerta">
                <p>Estoque baixo</p>
                <h2><?= $estoqueBaixo ?></h2>
            </div>

            <div class="card perigo">
                <p>Tintas esgotadas</p>
                <h2><?= $esgotadas ?></h2>
            </div>

        </section>

        <section class="painel">

            <h2>Estoque de tintas</h2>

            <input type="text" id="pesquisa"
                placeholder="Pesquisar tinta, código ou fabricante...">

            <div class="tabela-container">
                <table>
                    <thead>
                        <tr>
                            <th>Cor</th>
                            <th>Nome</th>
                            <th>Código</th>
                            <th>Fabricante</th>
                            <th>Quantidade</th>
                            <th>Situação</th>
                        </tr>
                    </thead>

                    <tbody id="lista-tintas">

                        <?php foreach ($tintas as $tinta): ?>
                        <tr>
                            <td>
                                <div class="amostra-cor"
                                    style="background-color: <?= preg_match('/^#[0-9a-fA-F]{6}$/', $tinta['cor_hex'] ?? '') ? $tinta['cor_hex'] : '#FFFFFF' ?>">
                                </div>
                            </td>

                            <td><?= htmlspecialchars($tinta['nome']) ?></td>
                            <td><?= htmlspecialchars($tinta['codigo']) ?></td>
                            <td><?= htmlspecialchars($tinta['fabricante'] ?? '') ?></td>

                            <td>
                                <?= number_format($tinta['quantidade'], 2, ',', '.') ?> kg
                            </td>

                            <td>
                                <?php if ($tinta['quantidade'] == 0): ?>
                                    <span class="status esgotado">Esgotado</span>

                                <?php elseif ($tinta['quantidade'] <= $tinta['estoque_minimo']): ?>
                                    <span class="status baixo">Estoque baixo</span>

                                <?php else: ?>
                                    <span class="status disponivel">Disponível</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>

                        <?php if (empty($tintas)): ?>
                        <tr>
                            <td colspan="6" class="vazio">
                                Nenhuma tinta cadastrada.
                            </td>
                        </tr>
                        <?php endif; ?>

                    </tbody>
                </table>
            </div>

        </section>

    </main>

    <script src="js/script.js"></script>

</body>
</html>
