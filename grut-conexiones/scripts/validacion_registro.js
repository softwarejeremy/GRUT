// Tarea 3 (Gustavo): validar el registro y mostrar los mensajes dentro del formulario.
// Tarea 2 (Jeremy): si el registro sale bien, se va a iniciar_sesion.html.
// Flujo: formulario -> esta validación -> php/procesar_register.php (fetch) -> respuesta JSON -> mensaje.
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('registroForm');
    const mensajeError = document.getElementById('mensajeError');

    // Muestra el aviso #mensajeError (rojo = error, verde = éxito) durante 4 segundos
    function mostrarAlerta(texto, esExito = false) {
        mensajeError.textContent = texto;
        mensajeError.className = "";
        if (esExito) {
            mensajeError.classList.add("exito");
        }
        mensajeError.classList.add("mostrar");

        setTimeout(() => {
            mensajeError.classList.remove("mostrar");
        }, 4000);
    }

    const nombre = document.getElementById('nombre');
    const apellido = document.getElementById('apellido');
    const email = document.getElementById('email');
    const telefono = document.getElementById('telefono');
    const password = document.getElementById('password');
    const confirmPassword = document.getElementById('confirmPassword');
    const centro = document.getElementById('centro');
    const curso = document.getElementById('curso');

    const campos = [nombre, apellido, email, telefono, password, confirmPassword, centro, curso];

    // Devuelve el mensaje de error de un campo, o '' si está bien
    function obtenerError(campo) {
        const val = campo.value.trim();
        if (val === '') return 'Todos los campos son obligatorios.';
        if ((campo.id === 'nombre' || campo.id === 'apellido') && !/^[A-Za-zÁÉÍÓÚÜÑáéíóúüñ][A-Za-zÁÉÍÓÚÜÑáéíóúüñ\s'.-]{1,99}$/.test(val)) {
            return 'El nombre y el apellido deben tener de 2 a 100 letras.';
        }
        if (campo.type === 'email') {
            const regexEmail = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!regexEmail.test(val)) return 'Ingresa un correo electrónico válido.';
        }
        if (campo.id === 'telefono') {
            const regexTelefono = /^[2678]\d{3}-\d{4}$/;
            if (!regexTelefono.test(val)) return 'El teléfono debe llevar guion (ej: 7000-0000).';
        }
        if (campo.id === 'password' && val.length < 6) return 'La contraseña debe tener al menos 6 caracteres.';
        if (campo.id === 'confirmPassword' && val !== password.value) return 'Las contraseñas no coinciden.';
        return '';
    }

    form.addEventListener('submit', async (evento) => {
        evento.preventDefault(); 

        for (let campo of campos) {
            const error = obtenerError(campo);
            if (error) {
                mostrarAlerta(error);
                campo.focus();
                return;
            }
        }

        mostrarAlerta('Procesando registro...', true);

        try {
            const formData = new FormData(form);
            const response = await fetch(form.action, {
                method: "POST",
                body: formData
            });

            let resultado;
            try {
                resultado = await response.json();
            } catch (error) {
                throw new Error(`El servidor devolvió una respuesta no válida (HTTP ${response.status}).`);
            }

            if (!resultado || typeof resultado.success !== "boolean") {
                throw new Error(`El servidor devolvió datos inesperados (HTTP ${response.status}).`);
            }
            if (!response.ok && resultado.success) {
                throw new Error(`El servidor indicó un error (HTTP ${response.status}).`);
            }

            if (resultado.success) {
                mostrarAlerta("¡Registro exitoso! Redirigiendo al login...", true);
                setTimeout(() => {
                    window.location.href = "iniciar_sesion.html";   // tarea 2
                }, 2000);
            } else {
                mostrarAlerta(resultado.message);
            }
        } catch (error) {
            console.error("Error al procesar el registro:", error);
            mostrarAlerta("No se pudo completar el registro. Revisa la consola y la conexión con el servidor.");
        }
    });
});