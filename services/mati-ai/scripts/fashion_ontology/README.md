# Fashion Ontology V1 — herramientas de diseño

Los JSON de `../../resources/fashion_ontology/v1/` son la fuente formal. La documentación de alcance, decisiones y revisión está en `DOCUMENTACION/IA/FASHION_ONTOLOGY_V1/`.

Requisitos de esta CLI independiente: Python >=3.11 y `jsonschema>=4` (Draft 2020-12). Los tests usan unittest de la biblioteca estándar. No se añadió ninguna dependencia al servicio operativo ni se importa `mati_ai`.

```sh
python services/mati-ai/scripts/fashion_ontology/validate_fashion_ontology.py
python -m unittest discover -s services/mati-ai/scripts/fashion_ontology -p test_fashion_ontology.py -v
```

El validador solo lee archivos y emite JSON a stdout; exit 0 indica validación automática, no freeze de dominio. Exit 1 expone errores. No conecta a DB, modelos ni red. Un intérprete sin jsonschema devuelve un error de dependencia explícito.

El exportador escribe solamente summary.json, coverage_matrix.csv, annotation_matrix.csv y hashes.json, derivados de los maestros estructurados:

```sh
python services/mati-ai/scripts/fashion_ontology/export_fashion_ontology.py
```

Tras editar un maestro: revisar semántica/versión, regenerar los cuatro derivados, ejecutar validator y tests y solicitar la revisión de dominio que corresponda. No modificar matrices a mano para ocultar conflictos. No añadir una alias local ambigua como equivalencia universal. El manifest usa SHA256 de bytes exactos e incluye schemas, ejemplo y matrices; excluye su propio archivo para evitar hash circular.

En esta sesión Windows se usó Python 3.12.14 empaquetado y jsonschema 4.26.0 instalado en una carpeta temporal independiente (`C:\Users\Usuario\AppData\Local\Temp\more-fashion-ontology-v1-python`). Esa ruta es evidencia del entorno de la sesión, no requisito portable del repositorio. El servicio continúa con su configuración Python original.

## Revisión 1.1.0 del borrador V1

El registro governance.json separa políticas humanas aprobadas de ámbitos pendientes y seis fases independientes. Se preservan los IDs de 1.0.0; ver matriz17, decisiones15, transición24 y reporte16. Los contratos18–22 son diseños sin servicios. Puños y nuevos estilos no autorizan entrenamiento.

La revisión se validó con Python empaquetado y jsonschema 4.26.0 en una carpeta aislada de la sesión (`ontology-python-deps` bajo el directorio de visualizaciones). No se cambian dependencias de mati-ai. Para reproducir, usar Python >=3.11 con jsonschema >=4 y los comandos portables anteriores. Esta nota no convierte la ruta local de sesión en requisito del proyecto. Los resultados actuales usan prefijo review_ y no sustituyen los resultados históricos.
