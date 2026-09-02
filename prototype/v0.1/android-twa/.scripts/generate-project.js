// Arma twa-manifest.json a partir del manifest real de la PWA, genera el
// proyecto Android (Gradle) y crea la clave de firma, todo de forma
// programática, sin pasar por los prompts interactivos de `bubblewrap init`
// (uno de esos prompts es tipo "list", que no anda bien con stdin no
// interactivo). Usa las mismas clases que usa la CLI real por dentro.
const fs = require('fs');
const path = require('path');
const crypto = require('crypto');
const os = require('os');

// Este entorno expone tanto `PATH` como `Path` como claves separadas en
// process.env (duplicado heredado de la cadena de shells que lanza este
// script). JdkHelper.getEnv() de @bubblewrap/core asume la clave `Path` y
// solo la actualiza a ella: si el valor real vive en `PATH`, el `Path`
// que arma queda como "<bin del jdk>;undefined" y los subprocesos (keytool,
// sdkmanager, gradle) no encuentran nada. Unificamos a una sola clave antes
// de tocar nada de @bubblewrap/core.
const realPath = process.env.PATH || process.env.Path || '';
for (const key of Object.keys(process.env)) {
  if (key.toLowerCase() === 'path') delete process.env[key];
}
process.env.Path = realPath;

const {
  TwaManifest,
  TwaGenerator,
  ConsoleLog,
  JdkHelper,
  KeyTool,
  Config,
  DigitalAssetLinks,
} = require('@bubblewrap/core');

const TARGET_DIR = __dirname && path.resolve(__dirname, '..');
const MANIFEST_URL = 'https://capy-meal.vercel.app/manifest.webmanifest';
const PACKAGE_ID = 'com.capymeal.twa';
const FRONTEND_PUBLIC_DIR = path.resolve(TARGET_DIR, '../frontend/public');

function randomPassword(length = 32) {
  // Solo alfanumérico, evita headaches de escaping de shell (%, ", \) en
  // los comandos de keytool, que en Windows corren vía cmd.exe.
  const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
  return Array.from(crypto.randomFillSync(new Uint32Array(length)))
    .map((n) => chars[n % chars.length])
    .join('');
}

async function main() {
  console.log('Descargando manifest real de la PWA:', MANIFEST_URL);
  const twaManifest = await TwaManifest.fromWebManifest(MANIFEST_URL);

  twaManifest.packageId = PACKAGE_ID;
  twaManifest.signingKey.path = path.join(TARGET_DIR, 'android.keystore');
  twaManifest.generatorApp = 'bubblewrap-cli';

  await twaManifest.saveToFile(path.join(TARGET_DIR, 'twa-manifest.json'));
  console.log('twa-manifest.json guardado.');

  console.log('Generando proyecto Android...');
  const twaGenerator = new TwaGenerator();
  const log = new ConsoleLog('generate');
  await twaGenerator.createTwaProject(TARGET_DIR, twaManifest, log);

  // manifest-checksum.txt: si no existe, `bubblewrap build` para a preguntar
  // si aplicar cambios (prompt interactivo). Lo generamos ahora para que la
  // build de más adelante corra sin prompts.
  const manifestContents = await fs.promises.readFile(path.join(TARGET_DIR, 'twa-manifest.json'));
  const sum = crypto.createHash('sha1').update(manifestContents).digest('hex');
  await fs.promises.writeFile(path.join(TARGET_DIR, 'manifest-checksum.txt'), sum);
  console.log('Proyecto Android generado.');

  const keystorePath = twaManifest.signingKey.path;
  if (fs.existsSync(keystorePath)) {
    console.log('La clave de firma ya existe, no se genera una nueva:', keystorePath);
    return;
  }

  console.log('Generando clave de firma...');
  const config = await Config.loadConfig(path.join(os.homedir(), '.bubblewrap', 'config.json'));
  const jdkHelper = new JdkHelper(process, config);
  const keyTool = new KeyTool(jdkHelper);

  // Mismo password para ambas: un keystore PKCS12 (el formato default desde
  // JDK 9+, y el que usa keytool acá) solo soporta una password real para
  // todo el store: si se le pide una "-keypass" distinta a "-storepass",
  // keytool la genera igual sin quejarse pero la ignora en silencio, y
  // cualquier herramienta que después intente abrir la clave privada con
  // esa segunda password (como apksigner) falla con "Wrong password?".
  // Confirmado contra este keystore real antes de este fix.
  const keystorePassword = randomPassword();
  const keyPassword = keystorePassword;

  await keyTool.createSigningKey({
    fullName: 'CapyMeal',
    organizationalUnit: 'CapyMeal',
    organization: 'CapyMeal',
    country: 'AR',
    password: keystorePassword,
    keypassword: keyPassword,
    alias: twaManifest.signingKey.alias,
    path: keystorePath,
  });
  console.log('Clave de firma creada en', keystorePath);

  const credentialsPath = path.join(TARGET_DIR, 'KEYSTORE_CREDENTIALS.txt');
  await fs.promises.writeFile(
    credentialsPath,
    'IMPORTANTE: mover estas credenciales a un lugar seguro (gestor de contraseñas) y\n' +
      'despues borrar este archivo. Sin esto no se puede generar un .apk actualizado\n' +
      'que Android reconozca como "la misma app".\n\n' +
      `Keystore: ${keystorePath}\n` +
      `Alias: ${twaManifest.signingKey.alias}\n` +
      `Password del keystore: ${keystorePassword}\n` +
      `Password de la key: ${keyPassword}\n`,
  );
  console.log('Credenciales guardadas (temporalmente) en', credentialsPath);

  const keyInfo = await keyTool.keyInfo({
    path: keystorePath,
    alias: twaManifest.signingKey.alias,
    password: keystorePassword,
    keypassword: keyPassword,
  });
  const sha256 = keyInfo.fingerprints.get('SHA256');
  if (!sha256) {
    throw new Error('No se pudo extraer el fingerprint SHA256 de la clave recien creada.');
  }
  console.log('Fingerprint SHA256:', sha256);

  const assetLinks = DigitalAssetLinks.generateAssetLinks(PACKAGE_ID, sha256);
  const wellKnownDir = path.join(FRONTEND_PUBLIC_DIR, '.well-known');
  fs.mkdirSync(wellKnownDir, { recursive: true });
  fs.writeFileSync(path.join(wellKnownDir, 'assetlinks.json'), assetLinks);
  console.log('assetlinks.json escrito en', path.join(wellKnownDir, 'assetlinks.json'));
}

main().catch((err) => {
  console.error('FALLO generate-project:', err);
  process.exit(1);
});
