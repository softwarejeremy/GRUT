// Tarea 12 (Bryan): agregar, editar y borrar en las páginas del administrador.
// Basado en el script de conductor_administrador.php de Bryan.
// La página debe tener: <body data-csrf="...">, filas <tr data-id="..." data-nombre="...">,
// campos con data-campo, el diálogo #dlgNuevo con #formNuevo y el aviso #toast.
// Si una página tiene dos tablas, la segunda indica sus acciones con
// <table data-actualizar="..." data-eliminar="..."> y sus formularios con
// <form class="form-crud" data-accion="..."> dentro de un <dialog>, abierto por <button data-abre="id-del-dialog">.
document.addEventListener("DOMContentLoaded", function () {
    const CSRF = document.body.dataset.csrf;
    const toast = document.getElementById("toast");

    function aviso(mensaje, esError) {
        // Si hay un formulario (dialog) abierto, el aviso se pone dentro para que se vea encima
        const abierto = document.querySelector("dialog[open]");
        (abierto || document.body).appendChild(toast);
        toast.textContent = mensaje;
        toast.className = esError ? "error" : "";
        toast.style.display = "block";
        setTimeout(function () { toast.style.display = "none"; }, 3000);
    }

    async function api(datos) {
        const fd = new FormData();
        fd.append("csrf", CSRF);
        for (const [clave, valor] of Object.entries(datos)) fd.append(clave, valor);
        try {
            const respuesta = await fetch(location.pathname, { method: "POST", body: fd });
            return await respuesta.json();
        } catch (error) {
            return { ok: false, msg: "No se pudo conectar con el servidor." };
        }
    }

    // ---------- Validación con JavaScript antes de enviar al PHP ----------
    // Las reglas están en los atributos HTML de cada campo (required, minlength, maxlength,
    // pattern, type="email", min, max...) y el mensaje para el usuario en data-error.
    // El PHP vuelve a validar todo: esto solo evita enviar datos incorrectos.
    function mensajeDeError(campo) {
        if (campo.type !== "password" && typeof campo.value === "string" && campo.tagName !== "SELECT") {
            campo.value = campo.value.trim();
        }
        if (campo.checkValidity()) return "";
        return campo.dataset.error || campo.validationMessage;
    }

    // Revisa todos los campos de un formulario; marca y enfoca el primero que esté mal
    function formularioValido(f) {
        for (const campo of f.querySelectorAll("input, select")) {
            const mensaje = mensajeDeError(campo);
            campo.toggleAttribute("aria-invalid", mensaje !== "");
            if (mensaje !== "") {
                aviso(mensaje, true);
                campo.focus();
                return false;
            }
        }
        return true;
    }

    // ---------- Editar un campo con el lápiz ----------
    document.querySelectorAll(".edit-btn").forEach(function (boton) {
        const campo = boton.previousElementSibling;
        let original = campo.value;

        boton.addEventListener("click", function () {
            original = campo.value;
            campo.readOnly = false;
            campo.focus();
        });

        async function guardar() {
            if (campo.readOnly) return;
            // Se valida mientras el campo todavía es editable (un campo readonly no se valida)
            const error = campo.value.trim() === original ? "" : mensajeDeError(campo);
            campo.readOnly = true;
            if (error !== "") {
                aviso(error, true);
                campo.value = original;
                return;
            }
            if (campo.value === original) return;

            const res = await api({
                accion: campo.closest("table").dataset.actualizar || "actualizar",
                id: campo.closest("tr").dataset.id,
                campo: campo.dataset.campo,
                valor: campo.value
            });
            aviso(res.msg, !res.ok);
            if (res.ok) {
                original = campo.value;
            } else {
                campo.value = original;
            }
        }

        campo.addEventListener("blur", guardar);
        campo.addEventListener("keydown", function (e) {
            if (e.key === "Enter") campo.blur();
            if (e.key === "Escape") { campo.value = original; campo.blur(); }
        });
    });

    // ---------- Listas desplegables: se guardan al cambiar ----------
    document.querySelectorAll("tr[data-id] select[data-campo]").forEach(function (lista) {
        let original = lista.value;
        lista.addEventListener("change", async function () {
            const res = await api({
                accion: lista.closest("table").dataset.actualizar || "actualizar",
                id: lista.closest("tr").dataset.id,
                campo: lista.dataset.campo,
                valor: lista.value
            });
            aviso(res.msg, !res.ok);
            if (res.ok) {
                original = lista.value;
            } else {
                lista.value = original;
            }
        });
    });

    // ---------- Botones de una acción (ej: "Marcar como pagada") ----------
    document.querySelectorAll("tr[data-id] [data-accion]").forEach(function (boton) {
        boton.addEventListener("click", async function () {
            const res = await api({ accion: boton.dataset.accion, id: boton.closest("tr").dataset.id });
            aviso(res.msg, !res.ok);
            if (res.ok) setTimeout(function () { location.reload(); }, 600);
        });
    });

    // ---------- Modo borrar ----------
    const btnBorrar = document.getElementById("btnBorrar");
    if (btnBorrar) {
        btnBorrar.addEventListener("click", function () {
            document.body.classList.toggle("modo-borrar");
        });
    }

    document.querySelectorAll(".del-btn").forEach(function (boton) {
        boton.addEventListener("click", async function () {
            const fila = boton.closest("tr");
            if (!confirm("¿Borrar " + fila.dataset.nombre + "?")) return;
            const res = await api({ accion: fila.closest("table").dataset.eliminar || "eliminar", id: fila.dataset.id });
            aviso(res.msg, !res.ok);
            if (res.ok) fila.remove();
        });
    });

    // ---------- Formularios extra (ej: asignar una parada a una ruta) ----------
    document.querySelectorAll("[data-abre]").forEach(function (boton) {
        boton.addEventListener("click", function () {
            document.getElementById(boton.dataset.abre).showModal();
        });
    });
    document.querySelectorAll("form.form-crud").forEach(function (f) {
        f.noValidate = true;   // la validación la hace formularioValido() con nuestros mensajes
        f.querySelectorAll("[data-cierra]").forEach(function (boton) {
            boton.addEventListener("click", function () { f.reset(); f.closest("dialog").close(); });
        });
        f.addEventListener("submit", async function (e) {
            e.preventDefault();
            if (!formularioValido(f)) return;
            const datos = Object.fromEntries(new FormData(f));
            const res = await api(Object.assign({ accion: f.dataset.accion }, datos));
            aviso(res.msg, !res.ok);
            if (res.ok) setTimeout(function () { location.reload(); }, 600);
        });
    });

    // ---------- Nuevo registro ----------
    const dialogo = document.getElementById("dlgNuevo");
    const form = document.getElementById("formNuevo");
    const btnNuevo = document.getElementById("btnNuevo");
    if (dialogo && form && btnNuevo) {
        form.noValidate = true;   // la validación la hace formularioValido() con nuestros mensajes
        btnNuevo.addEventListener("click", function () { dialogo.showModal(); });
        document.getElementById("cancelarNuevo").addEventListener("click", function () {
            form.reset();
            dialogo.close();
        });
        form.addEventListener("submit", async function (e) {
            e.preventDefault();
            if (!formularioValido(form)) return;
            const datos = Object.fromEntries(new FormData(form));
            const res = await api(Object.assign({ accion: "crear" }, datos));
            aviso(res.msg, !res.ok);
            if (res.ok) setTimeout(function () { location.reload(); }, 600);
        });
    }
});
