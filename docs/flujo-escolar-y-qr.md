# Flujo escolar y QR

## Director

1. Inicia sesión con la cuenta administrativa existente.
2. Abre **Administración escolar** desde el menú o Inicio.
3. Aprueba las cuentas docentes. Los docentes existentes también necesitan aprobación; no se aprueban silenciosamente con la actualización.
4. Crea, consulta, actualiza o elimina materias y grupos. Todos los registros del catálogo se listan, aunque no tengan clases.
5. Asigna cada alumno a un grupo y, opcionalmente, registra su matrícula.
6. Crea clases y asigna materia, grupo y docente aprobado. También puede crear materia y grupo junto con la clase.
7. Modifica o elimina clases sin historial desde Administración escolar. Los registros de asistencia impiden editar la clase y eliminarla individualmente. La eliminación normal de catálogos en uso está bloqueada.
8. Para eliminar un grupo en uso, abre **Eliminación forzada**, revisa el resumen, escribe el nombre exacto y marca la confirmación. Se borran sus clases, las asistencias de esas clases y las excepciones asignadas al grupo. Las cuentas se conservan sin grupo; materias y otros grupos permanecen. El borrado es permanente y se ejecuta en una transacción. Después se debe reentrenar el clasificador si se utiliza.

## Docente

1. Se registra y espera la aprobación del director.
2. Después de la aprobación, accede a sus clases. El director le asigna grupos y materias mediante esas clases.
3. Abre el QR, consulta los alumnos inscritos y los registros y actualiza la lista cuando lo necesite.
4. Cierra la clase para completar faltas y justificaciones. La clase queda cerrada y no acepta nuevos escaneos.
5. Consulta su gráfica y el riesgo de alumnos de los grupos de sus clases. No puede crear clases ni aprobar usuarios.

## Alumno

1. Se registra con rol Alumno e inicia sesión. El director le asigna su grupo; el alumno no puede asignárselo por su cuenta.
2. En **Escanear y ver mi asistencia**, abre el escáner o pega el enlace del QR.
3. También puede usar la cámara del teléfono para abrir el enlace del QR. Si no inició sesión, deberá hacerlo y volverá a la confirmación.
4. Confirma el registro. Abrir el enlace con GET no modifica la asistencia; el guardado requiere POST autenticado y CSRF.
5. Consulta solo su historial y su gráfica de asistencias, retardos, faltas y justificaciones.

El modelo actual permite un grupo por alumno. La pertenencia al grupo da acceso al registro de las materias impartidas a ese grupo. No existe una inscripción independiente a cada materia.

## Edición de grupos y listas rápidas

El menú del director separa **Docentes**, **Alumnos**, **Materias**, **Grupos** y **Clases**. El resumen solo presenta accesos y conteos. Las pantallas de docentes y alumnos son independientes y mantienen búsqueda y paginación. Desde Materias se puede crear o editar la ficha con nombre, clave única opcional, semestre y descripción. El ID interno se muestra por separado de la clave académica. Las materias anteriores conservan sus datos y pueden completar estos campos sin recrearse.

En **Grupos**, abre **Editar** en el grupo correspondiente para reunir las asignaciones rápidas.

- La lista de estudiantes permite buscar por nombre, correo o matrícula, mostrar inscritos o alumnos sin grupo, y agregar o quitar varios alumnos con casillas.
- Los resultados se paginan de 20 en 20. **Seleccionar esta página** solo marca las filas visibles. Guarda antes de cambiar de página o filtro. Agregar una página no quita a los alumnos de las demás páginas.
- Para trasladar alumnos que ya tienen otro grupo se requiere marcar la confirmación. Quitar del grupo conserva sus cuentas e historial.
- Las materias tienen semestre opcional. Las anteriores permanecen sin clasificar hasta editarlas; no se les inventa un semestre. Los buscadores de materias permiten filtrar por nombre y semestre.
- El grupo mantiene una lista de materias asignadas independiente de sus horarios. Puede agregarse o quitarse varias materias, o crear y asignar una nueva desde la edición del grupo. Quitar una materia del plan no elimina sus clases existentes.
- Las relaciones grupo-materia de las clases existentes se recuperan al ejecutar la migración. Las nuevas clases también agregan la materia al plan del grupo.
- En Clases del grupo se puede agregar una clase con el grupo preseleccionado o abrir la edición del horario, docente y materia de una existente. Se mantiene la protección de clases con historial.
- La pantalla principal también incluye búsqueda de estudiantes/docentes por nombre, correo o matrícula, filtros por rol y grupo, y búsqueda de materias por nombre y semestre.

## Reglas aplicadas

- Solo el rol Alumno registra su propia asistencia; no se recibe un alumno_id que permita suplantar a otro usuario.
- El grupo del alumno debe coincidir con el grupo de la clase.
- La clase debe estar abierta y haber comenzado, y el QR debe coincidir y estar vigente.
- Hasta inicio + tolerancia se registra asistencia; después se registra retardo, hasta el final de la clase.
- No se permite más de un registro por alumno y clase. La base de datos también tiene una restricción única.
- Al cerrar, las excepciones aplicables determinan si se registra falta, justificada o si la clase no cuenta para ese alumno.
- El cierre se inicia manualmente; no hay tarea programada de cierre automático.
- La gráfica usa registros reales almacenados; no convierte automáticamente en faltas las clases todavía abiertas.

## Formato para la futura aplicación JavaScript

### Prueba sin cámara

En la pantalla QR del docente se muestra un código manual con formato `id-XXXXXXXXXX`. Se deriva del token actual; no es una contraseña del alumno ni un código permanente. En la cuenta del alumno, abre **Escanear y ver mi asistencia**, escribe ese código y pulsa **Registrar asistencia**. También se sigue admitiendo pegar el enlace del QR.

Se aplican exactamente las mismas validaciones de grupo, horario, vencimiento, cierre y duplicados. En solicitudes JSON se puede enviar `codigo` en lugar de `clase_id` y `token` al mismo endpoint autenticado. La pantalla docente comprueba la vigencia por JSON cada 15 segundos sin navegar ni recargar toda la página. El token y el código se renuevan al vencer (15 minutos como máximo), con vencimiento limitado al final de la clase. Después del cierre o del final del horario no se generan nuevos códigos para esa clase. Cada nueva clase tiene sus propios códigos.

Para probar con dos cuentas en el mismo equipo utiliza sesiones separadas, por ejemplo dos perfiles del navegador o una ventana normal y otra de incógnito. El resultado será asistencia o retardo según el horario real; la entrada manual no omite ninguna regla. Para actualizar la lista del docente usa **Actualizar lista y QR**. El lector de cámara requiere todavía prueba en un dispositivo real.

El QR contiene una URL: `/alumno/asistencia/{clase}?token={token}`. El lector web también admite el formato anterior `{"c":1,"t":"token"}`.

El endpoint web `POST /alumno/asistencia` acepta JSON con `clase_id` y `token`, una sesión Laravel autenticada y encabezado `X-CSRF-TOKEN`. Con `Accept: application/json` devuelve estado 201 y el estado guardado, o un error de validación/permisos. No es una API con tokens móviles; si la aplicación futura corre en otro origen o es nativa, se necesitará implementar su autenticación e integración específicas.

## Cámara y acceso desde teléfono

El escáner integrado usa BarcodeDetector si el navegador lo soporta y requiere permiso de cámara y contexto seguro (HTTPS o localhost). Siempre hay entrada manual y se puede abrir el QR desde la cámara del teléfono. Las funciones de cámara requieren validación en el dispositivo real.

Para abrir un QR desde otro dispositivo, la aplicación debe estar publicada en una dirección alcanzable por ese teléfono. Un enlace con 127.0.0.1 apunta al propio teléfono, no al equipo donde corre Laravel. Abre la pantalla del docente desde la dirección pública/HTTPS que usarán los alumnos antes de mostrar el QR.

## Registro administrativo

El registro público permite crear al director inicial solo mientras no exista ningún administrador. Después solo permite docente o alumno, para que el proceso de aprobación no se pueda omitir creando una cuenta administrativa pública. Las cuentas administrativas existentes se conservan.
