---
name: CapyMeal
description: Tu diario de comidas con la calma de un carpincho.
colors:
  terracota: "#96684A"
  terracota-deep: "#7D5339"
  mostaza: "#C9A66B"
  capy-brown: "#A9805F"
  arena: "#F2EAE0"
  mantel: "#FBF7F1"
  tinta: "#362B22"
  tinta-suave: "#6E6156"
  borde-lino: "#DED2C0"
  salvia: "#A3C79A"
  durazno: "#F0C888"
  rosa-arcilla: "#E3968C"
  ladrillo: "#B5453C"
  noche-fondo: "#22190F"
  noche-mantel: "#2C2116"
  noche-tinta: "#F1E4D3"
  noche-titulo: "#F8EFE2"
  noche-tinta-suave: "#B7A68D"
  noche-miel: "#D9A45C"
  noche-miel-clara: "#E6BC80"
  noche-borde: "#3F3121"
typography:
  display:
    fontFamily: "Fredoka, Nunito, sans-serif"
    fontSize: "2.2rem"
    fontWeight: 700
    lineHeight: 1.2
  headline:
    fontFamily: "Fredoka, Nunito, sans-serif"
    fontSize: "1.8rem"
    fontWeight: 700
    lineHeight: 1.2
  title:
    fontFamily: "Fredoka, Nunito, sans-serif"
    fontSize: "1.4rem"
    fontWeight: 700
    lineHeight: 1.2
  title-sm:
    fontFamily: "Fredoka, Nunito, sans-serif"
    fontSize: "1.1rem"
    fontWeight: 600
    lineHeight: 1.3
  body:
    fontFamily: "Inter, Segoe UI, sans-serif"
    fontSize: "0.95rem"
    fontWeight: 400
    lineHeight: 1.5
  label:
    fontFamily: "Inter, Segoe UI, sans-serif"
    fontSize: "0.85rem"
    fontWeight: 600
    lineHeight: 1.4
  caption:
    fontFamily: "Inter, Segoe UI, sans-serif"
    fontSize: "0.75rem"
    fontWeight: 600
    lineHeight: 1.3
rounded:
  control: "8px"
  sm: "12px"
  md: "20px"
  lg: "32px"
  pill: "999px"
spacing:
  xs: "0.5rem"
  sm: "0.75rem"
  md: "1rem"
  lg: "1.5rem"
  xl: "2rem"
  2xl: "2.75rem"
components:
  button-primary:
    backgroundColor: "{colors.terracota}"
    textColor: "{colors.mantel}"
    typography: "{typography.label}"
    rounded: "{rounded.control}"
    height: "52px"
  button-primary-hover:
    backgroundColor: "{colors.terracota-deep}"
    textColor: "{colors.mantel}"
  button-ghost:
    backgroundColor: "transparent"
    textColor: "{colors.tinta}"
    typography: "{typography.label}"
    rounded: "{rounded.control}"
    height: "52px"
  card-meal:
    backgroundColor: "{colors.mantel}"
    textColor: "{colors.tinta}"
    rounded: "{rounded.control}"
    padding: "1.5rem"
  input-meal:
    backgroundColor: "{colors.arena}"
    textColor: "{colors.tinta}"
    typography: "{typography.body}"
    rounded: "{rounded.control}"
    padding: "0.75rem 1rem"
  input-meal-focus:
    backgroundColor: "{colors.mantel}"
  chip-quick-date:
    backgroundColor: "{colors.arena}"
    textColor: "{colors.tinta}"
    rounded: "{rounded.pill}"
    padding: "0.3rem 0.8rem"
  nav-bottom:
    backgroundColor: "{colors.mantel}"
    textColor: "{colors.tinta}"
    height: "70px"
  nav-bottom-active:
    textColor: "{colors.terracota-deep}"
---

# Design System: CapyMeal

## Overview

**Creative North Star: "La sobremesa"**

CapyMeal se siente como el rato tranquilo después de comer: la mesa todavía puesta, el mate dando vueltas, nadie apurado. Es un lugar cercano y relajado, y Capi está ahí como compañía, no como adorno. Todo lo visual busca esa temperatura: colores de tierra y madera, papel cálido en vez de blanco, sombras de luz de tarde en vez de grises.

La densidad es baja a propósito. Una pantalla muestra pocas cosas, con aire alrededor, en una sola columna angosta (600px como máximo) que se lee igual en el celular y en la computadora. La personalidad vive en las ilustraciones de Capi y de las comidas, en la tipografía redondeada de los títulos y en la voz de los mensajes; la estructura, en cambio, es sobria y predecible.

Calidez no es descuido. Lo que está registrado acá como sistema es lo que ya existe en el código; donde el código se desvió (tamaños sueltos, colores escritos a mano, tres estilos de íconos mezclados), la regla manda y el código se corrige.

**Key Characteristics:**
- Paleta "Tierra": terracota, mostaza y madera sobre fondo arena, con tema oscuro de miel sobre marrón noche.
- Dos tipografías con roles fijos: Fredoka redondeada para títulos, Inter para todo lo demás.
- Superficies suaves y apoyadas, con sombras cálidas y difusas.
- Ilustraciones de Capi y de comidas como única voz decorativa.
- Una columna de 600px, barra de navegación inferior fija, mucho aire.

## Colors

Una paleta de tierra, madera y papel: cálida, de saturación baja, con un solo color fuerte para la acción.

### Primary
- **Terracota** (`terracota`): el botón principal ("Cerrar el día"), la pestaña activa de la barra inferior, los links, la fecha de cada tarjeta del diario y el borde de una tarjeta recién guardada. Es la voz de "esto es lo importante acá".
- **Terracota tostada** (`terracota-deep`): solo el hover del botón principal.

### Secondary
- **Mostaza** (`mostaza`): acentos puntuales y el tinte del degradé suave arriba de cada pantalla. En el código se llama `--color-lavender` por herencia de una paleta anterior; el color real es mostaza.

### Tertiary
- **Madera de Capi** (`capy-brown`): detalles cálidos cercanos a las ilustraciones. Uso escaso.

### Neutral
- **Arena** (`arena`): el fondo general de la app y el fondo de los campos de texto en reposo.
- **Mantel** (`mantel`): tarjetas, encabezado, barra inferior y campos con foco. Es el "papel" que se apoya sobre la arena.
- **Tinta** (`tinta`): texto principal y títulos.
- **Tinta suave** (`tinta-suave`): fechas, subtítulos, placeholders y texto secundario.
- **Borde de lino** (`borde-lino`): bordes de tarjetas, divisores y chips.

### Estados
- **Salvia** (`salvia`), **Durazno** (`durazno`) y **Rosa arcilla** (`rosa-arcilla`): éxito, aviso y error. Son colores de **fondo**: en los avisos tonales tiñen el fondo y el ícono, y el texto va en tinta. Nunca como color de texto.
- **Ladrillo** (`ladrillo`, token `--color-danger-text`): el rojo para **texto** de errores y acciones destructivas (cerrar sesión, eliminar cuenta). En tema oscuro pasa a rosa arcilla.
- **Terracota de texto** (`--color-primary-text`, alias de terracota tostada; en oscuro, miel clara): links, fechas y etiquetas chicas en terracota. La terracota base solo da 4.03:1 sobre arena.

### Tema oscuro
El tema oscuro reemplaza la terracota por **Miel** (`noche-miel`, hover `noche-miel-clara`) para mantener contraste, y usa **Marrón noche** (`noche-fondo`), **Mantel de noche** (`noche-mantel`), **Tinta clara** (`noche-tinta`, títulos `noche-titulo`), **Tinta clara suave** (`noche-tinta-suave`) y **Borde de noche** (`noche-borde`). Mostaza, madera y los estados no cambian.

### Named Rules
**La regla de la mesa servida.** La terracota marca una sola cosa por pantalla como la más importante: la acción principal o la ubicación actual. Si dos elementos compiten en terracota, uno de los dos sobra.

**La regla de la doble fuente.** Cada color de marca existe en dos lugares que deben coincidir: `styles/main.css` y `plugins/vuetify.js`. Un color nuevo o cambiado se toca en los dos, o no se toca.

**La regla de la paleta cerrada.** Ningún componente escribe un hex o un `rgba()` propio. Si hace falta un tono nuevo, se agrega como token o se deriva con `color-mix()` desde un token existente.

## Typography

**Display Font:** Fredoka (con Nunito y sans-serif de respaldo)
**Body Font:** Inter (con Segoe UI y sans-serif de respaldo)

**Character:** Fredoka pone la ternura redondeada de Capi en los títulos; Inter pone orden y lectura cómoda en todo lo demás. Juntas dicen "cercano pero prolijo".

### Hierarchy
- **Display** (700, 2.2rem, 1.2): momentos de bienvenida, como la pantalla de inicio.
- **Headline** (700, 1.8rem, 1.2): título de las tarjetas de autenticación y de cada sección principal ("Mi Diario", "Ajustes").
- **Title** (700, 1.4rem, 1.2): el saludo de "Hoy" y la confirmación de día guardado.
- **Title chico** (600, 1.1rem, 1.3): nombre de cada comida en su tarjeta.
- **Body** (400, 0.95rem, 1.5): texto de las comidas, párrafos, campos. Los textos largos (políticas, guía de instalación) no pasan de 65ch por línea.
- **Label** (600, 0.85rem): botones, fechas, chips.
- **Caption** (600, 0.75rem): etiquetas de la barra inferior e insignias.

### Named Rules
**La regla de los dos cubiertos.** Fredoka solo para títulos, saludos y la marca. Botones, etiquetas, campos y párrafos van siempre en Inter.

**La regla de la escala.** Todo tamaño de letra sale de los tokens `--font-size-*`. Hoy hay más de sesenta tamaños escritos a mano en los componentes (0.72, 0.8, 0.88, 0.9rem…): cada vez que se toca un componente, sus tamaños sueltos se llevan al token más cercano.

## Layout

Una sola columna centrada de 600px como máximo, con margen lateral de 1rem en el celular y 1.5rem desde 768px. El espaciado sigue la escala `xs` a `2xl` (0.5 a 2.75rem), que crece apenas en pantallas de 768px o más (`lg` 1.75rem, `xl` 2.25rem, `2xl` 3rem).

Estructura fija de las pantallas con sesión: encabezado pegajoso de 68px con Capi chef y la marca centrados; contenido; barra de navegación inferior fija de 70px. Las pantallas con un formulario largo suman una barra de acción pegajosa justo encima de la navegación, con el botón principal siempre a mano. El contenido reserva espacio abajo para que nada quede tapado por esas barras.

Las pantallas de autenticación centran una tarjeta de 400px como máximo sobre el fondo.

La separación entre tarjetas de una lista es `md` (1rem); entre bloques de una pantalla, `xl` (2rem).

## Elevation & Depth

Superficies suaves y apoyadas: las tarjetas son hojas de papel sobre la mesa. La profundidad combina tres capas tonales (arena de fondo, mantel en tarjetas, arena otra vez dentro de los campos) con sombras cálidas y difusas. Las barras fijas (encabezado, navegación, acción) usan mantel semitransparente con desenfoque de 8px.

### Shadow Vocabulary
- **Apoyo** (`box-shadow: 0 2px 12px rgba(105,73,49,.12)`): tarjetas en reposo, encabezado, barra inferior, botón principal.
- **Elevado** (`box-shadow: 0 6px 24px rgba(105,73,49,.17)`): hover de tarjetas del diario y del botón principal, tarjetas de autenticación.
- **Recién guardado** (`box-shadow: 0 0 0 3px rgba(150,104,74,.22)`): anillo terracota en la tarjeta de una comida durante 2.5 segundos después de guardarse.
- **Foco** (`box-shadow: 0 0 0 3px rgba(150,104,74,.4)`): anillo de foco por teclado en botones, links y campos.

### Named Rules
**La regla de la luz de tarde.** Toda sombra es marrón cálido (base `105,73,49`), nunca gris ni negra. Las elevaciones de Material de Vuetify se reemplazan por estas dos en `vuetify-overrides.css`.

**La regla del levante.** Lo que se puede tocar sube 2px al pasar el mouse y vuelve a su lugar al apretar. Con movimiento reducido, no sube.

## Shapes

Esquinas redondeadas en todo, sin ángulos rectos. Radio de control (`control` 8px, token `--radius-control`, igual al `rounded="lg"` de Vuetify) para botones, tarjetas, campos y avisos; tres escalas mayores (`sm` 12px, `md` 20px, `lg` 32px) para paneles y contenedores grandes; y la píldora (999px) para chips, insignias y el indicador de la navegación.

Los bordes son finos y del color lino: 1px en tarjetas del diario y barras, 1.5px en tarjetas de comida, chips y paneles.

### Named Rules
**La regla del anidado.** Un elemento adentro de otro nunca tiene un radio mayor que su contenedor. Un campo dentro de una tarjeta usa `--radius-control`, nunca `--radius-sm`.

## Components

### Buttons
Grandes, cómodos y tranquilos: nunca agresivos.
- **Shape:** esquinas suaves (8px), ancho completo y tamaño grande por defecto; versión compacta para acciones secundarias dentro de un aviso.
- **Primary:** relleno terracota con texto mantel, Inter 600 sin mayúsculas forzadas, sombra de apoyo.
- **Hover / Focus:** sube 2px y pasa a sombra elevada; el foco por teclado muestra el anillo terracota.
- **Ghost:** contorno lino con texto tinta; al pasar el mouse el borde pasa a terracota y el fondo toma un 8% de terracota.
- **Danger:** misma forma que el principal, en rosa arcilla, solo para eliminar.

### Chips
- **Style:** píldora con fondo arena, borde lino de 1.5px y texto tinta a 0.8rem. Se usan para las fechas rápidas ("Hoy", "Ayer", "Hace 7 días").
- **State:** al pasar el mouse, borde terracota y fondo con 12% de terracota.

### Cards / Containers
- **Corner Style:** 8px (ver la regla del anidado).
- **Background:** mantel.
- **Shadow Strategy:** apoyo en reposo; las tarjetas del diario, que son links, pasan a elevado y suben 2px.
- **Border:** lino, 1px a 1.5px.
- **Internal Padding:** `lg` (1.5rem); las de autenticación, `2xl` por `xl`.

### Inputs / Fields
- **Campo de comida:** área de texto de 3 líneas sin borde visible, fondo arena, esquinas de 8px, placeholder en tinta suave sin opacidad extra, sin redimensionar. Con foco, el fondo pasa a mantel y aparece un borde terracota de 1.5px. Se guarda sola al salir del campo.
- **Campos de formulario (Vuetify):** variante rellena, 8px, con 20px de margen interno a los lados; el foco se marca con la línea inferior terracota, sin anillo extra.
- **Error / Disabled:** los errores se muestran en un aviso tonal rosa arcilla arriba del contenido, no en rojo sobre el campo.

### Navigation
- **Barra inferior:** fija, 70px, fondo mantel semitransparente con desenfoque a todo el ancho; los cuatro destinos (Hoy, Diario, PDF, Ajustes) quedan alineados con la columna de 600px. Ícono de línea de 24px y etiqueta `caption`. Inactivos en tinta suave; el activo en terracota de texto con una píldora de 56×30px detrás del ícono (terracota al 16%), para que no dependa solo del color.
- **Encabezado:** pegajoso, 68px, mantel con borde y sombra de apoyo, Capi chef de 40px y "CapyMeal" en Fredoka 700.

### Iconografía e ilustraciones
Capi y las comidas son la firma visual del producto, y la única voz decorativa.
- **Comidas:** cada campo del día tiene una sola ilustración (desayuno, almuerzo, merienda, cena y el diario para "Recuerdo del día"), definida una sola vez en `utils/meals.js` y dibujada con el componente `MealIcon`. Se usa en "Hoy", el detalle, el diario y la vista previa del PDF.
- **Deuda de assets:** las cuatro ilustraciones de comida no comparten tratamiento de fondo (desayuno trae un cuadrado amarillo, almuerzo un círculo con sol, merienda y cena van sin fondo). Hace falta reexportarlas con el mismo criterio.
- **Capi:** aparece en momentos con significado (bienvenida, carga, estado vacío, día guardado, autenticación), siempre con la sombra cálida suave. Acompaña, no decora.
- **Controles:** íconos de línea de Material Design (`@mdi/js`) para navegación y acciones.
- **Emoji:** solo dentro de frases de tono de Capi ("Listo 🍂", "Despertando a Capi… 🦫"), nunca como ícono de una comida, de un botón o de un control.

### Named Rules
**La regla de una sola voz.** Cada cosa se dibuja de una sola manera en toda la app. Una comida es siempre su ilustración; un control es siempre un ícono de línea; un emoji es siempre parte de una frase.

## Do's and Don'ts

### Do:
- **Do** usar las ilustraciones de comida también en las tarjetas del diario y en el detalle, en miniatura (24 a 32px).
- **Do** usar `var(--color-*)`, `var(--space-*)`, `var(--radius-*)` y `var(--font-size-*)` en todo componente nuevo o tocado.
- **Do** mantener las sombras en la base marrón `105,73,49` con los dos niveles definidos (apoyo y elevado).
- **Do** dejar que la terracota marque una sola cosa por pantalla.
- **Do** respetar `prefers-reduced-motion` en todo movimiento, como ya hacen el botón y el loader.
- **Do** mostrar errores y avisos como avisos tonales de Vuetify, con texto alineado al inicio.

### Don't:
- **Don't** usar emoji como ícono: ni en botones ("🤎 Guardar"), ni como ícono de una comida o de una fila de Ajustes, ni en títulos que nombran una función ("Nueva contraseña 🔒").
- **Don't** usar salvia, durazno o rosa arcilla como color de texto.
- **Don't** usar `text-transform: capitalize` en fechas: en español deja "15 De Noviembre". Mayúscula solo en la primera letra (`::first-letter`).
- **Don't** escribir hex, `rgba()` ni tamaños de letra a mano en un componente.
- **Don't** usar sombras grises o negras, ni las elevaciones por defecto de Material.
- **Don't** poner un radio interno mayor que el del contenedor.
- **Don't** usar Fredoka en botones, etiquetas o párrafos.
- **Don't** agregar un color de marca en `main.css` sin replicarlo en `plugins/vuetify.js`.
- **Don't** usar guion largo ni doble guion en los textos de la interfaz.
