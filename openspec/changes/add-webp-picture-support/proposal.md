## Why

El endpoint API v2 de imágenes (`api/v2/picture/get.php`) rechaza WebP, aunque es un formato habitual para imágenes optimizadas para web. WebP puede ser animado y esa animación debe conservarse al entregarlo o redimensionarlo.

## What Changes

- Añadir WebP estático y animado a los formatos aceptados por el endpoint API v2 de pictures.
- Devolver WebP original con `Content-Type: image/webp` cuando no se solicita redimensionamiento.
- Redimensionar WebP usando `w`, `h` y `adj`, preservando todos los frames, sus duraciones, el orden y el bucle de los WebP animados.
- Generar y reutilizar caché WebP durante las respuestas redimensionadas.
- Mantener el comportamiento actual de las demás extensiones y devolver error para formatos no soportados.

## Capabilities

### New Capabilities

- `webp-picture-support`: Soporte de WebP estático y animado en la entrega, redimensión y caché de `api/v2/picture/get.php`.

### Modified Capabilities

<!-- No existing OpenSpec capabilities are present. -->

## Impact

- `Server/htdocs/AppController/commands_RSM/api/v2/picture/get.php`
- La lógica de procesamiento WebP se mantiene dentro de `api/v2/picture/get.php`, siguiendo el patrón de los demás formatos.
- Respuestas HTTP y archivos de la caché de imágenes del endpoint API v2.
- Requiere una capacidad de servidor que lea y escriba WebP animado sin perder frames ni metadatos de animación.
