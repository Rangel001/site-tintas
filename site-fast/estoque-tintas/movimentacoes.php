
<?php

require_once "config/conexao.php";

$movimentacoes = $pdo->query(
    "SELECT movimentacoes.*, 
            tintas.nome AS nome_tinta,
            tintas.codigo AS codigo_tinta,
            tintas.cor_hex
     FROM movimentacoes
     INNER JOIN tintas
        ON movimentacoes.tinta_id = tintas.id
     ORDER BY movimentacoes.id DESC"
)->fetchAll(PDO::FETCH_ASSOC);

$totalEntradas = $pdo->query(
    "SELECT COALESCE(SUM(quantidade), 0)
     FROM movimentacoes
     WHERE tipo = 'entrada'"
)->fetchColumn();

$totalSaidas = $pdo->query(
    "SELECT COALESCE(SUM(quantidade), 0)
     FROM movimentacoes
     WHERE tipo = 'saida'"
)->fetchColumn();

$totalMovimentacoes = count($movimentacoes);

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

    <title>AluStock | Movimentações</title>

    <link rel="stylesheet"
        href="/site-fast/estoque-tintas/css/style.css?v=8">

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
            <a href="cadastrar.php">Cadastrar tinta</a>
            <a href="movimentacoes.php" class="ativo">
                Movimentações
            </a>
        </nav>

    </aside>

    <main class="conteudo">

        <header class="cabecalho">

            <div>
                <h1>Movimentações</h1>
                <p>Acompanhe todas as entradas e saídas.</p>
            </div>

            <a href="estoque.php" class="botao">
                Gerenciar estoque
            </a>

        </header>

        <section class="cards cards-movimentacoes">

            <div class="card">
                <p>Total de entradas</p>
                <h2 class="texto-verde">
                    <?= number_format($totalEntradas, 3, ',', '.') ?> kg
                </h2>
            </div>

            <div class="card">
                <p>Total de saídas</p>
                <h2 class="texto-vermelho">
                    <?= number_format($totalSaidas, 3, ',', '.') ?> kg
                </h2>
            </div>

            <div class="card">
                <p>Total de movimentações</p>
                <h2><?= $totalMovimentacoes ?></h2>
            </div>

        </section>

        <section class="painel">

            <h2>Histórico de movimentações</h2>

            <div class="filtros-estoque">

                <input type="text"
                    id="pesquisa"
                    placeholder="Pesquisar tinta, código ou observação...">

                <select id="filtro-movimentacao">
                    <option value="todos">Todas as movimentações</option>
                    <option value="entrada">Entradas</option>
                    <option value="saida">Saídas</option>
                </select>

            </div>

            <div class="tabela-container">

                <table>

                    <thead>

                        <tr>
                            <th>Data e horário</th>
                            <th>Tinta</th>
                            <th>Código</th>
                            <th>Operação</th>
                            <th>Quantidade</th>
                            <th>Observação</th>
                        </tr>

                    </thead>

                    <tbody id="lista-movimentacoes">

                        <?php foreach ($movimentacoes as $mov): ?>

                            <?php

                            $entrada = $mov['tipo'] === 'entrada';

                            $cor = preg_match(
                                '/^#[0-9a-fA-F]{6}$/',
                                $mov['cor_hex'] ?? ''
                            ) ? $mov['cor_hex'] : '#FFFFFF';

                            ?>

                            <tr data-tipo="<?= texto($mov['tipo']) ?>">

                                <td>
                                    <?= texto(date(
                                        "d/m/Y H:i",
                                        strtotime($mov['data_movimentacao'])
                                    )) ?>
                                </td>

                                <td>

                                    <div class="mov-nome">

                                        <div class="amostra-cor"
                                            style="background-color: <?= $cor ?>">
                                        </div>

                                        <strong>
                                            <?= texto($mov['nome_tinta']) ?>
                                        </strong>

                                    </div>

                                </td>

                                <td>
                                    <?= texto($mov['codigo_tinta'] ?: '-') ?>
                                </td>

                                <td>

                                    <span class="status <?= $entrada
                                        ? 'disponivel'
                                        : 'esgotado' ?>">

                                        <?= $entrada ? 'Entrada' : 'Saída' ?>

                                    </span>

                                </td>

                                <td>

                                    <strong class="<?= $entrada
                                        ? 'texto-verde'
                                        : 'texto-vermelho' ?>">

                                        <?= $entrada ? '+' : '-' ?>

                                        <?= number_format(
                                            $mov['quantidade'],
                                            3,
                                            ',',
                                            '.'
                                        ) ?> kg

                                    </strong>

                                </td>

                                <td>
                                    <?= texto($mov['observacao'] ?: 'Sem observação') ?>
                                </td>

                            </tr>

                        <?php endforeach; ?>

                        <?php if (empty($movimentacoes)): ?>

                            <tr>
                                <td colspan="6" class="vazio">
                                    Nenhuma movimentação registrada.
                                </td>
                            </tr>

                        <?php endif; ?>

                    </tbody>

                </table>

                <div id="sem-movimentacoes" class="vazio" hidden>
                    Nenhuma movimentação encontrada.
                </div>

            </div>

        </section>

    </main>

    <script>

        const pesquisa = document.getElementById("pesquisa");

        const filtro = document.getElementById(
            "filtro-movimentacao"
        );

        function normalizar(texto) {

            return texto.toLowerCase()
                .normalize("NFD")
                .replace(/[\u0300-\u036f]/g, "");

        }

        function filtrarMovimentacoes() {

            const texto = normalizar(pesquisa.value);
            const tipo = filtro.value;

            const linhas = document.querySelectorAll(
                "#lista-movimentacoes tr[data-tipo]"
            );

            let encontrados = 0;

            linhas.forEach(function (linha) {

                const correspondeTexto = normalizar(
                    linha.textContent
                ).includes(texto);

                const correspondeTipo =
                    tipo === "todos" ||
                    linha.dataset.tipo === tipo;

                const mostrar =
                    correspondeTexto && correspondeTipo;

                linha.hidden = !mostrar;

                if (mostrar) {
                    encontrados++;
                }

            });

            document.getElementById(
                "sem-movimentacoes"
            ).hidden = linhas.length === 0 || encontrados > 0;

        }

        pesquisa.addEventListener(
            "input",
            filtrarMovimentacoes
        );

        filtro.addEventListener(
            "change",
            filtrarMovimentacoes
        );

    </script>

</body>

</html>
