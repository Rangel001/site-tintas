
const pesquisa = document.getElementById("pesquisa");
const filtroStatus = document.getElementById("filtro-status");

function normalizar(texto) {
    return texto.toLowerCase()
        .normalize("NFD")
        .replace(/[\u0300-\u036f]/g, "");
}

function filtrarTintas() {

    const texto = normalizar(pesquisa ? pesquisa.value : "");

    const statusSelecionado = filtroStatus
        ? filtroStatus.value
        : "todos";

    // Pesquisa do Dashboard

    const linhas = document.querySelectorAll(
        "#lista-tintas tr"
    );

    linhas.forEach(function (linha) {

        const conteudo = normalizar(linha.textContent);

        linha.style.display = conteudo.includes(texto)
            ? ""
            : "none";

    });

    // Pesquisa e filtros do estoque

    const cards = document.querySelectorAll(".tinta-card");

    let encontrados = 0;

    cards.forEach(function (card) {

        const conteudo = normalizar(card.textContent);
        const status = card.dataset.status;

        const correspondeTexto = conteudo.includes(texto);

        const correspondeStatus =
            statusSelecionado === "todos" ||
            status === statusSelecionado;

        const mostrar = correspondeTexto && correspondeStatus;

        card.style.display = mostrar ? "" : "none";

        if (mostrar) {
            encontrados++;
        }

    });

    const mensagem = document.getElementById("sem-resultados");

    if (mensagem) {
        mensagem.hidden = cards.length === 0 || encontrados > 0;
    }

}

if (pesquisa) {
    pesquisa.addEventListener("input", filtrarTintas);
}

if (filtroStatus) {
    filtroStatus.addEventListener("change", filtrarTintas);
}
