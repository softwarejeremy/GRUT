let form = document.getElementById("login");
let email_login = document.getElementById("email_user");
let passwordInput = document.getElementById("passwd");
let mensajeError = document.getElementById("mensajeError");

form.addEventListener("submit", function(evento){
    evento.preventDefault();
    mensajeError.textContent = "";
    let email = email_login.value.trim();
    let password = passwordInput.value;

    if(email === "" || password === ""){
        mensajeError.textContent = "Por favor, completa todos los campos";
        return;
    }
    const regexEmail = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if(!regexEmail.test(email)){
        mensajeError.textContent = "Ingresa un correo válido.";
        return;
    }

    if(password.length < 6){
        mensajeError.textContent = "Ingresa 6 caracteres o más para la contraseña.";
        return;
    }

    form.submit();

});
