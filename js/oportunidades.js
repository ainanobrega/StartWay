
document.addEventListener("DOMContentLoaded", function () {

    // Abas para alternar entre Cursos e Vagas.
    const abas = document.querySelectorAll(".op-aba");
    const listas = document.querySelectorAll(".op-lista");

    abas.forEach(function (aba) {
        aba.addEventListener("click", function () {

            const alvo = aba.dataset.alvo;

            // Atualiza a aba selecionada.
            abas.forEach(function (item) {
                const estaAtiva = item === aba;

                item.classList.toggle("ativa", estaAtiva);
                item.setAttribute(
                    "aria-selected",
                    String(estaAtiva)
                );
            });

            // Mostra a lista selecionada.
            listas.forEach(function (lista) {
                const estaAtiva = lista.id === alvo;

                lista.hidden = !estaAtiva;
                lista.classList.toggle("ativa", estaAtiva);
            });
        });
    });

    // Configura a rotação dos cartões.
    listas.forEach(function (lista) {

        const cartoes = Array.from(
            lista.querySelectorAll(".op-card")
        );

        const pontos = Array.from(
            lista.querySelectorAll(".op-ponto")
        );

        // Sem pelo menos dois cartões, não há rotação.
        if (cartoes.length < 2) {
            return;
        }

        let indiceAtual = 0;
        let temporizador = null;

        function mostrarCartao(indice) {

            // Volta ao início ao chegar ao último cartão.
            indiceAtual =
                (indice + cartoes.length) % cartoes.length;

            cartoes.forEach(function (cartao, indice) {
                cartao.classList.toggle(
                    "visivel",
                    indice === indiceAtual
                );
            });

            pontos.forEach(function (ponto, indice) {
                ponto.classList.toggle(
                    "ativo",
                    indice === indiceAtual
                );
            });
        }

        function pararRotacao() {
            if (temporizador !== null) {
                window.clearInterval(temporizador);
                temporizador = null;
            }
        }

        function iniciarRotacao() {
            pararRotacao();

            temporizador = window.setInterval(function () {
                mostrarCartao(indiceAtual + 1);
            }, 5500);
        }

        const botaoAnterior =
            lista.querySelector("[data-anterior]");

        const botaoProximo =
            lista.querySelector("[data-proximo]");

        // Cartão anterior.
        if (botaoAnterior) {
            botaoAnterior.addEventListener("click", function () {
                mostrarCartao(indiceAtual - 1);
                iniciarRotacao();
            });
        }

        // Próximo cartão.
        if (botaoProximo) {
            botaoProximo.addEventListener("click", function () {
                mostrarCartao(indiceAtual + 1);
                iniciarRotacao();
            });
        }

        // Permite selecionar um cartão pelos pontos.
        pontos.forEach(function (ponto) {
            ponto.addEventListener("click", function () {

                const indiceEscolhido =
                    Number(ponto.dataset.ir || 0);

                mostrarCartao(indiceEscolhido);
                iniciarRotacao();
            });
        });

        // Pausa enquanto o cursor está sobre os cartões.
        lista.addEventListener("mouseenter", pararRotacao);
        lista.addEventListener("mouseleave", iniciarRotacao);

        // Pausa durante a navegação por teclado.
        lista.addEventListener("focusin", pararRotacao);
        lista.addEventListener("focusout", iniciarRotacao);

        // Pausa quando outra aba do navegador está ativa.
        document.addEventListener("visibilitychange", function () {
            if (document.hidden) {
                pararRotacao();
            } else {
                iniciarRotacao();
            }
        });

        iniciarRotacao();
    });
});
