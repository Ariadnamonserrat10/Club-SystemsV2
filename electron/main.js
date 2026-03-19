import { app, BrowserWindow, Menu, dialog } from 'electron';
import path from 'path';
import { fileURLToPath } from 'url';
import { spawn, spawnSync } from 'child_process';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

// Path a PHP (ajusta si no está en PATH)
// Si php.exe no está en el PATH del sistema, puedes editar esta constante.
let PHP_COMMAND = 'php'; // comando por defecto

// rutas alternativas comunes (MySQL server bundle, xampp, wamp, etc.)
const PHP_FALLBACKS = [
  // rutas de instaladores comunes
  'C:\\xampp\\php\\php.exe',
  'C:\\Program Files\\MySQL\\MySQL Server 9.6\\bin\\php.exe',
  'C:\\php\\php.exe',
  // ruta adicional dentro del instalador: incluye un binario php.exe portátil en /php
  path.join(__dirname, '../php/php.exe')
];

// Nota: para que el instalador pueda funcionar fuera de cualquier instalación de PHP,
// descarga el paquete CLI de PHP (zip) desde https://windows.php.net/download/ y extrae
// el ejecutable dentro de la carpeta `php` en el directorio raíz del proyecto antes de
// volver a construir. El instalador copiará ese ejecutable y lo usará automáticamente.


// intenta resolver un ejecutable válido
const resolvePhp = () => {
  // si el comando actual funciona, dejarlo
  let result = spawnSync(PHP_COMMAND, ['-v']);
  if (result.status === 0) return;

  // prueba rutas alternativas
  for (const candidate of PHP_FALLBACKS) {
    result = spawnSync(candidate, ['-v']);
    if (result.status === 0) {
      PHP_COMMAND = candidate;
      console.log('Usando php desde:', candidate);
      return;
    }
  }

  console.warn('No se encontró ejecutable de PHP. Asegúrate de instalar PHP o ajustar PHP_COMMAND.');
};

resolvePhp();

function startPHPServer() {
  try {
    const backendPath = path.join(__dirname, '../Backend');
    const phpProcess = spawn(PHP_COMMAND, ['-S', '127.0.0.1:8000', '-t', backendPath], {
      cwd: backendPath,
      stdio: 'inherit',
      detached: true
    });

    phpProcess.unref(); // Para que no bloquee el cierre de Electron
    console.log('Servidor PHP iniciado en http://127.0.0.1:8000');
  } catch (err) {
    console.error('No se pudo iniciar el servidor PHP:', err);
    dialog.showErrorBox('PHP no encontrado',
      `No se pudo arrancar el servidor PHP. Comprueba que PHP esté instalado y accesible.\n` +
      `Ruta utilizada: ${PHP_COMMAND}\nError: ${err.message}`);
  }
}

function createWindow() {
  const win = new BrowserWindow({
    width: 1200,
    height: 800,
    icon: path.join(__dirname, '../src/Img/lo.ico'), // Agregar ícono a la ventana
    webPreferences: {
      preload: path.join(__dirname, 'preload.js'),
      nodeIntegration: true,
      contextIsolation: false
    },
  });

  if (process.env.NODE_ENV === 'development') {
    win.loadURL('http://localhost:5173'); // Dirección de Vite en desarrollo
  } else {
    win.loadURL(`file://${path.join(__dirname, '../dist/index.html')}`); // Archivo local en producción
  }
}

app.whenReady().then(() => {
  Menu.setApplicationMenu(null); // Quitar la barra de menú
  if (process.env.NODE_ENV !== 'development') {
    startPHPServer(); // Iniciar servidor PHP solo en producción
  }
  createWindow();
});

app.on('window-all-closed', () => {
  if (process.platform !== 'darwin') app.quit();
});
