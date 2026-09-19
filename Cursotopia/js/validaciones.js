function validarRegistro(event) {
  event.preventDefault();
  
  const nombre = document.getElementById('reg-name').value.trim();
  const email = document.getElementById('reg-email').value.trim();
  const password = document.getElementById('reg-password').value;
  const role = document.getElementById('reg-role').value;
  const errorMsg = document.getElementById('mensaje-error');

  if (!nombre || !email || !password) {
    errorMsg.innerText = 'Todos los campos son obligatorios.';
    return false;
  }

  const regexPassword = /^(?=.*[A-Z])(?=.*\d)(?=.*[$("@"#$%\&\&=?;:;,.+\-*\{\]B)]).{8,}$/;

  if (!regexPassword.test(password)) {
    errorMsg.innerText = 'La contraseña debe incluir al menos 8 caracteres, una mayúscula, un número y un carácter especial ($#%...).';
    return false;
  }

  const usuario = { name: nombre, email: email, role: role };
  localStorage.setItem('usuario_sesion', JSON.stringify(usuario));

  errorMsg.innerText = '';
  alert(`¡Registro exitoso como ${role.toUpperCase()}! Redirigiendo a tu panel...`);
  
  redirigirPorRol(role);
  return true;
}

function validarLogin(event) {
  event.preventDefault();
  const email = document.getElementById('login-email').value.trim();
  const password = document.getElementById('login-password').value;
  const errorMsg = document.getElementById('mensaje-error-login');

  if (!email || !password) {
    errorMsg.innerText = 'Por favor ingresa tu correo y contraseña.';
    return false;
  }

  let usuario = JSON.parse(localStorage.getItem('usuario_sesion'));
  if (!usuario) {
    usuario = { name: email.split('@')[0], email: email, role: 'student' };
    localStorage.setItem('usuario_sesion', JSON.stringify(usuario));
  }

  errorMsg.innerText = '';
  alert(`Bienvenido de nuevo, ${usuario.name}`);
  redirigirPorRol(usuario.role);
  return true;
}

function redirigirPorRol(role) {
  if (role === 'teacher') {
    window.location.href = 'instructor.html';
  } else if (role === 'admin') {
    window.location.href = 'admin.html';
  } else {
    window.location.href = 'estudiante.html';
  }
}

function cerrarSesion() {
  localStorage.removeItem('usuario_sesion');
  window.location.href = 'index.html';
}

document.addEventListener('DOMContentLoaded', () => {
  const navLinks = document.getElementById('nav-dynamic-links');
  if (!navLinks) return;

  const usuario = JSON.parse(localStorage.getItem('usuario_sesion'));

  if (usuario) {
    let panelUrl = 'estudiante.html';
    if (usuario.role === 'teacher') panelUrl = 'instructor.html';
    if (usuario.role === 'admin') panelUrl = 'admin.html';

    navLinks.innerHTML = `
      <span style="color: var(--primary); font-weight: bold;">Hola, ${usuario.name}</span>
      <a class="nav-link" href="${panelUrl}">Mi Panel</a>
      ${usuario.role === 'student' ? '<a class="nav-link" href="kardex.html">Kardex</a>' : ''}
      <button class="btn btn-outline btn-sm" onclick="cerrarSesion()">Salir</button>
    `;
  }
});