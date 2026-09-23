## 1. Procesamiento WebP

- [x] 1.1 Verificar la capacidad disponible de PHP para leer, transformar y escribir WebP animado, y documentar/configurar el requisito de despliegue.
- [x] 1.2 Implementar dentro de `api/v2/picture/get.php` un flujo que detecte imágenes estáticas y animadas, procese todos los frames y conserve duraciones, orden y bucle.
- [x] 1.3 Aplicar a cada frame las transformaciones existentes de `w`, `h` y `adj`, preservando transparencia y usando calidad WebP explícita.

## 2. Endpoint API v2 y caché

- [x] 2.1 Añadir `webp` a la validación de formatos de `api/v2/picture/get.php`.
- [x] 2.2 Emitir `Content-Type: image/webp` para WebP original, redimensionado y recuperado desde caché.
- [x] 2.3 Guardar y recuperar variantes WebP estáticas y animadas en la caché existente.
- [x] 2.4 Devolver un error controlado cuando el servidor no pueda procesar WebP animado, sin degradar la respuesta al primer frame.

## 3. Verificación

- [x] 3.1 Probar WebP estático original y redimensionado con cada modo `adj` soportado.
- [ ] 3.2 Probar WebP animado original verificando frames, orden, duración y repetición.
- [ ] 3.3 Probar WebP animado redimensionado y recuperado desde caché verificando que no se pierdan frames ni transparencia.
- [ ] 3.4 Verificar formatos no soportados y ejecutar la suite de compatibilidad/regresión aplicable.
