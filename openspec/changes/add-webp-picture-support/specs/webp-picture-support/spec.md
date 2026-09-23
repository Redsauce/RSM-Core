## ADDED Requirements

### Requirement: API v2 accepts WebP pictures
El endpoint API v2 `api/v2/picture/get.php` SHALL aceptar WebP estático y animado con extensión `webp`, además de los formatos ya soportados, y SHALL rechazar formatos no incluidos en la lista soportada.

#### Scenario: WebP original is requested
- **WHEN** un cliente autorizado solicita una imagen WebP sin `w` ni `h`
- **THEN** el endpoint SHALL devolver los bytes originales con estado exitoso y `Content-Type: image/webp`

#### Scenario: Animated WebP original is requested
- **WHEN** un cliente autorizado solicita un WebP animado sin `w` ni `h`
- **THEN** el endpoint SHALL devolver todos los frames, sus duraciones, su orden y su configuración de repetición

#### Scenario: Unsupported image format is requested
- **WHEN** un cliente solicita una imagen con una extensión no soportada
- **THEN** el endpoint SHALL devolver el error de formato desconocido existente

### Requirement: API v2 resizes WebP with existing picture parameters
El endpoint API v2 SHALL aplicar a WebP estático y animado los parámetros `w`, `h` y `adj` con la misma semántica que aplica a JPEG, GIF y PNG.

#### Scenario: WebP is resized by width and height
- **WHEN** un cliente autorizado solicita una imagen WebP con `w` y/o `h`
- **THEN** el endpoint SHALL calcular las dimensiones según `adj` y devolver una imagen WebP redimensionada con `Content-Type: image/webp`

#### Scenario: WebP uses each supported adjustment mode
- **WHEN** se solicita WebP con cualquiera de los modos `s`, `f`, `w`, `h`, `d` o `c`
- **THEN** el endpoint SHALL producir el mismo encuadre, escalado o recorte definido para otros formatos rasterizados

#### Scenario: Animated WebP is resized without dropping frames
- **WHEN** se solicita un WebP animado con `w`, `h` y un modo de ajuste soportado
- **THEN** el endpoint SHALL aplicar la transformación a todos los frames y SHALL conservar el orden, las duraciones y el bucle

#### Scenario: WebP transparency is preserved
- **WHEN** se redimensiona un WebP con transparencia
- **THEN** la respuesta SHALL conservar el canal alfa en cada frame

### Requirement: API v2 caches WebP pictures
El endpoint API v2 SHALL guardar y recuperar variantes WebP estáticas y animadas usando la caché existente, diferenciando la combinación de dimensiones y modo de ajuste.

#### Scenario: Resized WebP is cached
- **WHEN** se solicita por primera vez una variante WebP redimensionada con la caché habilitada
- **THEN** el endpoint SHALL generar un archivo de caché WebP reutilizable y devolver la variante solicitada

#### Scenario: Animated WebP cache preserves animation
- **WHEN** se recupera de caché una variante WebP animada redimensionada
- **THEN** la respuesta SHALL conservar todos los frames, sus duraciones y su configuración de repetición
