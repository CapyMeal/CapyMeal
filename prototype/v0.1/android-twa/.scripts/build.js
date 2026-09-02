// Corre `bubblewrap build` en proceso (misma lógica que la CLI real), sin
// pasar por prompts interactivos: las passwords del keystore se toman de
// BUBBLEWRAP_KEYSTORE_PASSWORD/BUBBLEWRAP_KEY_PASSWORD (soportado nativamente
// por bubblewrap), y manifest-checksum.txt ya coincide con twa-manifest.json
// (lo generó generate-project.js), así que tampoco dispara el prompt de
// "actualizar proyecto".
const path = require('path');
const os = require('os');

// Mismo fix de PATH duplicado que generate-project.js, necesario acá
// también porque build.js invoca gradle/sdkmanager/apksigner via el mismo
// JdkHelper.getEnv().
const realPath = process.env.PATH || process.env.Path || '';
for (const key of Object.keys(process.env)) {
  if (key.toLowerCase() === 'path') delete process.env[key];
}
process.env.Path = realPath;

// Este entorno además fija NoDefaultCurrentDirectoryInExePath, que le dice a
// cmd.exe que NO busque en el directorio actual comandos sin ruta explícita.
// GradleWrapper de @bubblewrap/core invoca `gradlew.bat` a secas (sin ".\"),
// así que con esa variable puesta cmd.exe nunca lo encuentra aunque exista
// en el cwd correcto. La borramos para este subproceso puntual.
delete process.env.NoDefaultCurrentDirectoryInExePath;

const bubblewrapCliPkg = path.dirname(require.resolve('@bubblewrap/cli/package.json'));
const { build } = require(path.join(bubblewrapCliPkg, 'dist/lib/cmds/build'));
const { Config, TwaManifest, JdkHelper, AndroidSdkTools, ConsoleLog } = require('@bubblewrap/core');

const TARGET_DIR = path.resolve(__dirname, '..');

const silentPrompt = {
  printMessage: (msg) => console.log(msg),
  promptConfirm: async (msg, def) => {
    console.log(msg, '->', def);
    return def;
  },
};

async function main() {
  const keystorePassword = process.env.BUBBLEWRAP_KEYSTORE_PASSWORD;
  const keyPassword = process.env.BUBBLEWRAP_KEY_PASSWORD;
  if (!keystorePassword || !keyPassword) {
    throw new Error('Faltan BUBBLEWRAP_KEYSTORE_PASSWORD / BUBBLEWRAP_KEY_PASSWORD en el entorno.');
  }
  const config = await Config.loadConfig(path.join(os.homedir(), '.bubblewrap', 'config.json'));

  // skipSigning: true. Build.signApk()/signAppBundle() de @bubblewrap/cli
  // envuelven las passwords entre comillas literales antes de pasarlas
  // (pensado para el codepath que arma un string de shell), pero en Windows
  // apksigner se invoca vía `runJava` con args en array (no shell): esas
  // comillas quedan como caracteres literales de la password real y el
  // firmado falla con "Wrong password?". Frenamos el firmado automático acá
  // y lo hacemos nosotros mismos más abajo, sin ese bug. Tampoco firmamos el
  // .aab (App Bundle): es para Play Store, que no usamos, solo nos
  // interesa el .apk para descarga directa.
  const args = { directory: TARGET_DIR, skipSigning: true };
  const ok = await build(config, args, undefined, silentPrompt);
  if (!ok) {
    throw new Error('bubblewrap build devolvió false.');
  }

  console.log('Firmando el .apk (workaround del bug de comillas en Windows)...');
  const manifestFile = path.join(TARGET_DIR, 'twa-manifest.json');
  const twaManifest = await TwaManifest.fromFile(manifestFile);
  const jdkHelper = new JdkHelper(process, config);
  const androidSdkTools = await AndroidSdkTools.create(process, config, jdkHelper, new ConsoleLog('sign'));
  await androidSdkTools.apksigner(
    twaManifest.signingKey.path,
    keystorePassword,
    twaManifest.signingKey.alias,
    keyPassword,
    path.join(TARGET_DIR, 'app-release-unsigned-aligned.apk'),
    path.join(TARGET_DIR, 'app-release-signed.apk'),
  );
  console.log('APK firmado en', path.join(TARGET_DIR, 'app-release-signed.apk'));
}

main().catch((err) => {
  console.error('FALLO build:', err);
  process.exit(1);
});
