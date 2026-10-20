// ========================================
// HOME.JS — INTERAÇÕES DA PÁGINA INICIAL
// ========================================

document.addEventListener("DOMContentLoaded", function () {
    // Animação de entrada dos blocos
    const elementos = document.querySelectorAll(
        ".boas-vindas, .destaque, .secao-vagas, .bloco-painel"
    );

    elementos.forEach(function (elemento, indice) {
        elemento.style.animationDelay = (indice * 0.1) + "s";
    });
});