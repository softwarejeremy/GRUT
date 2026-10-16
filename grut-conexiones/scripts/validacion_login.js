// Tarea 3 (Gustavo): validar el inicio de sesión y mostrar los mensajes dentro del formulario.
// Tarea 4 (Jeremy): si el PHP acepta, se va al panel que indica según el rol (resultado.redirect).
// Flujo: formulario -> esta validación -> php/iniciar_sesion.php (fetch) -> respuesta JSON -> mensaje.
document.addEventListener("DOMContentLoaded", () => {
    let form = document.getElementById("login");
    let email_login = document.getElementById("email_user");
    let passwordInput = document.getElementById("passwd");
    let mensajeError = document.getElementById("mensajeError");

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

    form.addEventListener("submit", async function(evento){
        evento.preventDefault(); // Evita que el navegador recargue o cambie de página
        // ---------- Validación antes de enviar ----------
        let email = email_login.value.trim();
        let password = passwordInput.value;

        if(email === "" || password === ""){
            mostrarAlerta("Por favor, completa todos los campos.");
            return;
        }
        const regexEmail = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if(!regexEmail.test(email)){
            mostrarAlerta("Ingresa un correo válido.");
            return;
        }

        if(password.length < 6){
            mostrarAlerta("Ingresa 6 caracteres o más para la contraseña.");
            return;
        }

        // Enviar datos en segundo plano con Fetch (AJAX)
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
                mostrarAlerta("¡Inicio de sesión exitoso! Redirigiendo...", true);
                setTimeout(() => {
                    window.location.href = resultado.redirect; // Redirige solo si es exitoso
                }, 1500);
            } else {
                mostrarAlerta(resultado.message); // Muestra el error en el cuadro flotante
            }
        } catch (error) {
            console.error("Error al iniciar sesión:", error);
            mostrarAlerta("No se pudo iniciar sesión. Revisa la consola y la conexión con el servidor.");
        }
    });
});