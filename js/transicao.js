document.addEventListener("DOMContentLoaded", function () {
    const formularioLogin = document.querySelector('.form-box form[method="POST"]');
    const telaLogin = document.getElementById("transicaoLogin");
    if (formularioLogin && telaLogin) {
        formularioLogin.addEventListener("submit", function () {
            try { sessionStorage.setItem("startwayTransicao", "1"); } catch (e) {}
            telaLogin.classList.add("ativo");
        });
    }

    const telaHome = document.getElementById("transicaoLogo");
    if (telaHome) {
        let mostrar = false;
        try { mostrar = sessionStorage.getItem("startwayTransicao") === "1"; sessionStorage.removeItem("startwayTransicao"); } catch (e) {}
        if (mostrar) {
            telaHome.classList.add("ativo");
            window.setTimeout(function () { telaHome.classList.remove("ativo"); }, 900);
        }
    }
});
