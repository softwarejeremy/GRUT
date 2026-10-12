
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('registroForm');
    const mensajeError = document.getElementById('mensajeError');

    const nombre = document.getElementById('nombre');
    const apellido = document.getElementById('apellido');
    const email = document.getElementById('email');
    const telefono = document.getElementById('telefono');
    const password = document.getElementById('password');
    const confirmPassword = document.getElementById('confirmPassword');
    const centro = document.getElementById('centro');
    const curso = document.getElementById('curso');

    const campos = [nombre, apellido, email, telefono, password, confirmPassword, centro, curso];

    function obtenerError(campo) {
        const val = campo.value.trim();

        if (val === '') {
            return 'Todos los campos son obligatorios.';
        }

        if (campo.type === 'email') {
            const regexEmail = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!regexEmail.test(val)) return 'Ingresa un correo electrónico válido.';
        }

        if (campo.id === 'telefono') {
            const regexTelefono = /^[2678]\d{3}-\d{4}$/;
            if (!regexTelefono.test(val)) return 'El teléfono debe llevar guion (ej: 7000-0000).';
        }

        if (campo.id === 'password') {
            if (val.length < 6) return 'La contraseña debe tener al menos 6 caracteres.';
        }

        if (campo.id === 'confirmPassword') {
            if (val !== password.value) return 'Las contraseñas no coinciden.';
        }

        return '';
    }

    campos.forEach(campo => {
        campo.addEventListener('blur', () => {
            mensajeError.textContent = '';
            const error = obtenerError(campo);
            if (error) {
                mensajeError.style.color = '#d9534f';
                mensajeError.textContent = error;
            }
        });

        campo.addEventListener('keydown', (evento) => {
            if (evento.key === 'Enter') {
                evento.preventDefault();
                mensajeError.textContent = '';
                const error = obtenerError(campo);
                if (error) {
                    mensajeError.style.color = '#d9534f';
                    mensajeError.textContent = error;
                } else {
                    mensajeError.textContent = '';
                }
            }
        });
    });

    form.addEventListener('submit', (evento) => {
        evento.preventDefault(); 
        mensajeError.textContent = '';
        mensajeError.style.color = '#d9534f';

        for (let campo of campos) {
            const error = obtenerError(campo);
            if (error) {
                mensajeError.textContent = error;
                campo.focus();
                return;
            }
        }

        mensajeError.style.color = '#5cb85c';
        mensajeError.textContent = '¡Registro válido! Procesando datos...';

        // Todo correcto: ahora sí se envía a php/procesar_register.php
        form.submit();
    });
});