// Instala (si hace falta) el JDK 17 y el Android SDK propios de Bubblewrap, sin
// pasar por los prompts interactivos de `bubblewrap init` (que además de ser
// frágiles de scriptear a ciegas, usan un prompt tipo "list" para otros pasos
// que no funciona bien con stdin no-interactivo). Deja ~/.bubblewrap/config.json
// igual que lo dejaría la CLI real. Corridas futuras de `bubblewrap build`
// lo detectan solo.
const path = require('path');
const os = require('os');
const fs = require('fs');
const bubblewrapCliPkg = path.dirname(require.resolve('@bubblewrap/cli/package.json'));
const { Config } = require('@bubblewrap/core');
const { JdkInstaller } = require(path.join(bubblewrapCliPkg, 'dist/lib/JdkInstaller'));
const { AndroidSdkToolsInstaller } = require(path.join(bubblewrapCliPkg, 'dist/lib/AndroidSdkToolsInstaller'));

const CONFIG_FOLDER = path.join(os.homedir(), '.bubblewrap');
const CONFIG_PATH = path.join(CONFIG_FOLDER, 'config.json');
const JDK_FOLDER = path.join(CONFIG_FOLDER, 'jdk');
const SDK_FOLDER = path.join(CONFIG_FOLDER, 'android_sdk');

// Prompt "mudo": solo imprime mensajes y baja archivos, sin pedir nada por stdin.
const silentPrompt = {
  printMessage: (msg) => console.log(msg),
  downloadFile: async (url, filename, totalSize = 0) => {
    console.log(`Descargando ${url} -> ${filename}`);
    const { fetchUtils } = require('@bubblewrap/core');
    await fetchUtils.downloadFile(url, filename, (current, total) => {
      if (total > 0 && Date.now() % 2000 < 50) {
        console.log(`  ${Math.round((current / total) * 100)}%`);
      }
    });
  },
};

async function main() {
  let config = await Config.loadConfig(CONFIG_PATH);
  if (!config) {
    config = new Config('', '');
  }

  if (!config.jdkPath || !fs.existsSync(config.jdkPath)) {
    console.log('Instalando JDK 17 (propio de Bubblewrap, no toca el JDK del sistema)...');
    fs.mkdirSync(JDK_FOLDER, { recursive: true });
    const jdkInstaller = new JdkInstaller(process, silentPrompt);
    config.jdkPath = await jdkInstaller.install(JDK_FOLDER);
    await config.saveConfig(CONFIG_PATH);
    console.log('JDK listo en', config.jdkPath);
  } else {
    console.log('JDK ya configurado en', config.jdkPath);
  }

  if (!config.androidSdkPath || !fs.existsSync(config.androidSdkPath)) {
    console.log('Instalando Android SDK command-line tools...');
    fs.mkdirSync(SDK_FOLDER, { recursive: true });
    const sdkInstaller = new AndroidSdkToolsInstaller(process, silentPrompt);
    await sdkInstaller.install(SDK_FOLDER);
    config.androidSdkPath = SDK_FOLDER;
    await config.saveConfig(CONFIG_PATH);
    console.log('Android SDK listo en', config.androidSdkPath);
  } else {
    console.log('Android SDK ya configurado en', config.androidSdkPath);
  }

  console.log('OK: config guardada en', CONFIG_PATH);
}

main().catch((err) => {
  console.error('FALLO setup-config:', err);
  process.exit(1);
});
