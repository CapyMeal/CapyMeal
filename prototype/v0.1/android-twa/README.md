# CapyMeal, APK (Trusted Web Activity)

Envoltorio de [Bubblewrap](https://github.com/GoogleChromeLabs/bubblewrap) que empaqueta la PWA
de CapyMeal (ya hosteada en `https://capy-meal.vercel.app`) como un `.apk` instalable
directamente en Android, sin pasar por Play Store. No es una reescritura de la app: la Trusted
Web Activity abre el mismo sitio a pantalla completa, así que cualquier deploy normal del
frontend se ve reflejado ahí sin tocar este proyecto.

Solo hace falta regenerar el `.apk` cuando cambia algo del "shell": nombre, ícono, colores del
splash, o el dominio.

## Por qué hay scripts en vez de usar `bubblewrap init` a mano

`bubblewrap init` es un wizard interactivo (incluye un prompt tipo lista que no funciona bien
en shells no interactivas). Los scripts en `.scripts/` hacen lo mismo pero de forma
reproducible, usando las mismas clases que usa la CLI por dentro (`@bubblewrap/core`).

## Cómo regenerar todo desde cero

```bash
npm install

# 1. Instala (una sola vez) el JDK 17 y el Android SDK propios de Bubblewrap
#    en ~/.bubblewrap/. No toca el JDK del sistema. Es seguro correrlo de
#    nuevo, no hace nada si ya está instalado.
npm run setup

# 2. Genera twa-manifest.json (a partir del manifest real de la PWA) y el
#    proyecto Android. Si no existe ./android.keystore, genera uno nuevo y
#    lo deja anotado en KEYSTORE_CREDENTIALS.txt (gitignored). Ver el
#    aviso de la sección de abajo antes de correr esto por primera vez.
npm run generate

# 3. Compila y firma el .apk. Necesita las passwords del keystore como
#    env vars (las mismas que haya en KEYSTORE_CREDENTIALS.txt o donde se
#    hayan guardado después):
BUBBLEWRAP_KEYSTORE_PASSWORD=... BUBBLEWRAP_KEY_PASSWORD=... npm run build
```

Al terminar, copiar el resultado a donde Vercel lo sirve:

```bash
cp app-release-signed.apk ../frontend/public/capymeal.apk
```

`npm run generate` también escribe `../frontend/public/.well-known/assetlinks.json` con el
fingerprint de la clave de firma. **Eso sí se versiona**, es información pública (permite que
Android confíe en que el `.apk` es realmente CapyMeal).

## La clave de firma (`android.keystore`): no perderla

Determina si un futuro `.apk` cuenta como "la misma app" para Android. Sin ella:

- Hay que generar una clave nueva, eso da un fingerprint nuevo, y hay que reemplazar
  `assetlinks.json` en el frontend.
- Cualquiera que ya tenga el `.apk` instalado no puede actualizar "encima": tiene que
  desinstalar y volver a instalar (no pierde datos reales, esos viven en el backend vía cuenta y
  no en el teléfono, pero sí pierde la sesión local).

**Por eso `android.keystore` y `KEYSTORE_CREDENTIALS.txt` están gitignored a propósito.**
Después de generarlos, mover una copia del archivo `android.keystore` más las dos contraseñas
que aparecen en `KEYSTORE_CREDENTIALS.txt` a un lugar propio fuera de este repo (gestor de
contraseñas, Drive personal) y borrar `KEYSTORE_CREDENTIALS.txt` una vez guardado.

## Notas del entorno (Windows)

Estos dos scripts arreglan un par de particularidades del entorno donde se generó esto por
primera vez, documentadas en el código si hace falta tocarlas:

- `PATH`/`Path` duplicados como claves separadas en `process.env` rompían cómo
  `@bubblewrap/core` arma el PATH para invocar `keytool`/`gradle`/`sdkmanager`.
- `NoDefaultCurrentDirectoryInExePath` (fijado en este entorno) le impide a `cmd.exe` resolver
  `gradlew.bat` sin ruta explícita, que es como lo invoca `@bubblewrap/core`.

Si se regenera todo desde otra máquina Windows sin esas particularidades, los scripts deberían
seguir funcionando igual: los fixes son no-ops inofensivos si el problema no existe.

## Google Play Protect también avisa al instalar

Además del aviso de "orígenes desconocidos" que ya explica `/instalar-app`, la primera vez que
alguien instala este `.apk` es probable que Google Play Protect muestre un segundo aviso ("Se
bloqueó la app para proteger tu dispositivo") porque todavía no conoce a este desarrollador. No
es un bloqueo real: expandiendo "Más detalles" aparece la opción "Instalar de todos modos". Esto
es esperable para cualquier app fuera de Play Store y no depende de nada que podamos cambiar
desde el código, salvo que en algún momento se decida publicar en Play Store (descartado por
costo, ver la memoria del proyecto).
