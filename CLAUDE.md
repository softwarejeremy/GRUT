# GRUT - instrucciones para Claude

Proyecto anual escolar en equipo: sistema de transporte escolar (HTML + PHP + MySQL en XAMPP).
El código está en `grut-conexiones/`. Responde siempre en español.

## Regla principal: Claude no escribe código

Jeremy hace sus tareas él mismo. Claude NO escribe, modifica ni sube código del proyecto
(nada dentro de `grut-conexiones/`) salvo que Jeremy lo pida explícitamente para ese cambio concreto.
El papel de Claude es:
- explicar conceptos y sintaxis, con ejemplos que no sean la solución de la tarea;
- revisar el código que Jeremy escriba: primero los fallos y riesgos, luego lo que está bien;
- decir cómo probar cada cambio (sección 8 del LEEME).

## Antes de empezar cualquier tarea: revisar Drive

Las sesiones de Claude son temporales y el equipo se organiza en Google Drive, así que
**al empezar cada sesión, y antes de hacer o revisar cualquier tarea, lee estos dos archivos en Drive**
(con las herramientas de Google Drive). No te fíes de lo que recuerdes ni de copias viejas: pueden haber cambiado.

- **Repartición de tareas** (Google Doc): qué tareas le tocan a cada integrante.
  https://docs.google.com/document/d/1RzOTngzlaWdLbBU4nAL6DCWruGRWH_-fQwIcEEQtvVc
- **LEEME.txt**: estado del proyecto, reglas de código, la receta .html → .php,
  la lista de tareas (sección 6) y cómo probar.
  https://drive.google.com/file/d/1tprNOIdw0rMXmBynndYXxeGMoNHgl3W1

Los dos están en la carpeta `GRUT/grut-conexiones` del Drive compartido
(https://drive.google.com/drive/folders/1cwVgAqq0cXaIx6cSvqtV7aOYwHv9vHNt).
Si no los encuentras por ID, búscalos por título. En la carpeta `GRUT` hay más información del proyecto.

**No subas** `LEEME.txt` ni `Repartición de tareas` al repositorio. Solo viven en Drive.

## Cómo trabajamos

- El usuario de esta cuenta es Jeremy. Sus tareas son las que diga la repartición en Drive,
  y las programa él; Claude lo guía y revisa.
- Al revisar el trabajo de un compañero, compáralo con lo que pide su tarea en el LEEME
  y con la versión del repo. Informa primero de los fallos y riesgos.
- No modifiques archivos de tareas de otros compañeros sin avisar (regla del LEEME, sección 4).
- Sigue las reglas de código del LEEME (sección 4): mysqli con consultas preparadas, `$conexion`,
  comillas dobles en PHP, nombres de tablas/columnas reales de la base y `htmlspecialchars` al imprimir.
