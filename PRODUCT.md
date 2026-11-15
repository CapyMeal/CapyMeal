# Product

<!-- impeccable:product-schema 1 -->

## Platform

web

PWA instalable, más un `.apk` para Android generado con Bubblewrap (Trusted Web Activity) que abre la misma web. El lenguaje de diseño es web, no nativo de Android. Se usa tanto en el celular como en la computadora.

## Users

- **La creadora** (Mercedes), que la usa para su propio diario de comidas.
- **Amigos y conocidos** que la instalaron en sus Android o la usan desde el navegador.
- **Público general**: cualquier persona que quiera llevar un diario de comidas sin contar calorías.

Situación típica: alguien registra lo que comió en el día (desayuno, almuerzo, merienda, cena y un "recuerdo del día"), muchas veces poniéndose al día con días atrasados, y más adelante relee el diario o lo exporta.

Audiencia secundaria: CapyMeal también es pieza de portfolio de su creadora. Reclutadores y entrevistadores la abren para evaluar criterio de producto, diseño y calidad técnica.

## Product Purpose

Un diario de comidas simple y cálido. Su objetivo es ayudar a construir el hábito de registrar las comidas y conservar un historial personal. No controla calorías ni impone objetivos nutricionales.

El éxito es que la persona vuelva cada día a registrar sin sentir culpa ni esfuerzo, y que cuando lo necesite tenga un historial claro para llevar a una consulta.

## Positioning

"Las comidas pasan. Los recuerdos quedan." CapyMeal trata cada comida como parte de una historia, no como un dato a optimizar. La acompaña Capi, un carpincho que no controla, no exige y no corrige: acompaña. Frente a las apps de nutrición, no hay números, metas ni juicios; frente a anotar en el celular, hay estructura por comida, historial filtrable y un PDF listo para mostrar.

## Operating Context

- **Registro diario** en la pantalla "Hoy": cinco campos de texto libre (cuatro comidas y un recuerdo), con guardado automático por campo y un botón para guardar el día. Permite cambiar de fecha y avisa cuando hay días salteados.
- **Diario** ("Mi Diario" / recuerdos): historial con filtro por rango de fechas y detalle por día, editable.
- **Exportar a PDF**: con portada de Capi y rango de fechas elegido. En la práctica se usa para **llevarlo a una consulta** con una profesional de salud (doctora, nutricionista).
- **Cuenta**: email y contraseña, Google o Microsoft, verificación de email, 2FA opcional, eliminar cuenta, tema claro u oscuro, idioma, avatar (Gravatar o una ilustración de Capi).
- Bilingüe español rioplatense / inglés, con detección automática.

## Capabilities and Constraints

- Frontend Vue 3 + Vite + Vuetify 4; backend Laravel; PostgreSQL. Deploy en Vercel (frontend), Render (backend) y Neon (base), todo en planes gratuitos.
- **CapyMeal es y va a seguir siendo gratuita.** No hay presupuesto para Play Store ni dominio propio.
- Lectura offline del diario ya guardado; las escrituras necesitan conexión.
- Límite de 2000 caracteres por campo de texto.
- Terminología fija: Desayuno, Almuerzo, Merienda, Cena, "Recuerdo del día", "Mi Diario", "recuerdos".
- **Sin decidir:** importar glucemias desde mySugr (CSV) para mostrarlas junto a cada comida, en pausa hasta una consulta médica. Fotos en el recuerdo del día, recordatorio diario y onboarding real están en el roadmap, sin fecha.

## Brand Commitments

- Nombre: CapyMeal. Mascota: **Capi**, un carpincho curioso y tranquilo.
- Manifiesto ([CapyMeal/docs/manifesto.md](CapyMeal/docs/manifesto.md)): no es para contar calorías, no juzga hábitos, es un espacio para recordar.
- Voz: cercana, en "vos", cálida y breve ("Despertando a Capi…", "Este día ya forma parte de tu diario."). Los errores hablan en primera persona y sin tecnicismos.
- En textos de la app no se usa guion largo ni doble guion.

## Evidence on Hand

- Ilustraciones de Capi y de las comidas: `prototype/v0.1/frontend/src/assets/` y `CapyMeal/design/` (íconos, ilustraciones, logos).
- Capturas de onboarding, login y ajustes: `CapyMeal/design/mockups/`.
- Usuarios reales en producción, incluida la instalación del `.apk` confirmada por terceros.
- No hay testimonios, métricas de uso ni prensa. No inventarlos.

## Product Principles

1. **Nunca generar culpa.** Nada de números, metas, rachas punitivas ni lenguaje que juzgue lo que se comió.
2. **Registrar tiene que ser más fácil que no hacerlo.** Menos pasos, guardado automático, ponerse al día sin fricción.
3. **Lo cálido también tiene que verse cuidado.** La calidez de Capi no justifica descuido: la app tiene que sentirse profesional para quien la usa a diario y para quien la evalúa.
4. **El historial tiene que servir afuera de la app.** El diario y el PDF tienen que ser claros para una profesional de salud que los lee por primera vez.
5. **La simplicidad siempre gana.** Cada función nueva tiene que ganarse su lugar.
