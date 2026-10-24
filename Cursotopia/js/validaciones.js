function validarRegistro(event) {
  event.preventDefault();
  
  const nombre = document.getElementById('reg-name').value.trim();
  const email = document.getElementById('reg-email').value.trim();
  const password = document.getElementById('reg-password').value;
  const role = document.getElementById('reg-role').value;
  const errorMsg = document.getElementById('mensaje-error');

  if (!nombre || !email || !password) {
    if (errorMsg) {
      errorMsg.style.color = "red";
      errorMsg.innerText = 'Todos los campos son obligatorios.';
    }
    return false;
  }

  const regexPassword = /^(?=.*[A-Z])(?=.*\d)(?=.*[$("@"#$%\&\&=?;:;,.+\-*\{\]B)]).{8,}$/;

  if (!regexPassword.test(password)) {
    if (errorMsg) {
      errorMsg.style.color = "red";
      errorMsg.innerText = 'La contraseña debe incluir al menos 8 caracteres, una mayúscula, un número y un carácter especial ($#%...).';
    }
    return false;
  }

  if (errorMsg) {
    errorMsg.style.color = "orange";
    errorMsg.innerText = 'Registrando en el servidor...';
  }

  fetch('http://localhost/Cursotopia/api.php?action=usuarios', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      nombre: nombre,
      email: email,
      password: password,
      rol: role
    })
  })
  .then(response => {
    if (!response.ok) {
      return response.json().then(err => { throw new Error(err.error || 'Error en el servidor al registrar.'); });
    }
    return response.json();
  })
  .then(data => {
    console.log("Respuesta de la API:", data);
    
    const usuario = { name: nombre, email: email, role: role };
    localStorage.setItem('usuario_sesion', JSON.stringify(usuario));

    if (errorMsg) {
      errorMsg.style.color = "green";
      errorMsg.innerText = '¡Registro exitoso en la Base de Datos!';
    }
    
    setTimeout(() => {
      redirigirPorRol(role);
    }, 1200);
  })
  .catch(error => {
    console.error("Error:", error);
    if (errorMsg) {
      errorMsg.style.color = "red";
      errorMsg.innerText = error.message || 'No se pudo conectar con el servidor PHP.';
    }
  });

  return false;
}

function validarLogin(event) {
  event.preventDefault();
  const email = document.getElementById('login-email').value.trim();
  const password = document.getElementById('login-password').value;
  const errorMsg = document.getElementById('mensaje-error-login');

  if (!email || !password) {
    if (errorMsg) {
      errorMsg.style.color = "red";
      errorMsg.innerText = 'Por favor ingresa tu correo y contraseña.';
    } else {
      alert('Por favor ingresa tu correo y contraseña.');
    }
    return false;
  }

  if (errorMsg) {
    errorMsg.style.color = "orange";
    errorMsg.innerText = 'Verificando credenciales en el servidor...';
  }

  fetch('http://localhost/Cursotopia/api.php?action=login', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      email: email,
      password: password
    })
  })
  .then(response => {
    if (!response.ok) {
      return response.json().then(err => { throw new Error(err.error || 'Correo o contraseña incorrectos.'); });
    }
    return response.json();
  })
  .then(data => {
    console.log("Login exitoso:", data);
    
    localStorage.setItem('usuario_sesion', JSON.stringify(data.usuario));
    
    if (errorMsg) {
      errorMsg.style.color = "green";
      errorMsg.innerText = '¡Inicio de sesión exitoso!';
    }

    setTimeout(() => {
      redirigirPorRol(data.usuario.role);
    }, 1000);
  })
  .catch(error => {
    console.error("Error:", error);
    if (errorMsg) {
      errorMsg.style.color = "red";
      errorMsg.innerText = error.message || 'No se pudo conectar con el servidor PHP.';
    } else {
      alert(error.message || 'Error al iniciar sesión.');
    }
  });

  return false;
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
  window.location.href = '../index.html';
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