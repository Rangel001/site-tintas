<?php

require_once __DIR__ . "/config/auth.php";
require_once __DIR__ . "/config/conexao.php";

$tintas = $pdo->query(
    "SELECT tintas.*,
        EXISTS (
            SELECT 1 FROM fotos_tintas
            WHERE tinta_id = tintas.id
        ) AS tem_foto
     FROM tintas
     ORDER BY nome ASC"
)->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>AluStock | Estoque de tintas</title>

    <link rel="stylesheet" href="/site-fast/estoque-tintas/css/style.css?v=5">

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
                <h1>Estoque de tintas</h1>
                <p>Gerencie todas as tintas cadastradas.</p>
            </div>

            <a href="cadastrar.php" class="botao">
                + Nova tinta
            </a>

        </header>

        <section class="painel">

            <div class="filtros-estoque">

                <input type="text" id="pesquisa"
                    placeholder="Pesquisar tinta, código ou fabricante...">

                <select id="filtro-status">
                    <option value="todos">Todas as tintas</option>
                    <option value="disponivel">Disponíveis</option>
                    <option value="baixo">Estoque baixo</option>
                    <option value="esgotado">Esgotadas</option>
                </select>

            </div>

            <div class="lista-estoque">

                <?php foreach ($tintas as $tinta): ?>

                    <?php

                    if ($tinta['quantidade'] <= 0) {
                        $status = "esgotado";
                        $textoStatus = "Esgotado";

                    } elseif ($tinta['quantidade'] <= $tinta['estoque_minimo']) {
                        $status = "baixo";
                        $textoStatus = "Estoque baixo";

                    } else {
                        $status = "disponivel";
                        $textoStatus = "Disponível";
                    }

                    $cor = preg_match(
                        '/^#[0-9a-fA-F]{6}$/',
                        $tinta['cor_hex'] ?? ''
                    ) ? $tinta['cor_hex'] : '#FFFFFF';

                    ?>

                    <article class="tinta-card"
                        data-status="<?= $status ?>">



                        <?php if ($tinta['tem_foto']): ?>

                            <img class="foto-tinta"
                                src="foto.php?ver=1&id=<?= (int) $tinta['id'] ?>"
                                alt="Foto da tinta"
                                loading="lazy">

                        <?php endif; ?>

                        <a class="botao-foto"
                        href="foto.php?id=<?= (int) $tinta['id'] ?>">

                            <?= $tinta['tem_foto']
                                ? 'Alterar foto'
                                : '+ Adicionar foto' ?>

                        </a>


                        <div class="tinta-topo">

                            <div class="tinta-identificacao">

                                <div class="cor-grande"
                                    style="background-color: <?= $cor ?>">
                                </div>

                                <div>

                                    <h3>
                                        <?= htmlspecialchars($tinta['nome']) ?>
                                    </h3>

                                    <p>
                                        <?= htmlspecialchars($tinta['codigo'] ?: 'Sem código') ?>
                                    </p>

                                </div>

                            </div>

                            <div class="tinta-quantidade">

                                <h2>
                                    <?= number_format($tinta['quantidade'], 3, ',', '.') ?> kg
                                </h2>

                                <span class="status <?= $status ?>">
                                    <?= $textoStatus ?>
                                </span>

                            </div>

                        </div>

                        <div class="tinta-detalhes">

                            <div>
                                <span>Fabricante</span>
                                <strong>
                                    <?= htmlspecialchars($tinta['fabricante'] ?: 'Não informado') ?>
                                </strong>
                            </div>

                            <div>
                                <span>Acabamento</span>
                                <strong>
                                    <?= htmlspecialchars($tinta['acabamento'] ?: 'Não informado') ?>
                                </strong>
                            </div>

                            <div>
                                <span>Tipo de tinta</span>
                                <strong>
                                    <?= htmlspecialchars($tinta['tipo'] ?: 'Não informado') ?>
                                </strong>
                            </div>

                            <div>
                                <span>Localização</span>
                                <strong>
                                    <?= htmlspecialchars($tinta['localizacao'] ?: 'Não informada') ?>
                                </strong>
                            </div>

                            <div>
                                <span>Lote</span>
                                <strong>
                                    <?= htmlspecialchars($tinta['lote'] ?: 'Não informado') ?>
                                </strong>
                            </div>

                            <div>
                                <span>Validade</span>
                                <strong>
                                    <?= $tinta['validade']
                                        ? htmlspecialchars(date('d/m/Y', strtotime($tinta['validade'])))
                                        : 'Não informada' ?>
                                </strong>
                            </div>

                            <div>
                                <span>Estoque mínimo</span>
                                <strong>
                                    <?= number_format($tinta['estoque_minimo'], 3, ',', '.') ?> kg
                                </strong>
                            </div>

                            <div>
                                <span>Valor por kg</span>
                                <strong>
                                    <?= $tinta['valor_kg'] !== null
                                        ? 'R$ ' . number_format($tinta['valor_kg'], 2, ',', '.')
                                        : 'Não informado' ?>
                                </strong>
                            </div>

                        </div>

                        
                        <div class="tinta-acoes">

                            <a class="acao entrada"
                            href="entrada.php?id=<?= (int) $tinta['id'] ?>">
                            + Entrada
                            </a>

                            <a class="acao saida"
                            href="saida.php?id=<?= (int) $tinta['id'] ?>">
                            − Saída
                            </a>

                            <a class="acao editar"
                            href="editar.php?id=<?= (int) $tinta['id'] ?>">
                            Editar
                            </a>

                            <a class="acao excluir"
                            href="excluir.php?id=<?= (int) $tinta['id'] ?>">
                            Excluir
                            </a>

                        </div>

                    </article>

                <?php endforeach; ?>

                <?php if (empty($tintas)): ?>

                    <div class="vazio">
                        <h3>Nenhuma tinta cadastrada</h3>
                        <p>Cadastre uma tinta para começar.</p>
                    </div>

                <?php endif; ?>

                <div id="sem-resultados" class="vazio" hidden>
                    Nenhuma tinta encontrada.
                </div>

            </div>

        </section>

    </main>

    <script src="/site-fast/estoque-tintas/js/script.js?v=5"></script>

</body>

</html>
