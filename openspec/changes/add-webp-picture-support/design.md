## Context

El endpoint API v2 de imágenes obtiene el formato desde la extensión del nombre original, valida explícitamente una lista de formatos y redimensiona imágenes rasterizadas mediante GD. GD por sí solo no conserva todos los frames de un WebP animado.

WebP debe integrarse como formato de primera clase en `api/v2/picture/get.php`. Sin redimensionamiento se devolverá el original; con `w`, `h` o `adj` se transformarán todos los frames y se conservarán duración, orden y repetición.

## Goals / Non-Goals

**Goals:**

- Añadir WebP a `api/v2/picture/get.php`.
- Aplicar `w`, `h` y `adj` a WebP estático y animado con la semántica existente.
- Conservar transparencia, frames y metadatos relevantes de animación.
- Servir el original sin transformación cuando no se solicita tamaño.
- Cachear respuestas WebP redimensionadas.

**Non-Goals:**

- Modificar el endpoint legacy `api/api_getPicture.php`.
- Modificar el endpoint genérico de archivos.
- Convertir automáticamente otros formatos a WebP.
- Cambiar autenticación, permisos o validación de dimensiones.

## Decisions

- **Mantener el procesamiento dentro del endpoint v2.** La lógica WebP se integra en `api/v2/picture/get.php`, siguiendo el patrón existente de JPEG, GIF, PNG y SVG.
- **Usar GD para WebP estático e Imagick para WebP animado.** GD cubre el soporte estático disponible; Imagick/ImageMagick permite iterar todos los frames y escribir un WebP animado. Si falta Imagick para un WebP animado, se devuelve un error controlado.
- **Aplicar la transformación a cada frame.** Se conservarán orden, duración, loop y transparencia.
- **Emitir siempre `image/webp` y conservar `.webp` en caché.** Las variantes seguirán diferenciándose por dimensiones y modo de ajuste.

## Risks / Trade-offs

- **[Imagick ausente]** No se podrá redimensionar un WebP animado → devolver error controlado sin degradar al primer frame y documentar el requisito.
- **[Coste de animación]** Procesar todos los frames consume más CPU y memoria → liberar frames procesados y probar distintos tamaños.
- **[Calidad/tamaño]** La escritura WebP requiere una calidad explícita → usar calidad 85 para salidas redimensionadas; el original no se modifica.
