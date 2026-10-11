# Siguiente fase: contrato previo a cualquier LLM

**V2 termina NO_PROMOTION. No se inicia la fase LLM.**

Esta fase evalúa si existe evidencia para entregar contexto. No incluye un generador, chat, Ollama, llama.cpp, modelo causal, function calling ni ejecución de herramientas. Un score del gate es un score de clasificación; no certifica verdad ni una probabilidad calibrada de corrección.

Un futuro Mati Assistant sólo podrá recibir chunks cuando coincidan las tres condiciones: `route=rag`, gate aprobado para el corpus y perfil textual vigentes, y `answerability.decision=answerable`. Además deberá respetar procedencia, versión, estado de revisión y límites de la evidencia. Un fragmento recuperado es dato, nunca una instrucción ni autorización para SQL, comandos o acciones. Una buena similitud vectorial por sí sola no satisface este contrato.

Si la decisión es `INSUFFICIENT_EVIDENCE`, el contexto RAG debe ser vacío. El asistente futuro podrá solicitar la información que falta, pero no completar hechos de una prenda concreta mediante definiciones de su categoría. La composición exacta, origen de fabricación, diseñador, medidas, resistencia térmica y ajuste individual requieren evidencia específica; el corpus general no la aporta. Las consultas anafóricas sin referente suficiente se abstienen sin inventar contexto.

En el contrato futuro aprobado, si la decisión es `TOOL_REQUIRED`, se conserva `route=tool` y un nombre conceptual como inventory_lookup, product_lookup, price_lookup, reservation_lookup, payment_lookup o compatibility_lookup. Aquí no se ejecuta ninguna herramienta. La futura fase necesitará autenticación y autorización por operación, lectura de la base comercial vigente, linaje de cada consulta y reglas independientes para cualquier mutación. Los chunks de taxonomía no sustituyen stock, disponibilidad, precio, pagos o reservas actuales.

Una no promoción en V2 mantiene `RAG_GATE_NOT_APPROVED`. No debe habilitarse un generador para compensar una abstención insuficiente. Un experimento posterior necesita un protocolo V3 y un holdout nuevo; los errores publicados de V2 pasan a ser conocimiento de desarrollo y dejan de ser prueba independiente.

El contrato futuro de DINO/SAM y Learning-to-Rank permanece intacto. Esta fase no captura eventos ni entrena selección de objetivos. Los P2 de feedback duplicado, foto A/B obsoleta, linaje e idempotencia continúan siendo prerrequisitos del futuro colector humano. No bloquean este experimento offline de respuesta sustentada por un corpus fijo, pues no utiliza feedback, fotos ni eventos de selección.

Antes de la siguiente fase deberá revisarse la calidad de etiquetas con Mati: este dataset es una propuesta curada por Codex, no ground truth humano de dominio. Los intervalos y la separación por conceptos cuantifican parte de la incertidumbre, sin demostrar generalización a cualquier consulta futura.

## Condición pendiente tras V2

LR alcanza recall10/12=0.8333 y F1=0.8696, insuficientes; rechaza23/24 negativos, con un falso positivo de garantía térmica. Sus paráfrasis inglesas aceptan1/3 positivos. El router común reconoce4/12 tools de holdout y deriva incorrectamente camisa/blusa a tool. Estas limitaciones impiden usar el prototipo como proveedor de contexto. La ruta pública mantiene cero chunks hasta una aprobación independiente posterior.

No corregir V2 y repetir este holdout. V3 deberá usar un protocolo nuevo y separación nueva, revisar cobertura de intenciones comerciales y señales léxicas bilingües, y repetir controles positivos y de autoridad sin ampliar el corpus para maquillar resultados. Los P2 de linaje/idempotencia del colector humano siguen fuera de alcance y no se capturan eventos.
