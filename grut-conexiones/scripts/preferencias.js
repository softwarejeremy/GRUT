// Aplica las preferencias guardadas (tamaño de texto y modo de color).
// Debe incluirse en el <head> de TODAS las páginas, SIN defer,
// para evitar el parpadeo de claro a oscuro al cargar.
(function () {
    try {
        var texto = localStorage.getItem("grut_tamano_texto") || "medium";
        var tema = localStorage.getItem("grut_modo_color") || "light";
        var raiz = document.documentElement;
        raiz.setAttribute("data-texto", texto);
        raiz.setAttribute("data-tema", tema);
    } catch (e) {
        // localStorage bloqueado: se usan los valores por defecto
    }
})();
