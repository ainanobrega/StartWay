// ========================================
// STARTWAY — MENU LATERAL (GAVETA)
// ========================================

document.addEventListener("DOMContentLoaded", function () {
    const botaoMenu = document.querySelector(".botao-menu");
    const menu = document.querySelector(".menu-lateral");
    const fundo = document.querySelector(".fundo-menu");
    const botaoFechar = document.querySelector(".botao-fechar-menu");

    if (!botaoMenu || !menu || !fundo) {
        console.error("Menu StartWay: confira botão, menu lateral e fundo no HTML.");
        return;
    }

    function abrirMenu() {
        menu.classList.add("aberto");
        fundo.classList.add("aberto");

        menu.setAttribute("aria-hidden", "false");
        botaoMenu.setAttribute("aria-expanded", "true");
        document.body.style.overflow = "hidden";

        if (botaoFechar) botaoFechar.focus();
    }

    function fecharMenu() {
        menu.classList.remove("aberto");
        fundo.classList.remove("aberto");

        menu.setAttribute("aria-hidden", "true");
        botaoMenu.setAttribute("aria-expanded", "false");
        document.body.style.overflow = "";

        botaoMenu.focus();
    }

    botaoMenu.addEventListener("click", abrirMenu);

    if (botaoFechar) botaoFechar.addEventListener("click", fecharMenu);

    fundo.addEventListener("click", fecharMenu);

    document.addEventListener("keydown", function (evento) {
        if (evento.key === "Escape" && menu.classList.contains("aberto")) {
            fecharMenu();
        }
    });

    // Se a janela for redimensionada com o menu aberto, mantém tudo consistente
    window.addEventListener("pageshow", function (evento) {
        if (evento.persisted && menu.classList.contains("aberto")) {
            fecharMenu();
        }
    });
});