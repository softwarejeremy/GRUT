// Tarea 13 (Andrea): lógica de la página configuracion.html
//   - tamaño de texto y modo claro/oscuro (se guardan en localStorage)
//   - pedir confirmación antes de desactivar la cuenta (php/desactivar_cuenta.php)
// El botón "Cerrar sesión" NO se toca aquí: es un enlace a php/cerrar_sesion.php (tarea 1, Jeremy).
document.addEventListener("DOMContentLoaded", function () {
    var raiz = document.documentElement;

    function guardar(clave, valor) {
        try { localStorage.setItem(clave, valor); } catch (e) {}
    }
    function leer(clave, porDefecto) {
        try { return localStorage.getItem(clave) || porDefecto; } catch (e) { return porDefecto; }
    }

    // ---------- Tamaño de texto ----------
    var radiosTexto = document.querySelectorAll('input[name="text-size"]');
    var textoActual = leer("grut_tamano_texto", "medium");

    radiosTexto.forEach(function (radio) {
        radio.checked = (radio.value === textoActual);
        radio.addEventListener("change", function () {
            if (radio.checked) {
                guardar("grut_tamano_texto", radio.value);
                raiz.setAttribute("data-texto", radio.value);
            }
        });
    });

    // ---------- Modo claro / oscuro ----------
    var radiosColor = document.querySelectorAll('input[name="color-mode"]');
    var temaActual = leer("grut_modo_color", "light");

    radiosColor.forEach(function (radio) {
        radio.checked = (radio.value === temaActual);
        radio.addEventListener("change", function () {
            if (radio.checked) {
                guardar("grut_modo_color", radio.value);
                raiz.setAttribute("data-tema", radio.value);
            }
        });
    });

    // ---------- Desactivar cuenta (pide confirmación) ----------
    var formDesactivar = document.getElementById("form-desactivar");
    if (formDesactivar) {
        formDesactivar.addEventListener("submit", function (evento) {
            var seguro = confirm("¿Seguro que quieres desactivar tu cuenta?");
            if (!seguro) {
                evento.preventDefault();   // cancelar: no se envía nada
            }
        });
    }
});
