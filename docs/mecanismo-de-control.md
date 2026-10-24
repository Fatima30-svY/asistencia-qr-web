# Mecanismo de control del sistema de asistencia

## 1. ¿Qué es el mecanismo de control?

Es la parte que determina en qué orden se comprueban las condiciones, cuándo se detiene una operación y qué resultado se guarda. En este proyecto coordina las reglas de asistencia y la selección de excepciones.

Los hechos son los datos disponibles: grupo del alumno, horario de la clase, token QR, registros anteriores y excepciones vigentes. Las reglas indican qué hacer con ellos. El mecanismo de control establece cómo recorrer esas reglas para llegar a una decisión.

## 2. ¿Qué tipo utiliza el proyecto?

Utiliza un control procedimental, secuencial y determinista, con prioridad por especificidad para las excepciones.

- **Procedimental:** el recorrido está escrito en los métodos PHP; no existe un motor general que elija dinámicamente entre todas las reglas.
- **Secuencial:** las validaciones del escaneo se ejecutan en el orden definido en el código.
- **Determinista:** con los mismos datos, el mismo momento de evaluación y el mismo estado de la base de datos, las reglas producen el mismo resultado.
- **Con interrupción ante un error:** si una validación falla, se lanza una excepción y no se continúa hasta guardar la asistencia.
- **Con prioridad por especificidad:** al buscar excepciones se prefiere alumno, después grupo y finalmente general.

Su dirección de razonamiento va de los datos al resultado: se consultan los hechos y se obtiene asistencia, retardo, falta o justificación. Esto se parece al encadenamiento hacia adelante, pero la implementación no es un motor formal de encadenamiento con agenda y repetición hasta una meta.

## 3. ¿Es automático o manual?

El funcionamiento general es mixto o semiautomático: una persona inicia las operaciones y el código evalúa automáticamente las condiciones.

| Operación | Intervención manual | Parte automática |
|---|---|---|
| Programar una clase | El docente captura materia, grupo y horario. | El sistema valida los campos y guarda la clase. |
| Mostrar el QR | El docente abre la pantalla de la clase. | Se genera o renueva el token cuando corresponde y se construye el QR. |
| Evaluar un escaneo | Se requiere una entrada de escaneo; la interfaz del alumno está pendiente. | El servicio comprueba las reglas y determina asistencia o retardo cuando es invocado. |
| Registrar una excepción | Un usuario autorizado captura tipo, fechas y motivo. | El sistema valida los datos y los permisos. |
| Completar faltas al cerrar | El docente pulsa la opción de cierre. | Se recorren los alumnos sin registro, se buscan excepciones y se guarda el resultado aplicable. |
| Entrenar el clasificador | Un usuario autorizado solicita el entrenamiento o se ejecuta el comando. | Se procesa el historial y se guarda el modelo. |

No hay cierre automático por horario en las rutas y operaciones revisadas. La hora se comprueba al ejecutar las reglas; no inicia por sí sola el cierre de la clase.

## 4. Ciclo de control del escaneo

Implementación: `app/Services/ReglasAsistencia.php`, método `registrarEscaneo`.

1. **Recibir los hechos.** Se reciben la clase, el alumno, el token y el momento del escaneo. Si no se proporciona un momento, se utiliza la hora actual.
2. **Validar el token.** Debe existir un token de clase y coincidir con el recibido. Si no coincide, la operación se detiene.
3. **Comprobar la vigencia.** Si existe una fecha de vencimiento y ya pasó, se rechaza el QR.
4. **Comprobar el grupo.** El alumno debe pertenecer al grupo de la clase.
5. **Evitar duplicados.** Se consulta si el alumno ya tiene un registro en esa clase. Si existe, se rechaza el nuevo registro.
6. **Comprobar el final de la clase.** Si el momento del escaneo es posterior al final, se detiene la operación.
7. **Seleccionar el estado.** Hasta el inicio más la tolerancia se asigna asistencia; después de ese límite se asigna retardo, siempre que se hayan superado las validaciones anteriores.
8. **Guardar el resultado.** Se crea el registro con clase, alumno, momento de escaneo y estado.

```text
Datos del escaneo
       ↓
Token → Vigencia → Grupo → Duplicados → Fin de clase
       ↓ Todas las comprobaciones permiten continuar
Comparar hora del escaneo con inicio + tolerancia
       ↓
Asistencia o retardo → Guardar en la base de datos

Si falla una comprobación: detener y devolver el motivo.
```

La comprobación de cada paso es automática cuando se ejecuta el servicio. Actualmente falta la pantalla y la ruta del alumno que conecten el escaneo con este método; el ciclo descrito existe como lógica del servidor.

## 5. Ciclo de control del cierre y las excepciones

Implementación: `ReglasAsistencia::cerrarClase` y `app/Services/ExcepcionesAsistencia.php`.

1. El docente solicita el cierre y el controlador verifica que la clase le pertenezca.
2. El servicio obtiene los alumnos del grupo de la clase.
3. Si un alumno ya tiene registro, lo conserva y continúa con el siguiente.
4. Para un alumno sin registro, busca excepciones cuyo periodo incluya la fecha de la clase y cuyo alcance le corresponda.
5. Si hay varias, selecciona primero la excepción individual; si no existe, la del grupo; si tampoco existe, la general.
6. Si la seleccionada es suspensión o festivo, no crea registro: la clase no cuenta para ese alumno.
7. Si la seleccionada es justificante o permiso, crea un registro con estado `justificada`.
8. Si no encuentra una excepción, crea un registro con estado `falta`.
9. Repite el recorrido para los demás alumnos y devuelve la cantidad de registros creados.

La prioridad resuelve el conflicto entre excepciones de distinto alcance. El código no establece un desempate explícito entre varias excepciones del mismo nivel de especificidad; por eso no se debe afirmar que siempre gana la más reciente o un tipo determinado.

La operación actual completa los registros faltantes, pero no almacena una marca persistente de clase cerrada.

## 6. Ejemplo para mostrar en equipo

Los siguientes datos son ilustrativos:

- La clase C1 pertenece al grupo 3A.
- Ana y Luis son alumnos del grupo 3A y no tienen registro en C1.
- Ana tiene un justificante vigente para la fecha de C1.
- Luis no tiene excepciones aplicables.

**Acción manual:** el docente solicita el cierre de C1.

**Evaluación automática:** el sistema encuentra el justificante de Ana y guarda una falta justificada. Para Luis, al no encontrar una excepción, guarda una falta. Después devuelve el total de registros creados.

Este ejemplo permite mostrar que el docente inicia el proceso, pero no selecciona manualmente el resultado de cada alumno: las condiciones programadas y los datos disponibles determinan el resultado.

## 7. ¿Por qué es importante?

- **Mantiene un orden de evaluación:** primero se verifica si una operación es válida y después se decide qué guardar.
- **Evita registros inválidos:** rechaza tokens incorrectos, QR vencidos, alumnos de otro grupo y registros repetidos durante la comprobación.
- **Aplica las excepciones antes de la falta general:** permite considerar justificantes, permisos, suspensiones y festivos.
- **Hace explicable la decisión:** es posible señalar qué condición rechazó un escaneo o qué excepción produjo una justificación.
- **Reduce decisiones repetitivas:** el docente inicia el cierre y el sistema recorre los alumnos y aplica las mismas condiciones.

## 8. Relación con el clasificador bayesiano

El clasificador es un componente diferente del control de asistencia: calcula una probabilidad de falta a partir del historial. No decide si el escaneo es válido ni sustituye las reglas de justificantes. Su entrenamiento se solicita manualmente y sus cálculos se ejecutan automáticamente. Una vez calculada la probabilidad, el código asigna riesgo bajo, medio o alto mediante umbrales.

## 9. Texto breve para exponer

"Nuestro proyecto utiliza un mecanismo de control secuencial y procedimental. Esto significa que las reglas se revisan en un orden establecido: primero se valida el QR, después el grupo, los registros anteriores y el horario. Si una condición falla, se detiene el registro. Al cerrar una clase, se revisan las excepciones antes de asignar una falta, dando prioridad a las del alumno sobre las del grupo y las generales. El funcionamiento es semiautomático porque el docente inicia acciones como el cierre, pero el sistema evalúa las reglas y guarda los resultados automáticamente. Este mecanismo es importante porque mantiene decisiones consistentes y permite explicar por qué se obtuvo cada resultado."
