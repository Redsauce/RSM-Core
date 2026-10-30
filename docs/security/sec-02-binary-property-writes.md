# SEC-02: escrituras de archivos e imágenes

## Formato de transporte

Se conserva el formato de `api/v2/items/create.php` y `update.php` para que los
clientes actuales, incluido Xojo Android, no tengan que migrar a Base64:

```json
{"101": "archivo.bin:00aaff"}
```

V1 conserva sus envoltorios existentes: `api_createItem.php` y
`api_updateItem.php` reciben el valor `nombre:hexadecimal` codificado en Base64;
`api_updateItems.php` recibe el JSON completo codificado en Base64, con esos
valores como cadenas. Los resultados correctos de v1 siguen usando el XML
habitual y los de v2 siguen usando JSON.

El valor es una cadena `[nombre:]hexadecimal`. El nombre es opcional; el contenido
puede llevar el prefijo `0x` o `0X`. Solo se acepta una secuencia completa de
caracteres `0-9`, `a-f` y `A-F`, con longitud par. No se aceptan espacios, saltos
de línea ni expresiones SQL. Se permiten comillas y Unicode en el nombre, que
se almacena como un parámetro SQL. En creación se mantiene la conversión
anterior a entidades HTML del nombre, tanto en v1 como en v2; en actualización
se mantiene el nombre recibido. El nombre almacenado tiene un máximo de 255 caracteres
UTF-8 y no puede contener `:`, NUL, CR ni LF.

El contenido vacío sigue permitido: en creación produce un BLOB vacío; en el
endpoint de actualización elimina el valor de la propiedad, como antes.
Esta validación comprueba el transporte hexadecimal, no el formato de una imagen.

## Límites y respuestas

`$RSmaxBinaryPropertyBytes` en `utilities/RSconfiguration.php` limita el contenido
decodificado de cada propiedad. El valor inicial es **32 MiB** (33.554.432 bytes). La comprobación
se realiza antes de `hex2bin()`, después de decodificar y antes de almacenar.
El JSON ya ha sido leído al validar la propiedad; los límites del cuerpo HTTP
de PHP y del servidor web deben configurarse también en el servidor.
Una carga de 32 MiB necesita más de 64 MiB de cuerpo HTTP en hexadecimal;
con el envoltorio Base64 de v1 necesita aproximadamente 85,4 MiB, más metadatos.

Los endpoints de ambas versiones rechazan un valor mal formado con HTTP **400**, y uno que excede
el límite con HTTP **413**. Validan todas las cargas de la petición antes de
empezar a modificar items. No sustituyen una carga inválida por un valor por
defecto. La creación también valida los valores binarios por defecto y revierte
la transacción si alguno es inválido.

## Almacenamiento y transacciones

`_dbInsertItemPropertyValue()` y `setDataPropertyValueByID()` delegan las escrituras
binarias en consultas preparadas. Identificadores, nombre, tamaño y contenido se
envían como parámetros. El tamaño se calcula a partir de los bytes decodificados.
El BLOB se transmite con `mysqli_stmt::send_long_data()` en bloques de 64 KiB;
se comprueba cada envío y se cierran las sentencias también ante un fallo.
La auditoría de creación utiliza igualmente parámetros para sus metadatos y BLOB.

`createItem()` comprueba todas las escrituras del item y sus propiedades,
incluida la auditoría, y devuelve `0` ante un fallo. Conserva sus tres argumentos,
gestiona su propia transacción y bloquea el contador del tipo de item durante la
asignación de IDs. La escritura común está en `RScreateItemInTransaction()`.
Solo el lote v2 y el asignador de subcuentas llaman directamente a ese escritor,
porque ya controlan una transacción completa: deben revertirla ante un resultado
`0`. No hay cuarto argumento, detección de transacciones ni savepoints.

El endpoint v2 de creación utiliza una transacción para toda la petición: si
falla un item, revierte también los anteriores del mismo lote y devuelve HTTP
**500**. Los IDs revertidos se retiran de la lista que genera eventos de creación.
La atomicidad requiere tablas transaccionales, como las tablas InnoDB del esquema
del repositorio. El endpoint de actualización no incorpora una transacción de
lote; su validación previa evita cambios parciales causados por cargas inválidas.
En v1 se conserva la transacción por item de creación y la respuesta XML
existente; su validación previa del lote también evita empezar a escribir
cuando contiene una carga binaria mal formada o demasiado grande.

## Verificación

```powershell
php -d extension_dir=C:/php-8.5.0/ext -d extension=php_sqlite3.dll -d extension=php_mbstring.dll scripts/test_binary_property_writes.php
```

La prueba ejecuta las funciones de producción y los endpoints sobre un adaptador
SQLite con consultas preparadas. Cubre contenido binario con NUL, comillas en
nombres y tokens, límites de tamaño, rechazos de inyección, envío por bloques,
BLOB vacío, auditoría y rollback ante fallos de propiedades, contadores,
metadatos, envío, inicio y confirmación de transacción, además de permisos y
ámbito de cliente. Verifica también que un fallo en el segundo item revierte
el primero en v2, la compatibilidad de nombres y campos de éxito en v1/v2,
los tres endpoints v1, el límite exacto de 32 MiB y el HTTP 413 real del helper
de v1. No verifica el protocolo MySQL ni los bloqueos concurrentes contra
un servidor MySQL/MariaDB real.

Al desplegar, incluir el nuevo archivo `utilities/RSMbinaryProperties.php` junto
con los cambios de los endpoints y las utilidades. El límite tiene un valor de
respaldo de 32 MiB si la configuración del servidor aún no define la variable.

## Rutas administrativas y compartidas

Los iconos de tipos de item (`classLbxItemtypesList_createItemtype.php` y
`classLbxItemtypesList_updateItemtype.php`) y las imágenes de variables globales
(`Globals/classLbxGlobals_update.php`) usan `utilities/RSMbinaryUploads.php`.
Conservan sus campos de entrada y los campos XML de éxito. Sus datos binarios
pasan por el mismo parser, límite de 32 MiB y envío por bloques que las propiedades.
Sus nombres, IDs y valores escalares también se escriben mediante parámetros.

Las variables globales validan todas las imágenes antes de eliminar valores
anteriores; las comprobaciones de conflicto, eliminaciones e inserciones se
ejecutan en una transacción. Un fallo revierte también los renombrados.

`classWndImportCSV_import.php` conserva el transporte Base64 y sus códigos
`OK`, `ERROR0`, `ERROR1`, `ERROR2` y `ERROR3`. Delega la importación al helper común,
que valida las cargas antes de escribir y utiliza `_dbInsertItemPropertyValue()`
para todas las propiedades. El modo de sobrescritura usa `REPLACE` parametrizado,
conserva las propiedades omitidas y no añade auditoría que el importador anterior
no generaba. Los items, propiedades y contador se confirman juntos; solo se
generan eventos tras una importación correcta y se invalidan las cachés de medios.
Los errores ya no incluyen consultas SQL con datos de la carga.

DataManager sigue utilizando `createItem()` y `setDataPropertyValueByID()`.
La edición valida todas las cargas visibles antes de escribir y comprueba el
resultado del escritor; los comandos de creación informan del fallo en lugar
de devolver un ID `0` como si se hubiera creado un item. El editor de propiedades
`classLbxPropertyValues_setProperties.php` ya utiliza el mismo escritor común.

La prueba incluye los endpoints reales de iconos, globals, CSV y DataManager,
además de las APIs v1/v2. Comprueba los campos de éxito, datos binarios y nombres
con comillas, conflictos, separación de clientes, cargas inválidas y demasiado
grandes, rechazo antes de escrituras parciales, rollback y respuesta de fallo
cuando el almacenamiento falla. Estas verificaciones usan SQLite, no un servidor
MySQL/MariaDB real.

Al desplegar estas rutas, incluir también `utilities/RSMbinaryUploads.php`.

## Despliegue gradual

El orden de despliegue está en `sec-02-deploy-plan.json`. Generar los paquetes
con `php scripts/build_security_deploy.php`: valida la sintaxis, copia las
versiones locales y registra SHA-256 de cada archivo. No conecta con producción.
Las rutas de los paquetes empiezan en `AppController/commands_RSM`.
Sustituir cada archivo completo mediante renombrado, sin servir un PHP a medio
copiar. Mantener el orden entre etapas; los archivos de la etapa 01 se pueden
actualizar individualmente antes de activar el gestor de la etapa 02.

1. **00-foundation:** añadir `RSMbinaryProperties.php` y después
   `RSMbinaryUploads.php`. Añadir helpers no activa por sí solo las nuevas rutas.
2. **01-callers:** actualizar los llamadores listados. Se pueden copiar uno a
   uno. Comprueban el fallo de creación antes de continuar, conservan los campos
   de éxito y funcionan con la firma anterior de `createItem`.
3. **02-shared-manager:** actualizar juntos `RSMitemsManagement.php`,
   `shared/classLbxSubaccounts_addSubAccount.php` y `api/v2/items/create.php`,
   mediante un único cambio de release o pausando peticiones durante la copia.
   Este es el bloque mínimo acoplado: no servir versiones mezcladas de estos tres
   archivos. El gestor carga el escritor
   directamente, sin depender de que `RSMpropertiesManagement.php` esté actualizado.
   Valida el transporte binario original antes de que un validador antiguo pueda
   sustituirlo por un valor por defecto. `createItem` mantiene tres argumentos y
   controla su transacción. Subcuentas y el lote v2 utilizan la misma escritura
   interna dentro de la transacción que ya controlan. No hay modo permisivo,
   flags ni savepoints. El asignador retira los eventos de items revertidos.
4. **03-routes:** actualizar las rutas listadas, individualmente. Validar cada una
   antes de avanzar. Ya disponen de helpers y del escritor seguro de la etapa 02.
5. **04-properties:** actualizar `RSMpropertiesManagement.php` para alinear el
   validador común, después de migrar el CSV y sus demás llamadores.

Los helpers deben permanecer instalados después de actualizar sus consumidores.
Una ruta todavía no migrada conserva los límites de su implementación anterior;
la etapa preparatoria no equivale a completar SEC-02. No se desactivan validación,
consultas preparadas ni rollback para facilitar el despliegue.

Después de la etapa 02 comprobar creación en raíz y con padre, sobrescritura CSV,
archivos/imágenes y propiedades con auditoría. Un resultado `NOK` requiere resolver
el fallo antes de seguir; nunca volver a ignorar errores para obtener `OK`.
Este plan no afirma que se haya resuelto el fallo del tipo 71 en producción.
No se han leído ni modificado los archivos de ese servidor.

Pruebas locales de versiones mezcladas:

```powershell
php -d extension_dir=C:/php-8.5.0/ext -d extension=php_sqlite3.dll -d extension=php_mbstring.dll scripts/test_binary_property_writes.php --legacy-properties --legacy-manager
php -d extension_dir=C:/php-8.5.0/ext -d extension=php_sqlite3.dll -d extension=php_mbstring.dll scripts/test_binary_property_writes.php --legacy-properties
php -d extension_dir=C:/php-8.5.0/ext -d extension=php_sqlite3.dll -d extension=php_mbstring.dll scripts/test_binary_property_writes.php
```

La primera ejecuta los llamadores de árbol, API v1 y DataManager actualizados
con gestor y propiedades de `c284da4`, incluyendo creación en raíz y con padre.
La segunda ejecuta la suite completa con
el escritor nuevo y las propiedades de esa revisión. La tercera usa las versiones
actuales. Incluyen fallo de creación/auditoría, rechazo binario, rollback y una
transacción propiedad del llamador. Son pruebas SQLite con adaptadores; no prueban
los bloqueos MySQL ni garantizan las versiones o configuración actuales de PROD.
Los lotes de conceptos y otras operaciones compuestas conservan su alcance
transaccional existente: el rollback de `createItem` se refiere al item creado,
no a todas las operaciones previas de un endpoint.
