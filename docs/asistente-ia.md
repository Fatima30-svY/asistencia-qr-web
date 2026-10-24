# Asistente escolar con Ollama

El proyecto incluye un modelo generativo local, separado del clasificador bayesiano de ausentismo. No requiere una API de pago. Consume RAM, CPU/GPU y electricidad del equipo donde corre Ollama.

## Uso

- En cualquier pantalla autenticada, abre **¿Necesitas ayuda? · IA**. El asistente recibe una guía breve según el rol real de la sesión y puede orientar sobre el sistema o dudas académicas breves.
- En **Materias > Nueva materia / Editar**, escribe el título y opcionalmente el semestre. Pulsa **Sugerir descripción con IA**. La propuesta aparece aparte: puedes descartarla o usarla. Si ya existe una descripción, se pide confirmación antes de reemplazar el texto del formulario.
- Usar una sugerencia no guarda la materia. El director puede corregirla y después pulsar **Guardar cambios**.
- Si se cambia el título o semestre después de generar la propuesta, debe pedirse una nueva antes de aplicarla.

## Configuración

```dotenv
ASISTENTE_ENABLED=true
OLLAMA_URL=http://127.0.0.1:11434
OLLAMA_MODEL=qwen2.5:1.5b
```

El equipo ya tenía Ollama y `qwen2.5:1.5b` instalados al implementar esta función. Para otro servidor hay que instalar Ollama, disponer de ese modelo e iniciar el servicio. Laravel hace las peticiones desde el servidor; el navegador no necesita acceder directamente al puerto de Ollama. Mantén la dirección de Ollama en una red confiable. Después de cambiar `.env`, ejecuta `php artisan config:clear`.

## Límites de consumo

- Hasta 220 tokens de generación por respuesta de chat y 160 por descripción.
- Contexto del modelo de 2048 tokens; pregunta de hasta 600 caracteres y solo los últimos cuatro mensajes, de hasta 600 caracteres cada uno.
- Hasta seis solicitudes por minuto y sesenta por día por usuario, compartidas entre chat y sugerencias.
- Una generación simultánea para no saturar el modelo local. Si está ocupado, el usuario recibe un aviso y puede continuar escribiendo manualmente.
- Descripciones reutilizadas durante una hora para el mismo título, semestre y modelo.
- Sin reintentos automáticos ni generación al teclear: las consultas siempre requieren pulsar un botón.

Los tokens locales limitan el trabajo del modelo; no son cobros por tokens. La instrucción de longitud en palabras es orientativa; el límite técnico se aplica con `num_predict`.

## Datos y permisos

El chat no consulta ni envía expedientes, nombres, correos, matrículas, asistencias o contraseñas desde la base de datos. Se envían la pregunta escrita por el usuario, el historial breve que conserva esa pestaña y la guía de uso correspondiente a su rol. Por eso se pide no escribir datos personales. Limpiar la conversación o recargar la página elimina el historial del navegador; no se guarda un historial de chat en la base de datos de esta función. Las propuestas de descripción se cachean temporalmente en el servidor.

La IA no dispone de herramientas para modificar registros, aprobar usuarios, registrar asistencia ni borrar datos. Las sugerencias de descripción solo se solicitan con rol administrador. El texto generado se muestra como texto plano y no se ejecuta como HTML. Las respuestas pueden contener errores o propuestas que no correspondan al programa oficial; deben revisarse.

Si Ollama falla, se muestran mensajes genéricos sin rutas internas y siguen disponibles los formularios manuales. La primera respuesta puede tardar mientras se carga el modelo. No se garantiza una latencia determinada.
