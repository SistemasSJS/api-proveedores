
Quiero que me ayudes a realizar un formato para el diseño de modulos webs.
Para esto requiero que de forma clara especificar las partes que conforman al modulo.

all components, pages deben tener l a estructura:
|--{NAME}-list.{component | page}.ts
|--{NAME}-list.{component | page}.html
|--{NAME}-list.{component | page}.css

1. Estructura de directorias
   |--import-productos
   |--pages
   |--import-profductos-lista-page
   |--import-profductos-detalle-page
   |--import-profductos-upload-file
   |--components
   |--....
   |--model
   |--....
   |--shared
   |--services ----> en @core esta un servicio para importacion de productos usaremos ese
   |--index.ts
   |--import-productos.module.ts
   |--import-productos-routing.module.ts

BACKEND:

- en el controlador dle backend: ProductosImportController.php
  add bulk insert data para volcado de datos...
  sin necesidad de subir archivos en diversos formatos

flujo de subida de catalogo de productos:

1. [ GET /api/import/template/{tipo} ] Se descarga la plantilla prefabriocadad desde la pagina.
2. (fuera de la pagina) El usuairo llean los datos de los productos dentro de la plantilla.
3. Sube el archivo a la plataforma. (Se soportan varios formatos)  
   3.1 De forma local se analiza el contenido
   3.2
4. El frontend muestra de forma tabular los datos de los porductos (solo para vistas de escritorio  en movil no prepares nada aun)
5. El formato tabular permitira realizar edicion de items.

- la tabla debe poder filtrar por un search-bar
- reordenameinto por colunas
- paginado de datos

6. Se permitira realizar seleccion de regustros a subir.

nota:

- la subida se debe hacer enn segmentos de datos, con los end points de registrar prodcutos.
- se debe guardar registros historico de los cambios de lso productos
  Especificiacion de las paginas:
  PAGE: import-profductos-upload-file

-
