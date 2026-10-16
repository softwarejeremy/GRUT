// Editar el perfil desde el panel (estudiante y conductor).
// Tarea 8 (Fabiola): perfil del estudiante -> php/actualizar_perfil.php
// Tarea 9 (Bryan): perfil del conductor   -> php/conductor_editar_perfil.php
//
// Primer clic en "Editar información": se habilitan los campos.
// Segundo clic ("Guardar cambios"): se validan los datos y, si están bien, se envía el formulario.
// El PHP vuelve a validar todo; esta validación solo evita enviar datos incorrectos.
document.addEventListener("DOMContentLoaded", function () {
    const form = document.querySelector("form[data-perfil]");
    if (!form) return;
    const boton = document.getElementById("btn-editar");
    const aviso = document.getElementById("aviso-perfil");

    // Campos que se pueden editar: los que tienen name (correo e id no tienen y no se envían)
    const campos = Array.from(form.querySelectorAll("input[name]"));

    // Regla de cada campo: expresión regular y mensaje si no se cumple
    const letras = /^[A-Za-zÁÉÍÓÚÜÑáéíóúüñ][A-Za-zÁÉÍÓÚÜÑáéíóúüñ\s'.-]{1,99}$/;
    const reglas = {
        "nombre": [letras, "El nombre debe tener de 2 a 100 letras."],
        "apellido": [letras, "El apellido debe tener de 2 a 100 letras."],
        "telefono": [/^[2678]\d{3}-\d{4}$/, "El teléfono debe tener el formato 7000-0000 y empezar con 2, 6, 7 u 8."],
        "num-licencia": [/^.{3,50}$/, "La licencia debe tener de 3 a 50 caracteres."],
        "curso": [/^.{1,50}$/, "El curso es obligatorio (máximo 50 caracteres)."],
        "centro": [/^.{1,150}$/, "El centro educativo es obligatorio (máximo 150 caracteres)."]
    };

    function mostrarError(mensaje) {
        aviso.textContent = mensaje;
        aviso.hidden = mensaje === "";
    }

    // Devuelve true si todos los campos están bien; si no, marca el primero que falla
    function validar() {
        for (const campo of campos) {
            campo.value = campo.value.trim();
            const regla = reglas[campo.name];
            if (regla && !regla[0].test(campo.value)) {
                mostrarError(regla[1]);
                campo.setAttribute("aria-invalid", "true");
                campo.focus();
                return false;
            }
            campo.removeAttribute("aria-invalid");
        }
        mostrarError("");
        return true;
    }

    let editando = false;
    boton.addEventListener("click", function () {
        if (!editando) {
            campos.forEach(function (campo) { campo.removeAttribute("readonly"); });
            boton.textContent = "Guardar cambios";
            editando = true;
            campos[0].focus();
        } else if (validar()) {
            form.submit();
        }
    });

    // Si se pulsa Enter dentro de un campo, también se valida antes de enviar
    form.addEventListener("submit", function (e) {
        if (!editando || !validar()) e.preventDefault();
    });
});
