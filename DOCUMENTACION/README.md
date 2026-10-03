# Documentación del sistema MORE

Esta carpeta reúne la documentación funcional y técnica del sistema MORE construido hasta este punto. Su objetivo es servir como referencia para desarrollo, revisión, presentación del proyecto y toma de decisiones sobre próximos pasos.

La documentación está escrita con base en la estructura real del proyecto: rutas, controladores, modelos, migraciones, componentes React, tipos TypeScript, estilos y configuración actual. No describe como finalizadas funciones que todavía están en estado parcial, informativo o de consulta.

## Índice

1. [Visión general](01_vision_general.md)
2. [Arquitectura](02_arquitectura.md)
3. [Diseño y UI/UX](03_diseno_y_ui_ux.md)
4. [Módulo público](04_modulo_publico.md)
5. [Módulo superadmin](05_modulo_superadmin.md)
6. [Autenticación y roles](06_autenticacion_y_roles.md)
7. [Catálogo y prendas](07_catalogo_y_prendas.md)
8. [Validaciones y reglas de negocio](08_validaciones_y_reglas.md)
9. [Estado actual y pendientes](09_estado_actual_y_pendientes.md)
10. [Categorías y sugerencia de tallas en superadmin](10_categorias_tallas_superadmin.md)
11. [Temporadas en superadmin](11_temporadas_superadmin.md)
12. [Colores en superadmin](12_colores_superadmin.md)
13. [Registro inteligente de prendas: resumen y funcionamiento](49_registro_inteligente_ia.md)

## Alcance documentado

La documentación cubre:

- Storefront público: landing, catálogo, colecciones, editorial, detalle de producto, favoritos, bolsa y cuenta.
- Panel superadmin: dashboard, seguridad, catálogo, inventario, comercial, reportes, logs y perfil.
- Modelo de datos: usuarios, roles, prendas, variantes, imágenes, inventario, ventas, reservas y favoritos.
- Reglas de acceso, validaciones, restricciones de eliminación y protección de roles críticos.
- Registro inteligente de prendas: análisis de fotos, barcode/QR, etiquetas, coincidencias, fuentes externas y revisión humana.
- Estado real de madurez por módulo.

## Criterio de lectura

Cuando un módulo se describe como **CRUD real**, significa que permite crear, consultar, editar y guardar registros desde el panel. Cuando se describe como **solo lectura**, significa que consulta registros existentes pero no implementa creación o edición operativa desde esa pantalla. Cuando se describe como **no listo**, significa que la vista existe como referencia informativa, pero no ejecuta cambios de negocio.
