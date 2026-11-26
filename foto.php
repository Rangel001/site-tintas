
<?php

require_once __DIR__ . "/config/auth.php";
require_once __DIR__ . "/config/conexao.php";

$id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

if (!$id) {
    header("Location: estoque.php");
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM tintas WHERE id = ?");
$stmt->execute([$id]);

$tinta = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$tinta) {
    exit("Tinta não encontrada.");
}

$stmt = $pdo->prepare(
    "SELECT imagem, mime FROM fotos_tintas WHERE tinta_id = ?"
);

$stmt->execute([$id]);

$foto = $stmt->fetch(PDO::FETCH_ASSOC);

// Exibir a imagem

if (isset($_GET["ver"])) {

    if (!$foto || empty($foto["imagem"])) {
        http_response_code(404);
        exit;
    }

    $imagem = $foto["imagem"];

    if (is_resource($imagem)) {
        $imagem = stream_get_contents($imagem);
    }

    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    header("Content-Type: " . $foto["mime"]);
    header("Content-Length: " . strlen($imagem));
    header("Content-Disposition: inline");
    header("Cache-Control: no-store");
    header("X-Content-Type-Options: nosniff");

    echo $imagem;
    exit;
}

$erro = "";

// Salvar ou remover a foto

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (!hash_equals(
        $_SESSION["csrf"],
        $_POST["csrf"] ?? ""
    )) {

        $erro = "Solicitação inválida.";

    } elseif (isset($_POST["remover"])) {

        $stmt = $pdo->prepare(
            "DELETE FROM fotos_tintas WHERE tinta_id = ?"
        );

        $stmt->execute([$id]);

        header("Location: estoque.php");
        exit;

    } elseif (
        !isset($_FILES["foto"]) ||
        $_FILES["foto"]["error"] !== UPLOAD_ERR_OK ||
        $_FILES["foto"]["size"] > 5 * 1024 * 1024
    ) {

        $erro = "Selecione uma imagem de até 5 MB.";

    } else {

        $arquivo = $_FILES["foto"]["tmp_name"];

        $info = getimagesize($arquivo);

        $mime = $info["mime"] ?? "";

        if (!in_array($mime, [
            "image/jpeg",
            "image/png",
            "image/webp"
        ], true)) {

            $erro = "Utilize uma imagem JPG, PNG ou WEBP.";

        } else {

            try {

                $stmt = $pdo->prepare(
                    "INSERT OR REPLACE INTO fotos_tintas
                    (tinta_id, imagem, mime)
                    VALUES (?, ?, ?)"
                );

                $stmt->bindValue(
                    1, $id, PDO::PARAM_INT
                );

                $stmt->bindValue(
                    2,
                    file_get_contents($arquivo),
                    PDO::PARAM_LOB
                );

                $stmt->bindValue(3, $mime);

                $stmt->execute();

                header("Location: estoque.php");
                exit;

            } catch (PDOException $e) {

                $erro = "Erro ao salvar: " . $e->getMessage();

            }
        }
    }
}

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>AluStock | Foto da tinta</title>

    <link rel="stylesheet"
          href="/site-fast/estoque-tintas/css/style.css?v=12">

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
                <h1>Foto da tinta</h1>
                <p>Adicione ou altere a fotografia.</p>
            </div>

            <a href="estoque.php" class="botao-secundario">
                Voltar
            </a>

        </header>

        <section class="painel painel-foto">

            <h2><?= htmlspecialchars($tinta["nome"]) ?></h2>

            <?php if ($erro): ?>

                <div class="mensagem-erro">
                    <?= htmlspecialchars($erro) ?>
                </div>

            <?php endif; ?>

            <div class="area-foto">

                <img id="preview"
                     src="<?= $foto
                         ? 'foto.php?ver=1&id=' . $id
                         : '' ?>"
                     <?= !$foto ? 'hidden' : '' ?>
                     alt="Foto da tinta">

                <p id="sem-foto"
                   <?= $foto ? 'hidden' : '' ?>>
                    Nenhuma foto adicionada
                </p>

            </div>

            <form method="POST"
                  enctype="multipart/form-data">

                <input type="hidden" name="csrf"
                       value="<?= htmlspecialchars($_SESSION["csrf"]) ?>">

                <div class="campo">

                    <label>Selecionar fotografia</label>

                    <input type="file"
                           name="foto"
                           id="arquivo-foto"
                           accept="image/jpeg,image/png,image/webp"
                           required>

                    <small>
                        Formatos JPG, PNG e WEBP.
                        Tamanho máximo: 5 MB.
                    </small>

                </div>

                <div class="form-acoes">

                    <a href="estoque.php" class="botao-secundario">
                        Cancelar
                    </a>

                    <button type="submit" class="botao">
                        Salvar foto
                    </button>

                </div>

            </form>

            <?php if ($foto): ?>

                <form method="POST"
                      onsubmit="return confirm('Deseja remover a foto?')">

                    <input type="hidden" name="csrf"
                           value="<?= htmlspecialchars($_SESSION["csrf"]) ?>">

                    <button type="submit"
                            name="remover"
                            class="botao botao-excluir remover-foto">

                        Remover foto

                    </button>

                </form>

            <?php endif; ?>

        </section>

    </main>

    <script>

        const arquivo = document.getElementById("arquivo-foto");
        const preview = document.getElementById("preview");
        const semFoto = document.getElementById("sem-foto");

        let urlTemporaria = null;

        arquivo.addEventListener("change", function () {

            const foto = this.files[0];

            if (!foto) return;

            if (urlTemporaria) {
                URL.revokeObjectURL(urlTemporaria);
            }

            urlTemporaria = URL.createObjectURL(foto);

            preview.src = urlTemporaria;
            preview.hidden = false;
            semFoto.hidden = true;

        });

    </script>

</body>
</html>
