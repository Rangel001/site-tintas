const temaSalvo = localStorage.getItem("tema") || "claro";

document.documentElement.dataset.tema = temaSalvo;

document.addEventListener("DOMContentLoaded", function () {

    const menu = document.querySelector(".sidebar");

    if (!menu) return;

    const botao = document.createElement("button");

    botao.className = "botao-tema";

    function atualizarBotao() {
        botao.textContent =
            document.documentElement.dataset.tema === "escuro"
                ? "☀ Modo claro"
                : "☾ Modo escuro";
    }

    botao.addEventListener("click", function () {

        const temaAtual = document.documentElement.dataset.tema;

        const novoTema =
            temaAtual === "escuro" ? "claro" : "escuro";

        document.documentElement.dataset.tema = novoTema;

        localStorage.setItem("tema", novoTema);

        atualizarBotao();
    });

    atualizarBotao();

    menu.appendChild(botao);
});