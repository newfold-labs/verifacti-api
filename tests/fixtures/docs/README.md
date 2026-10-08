# Verifacti documentation fixtures

Request bodies copied verbatim from the Verifacti documentation, retrieved 2026-10-08:

| File | Source |
|---|---|
| `factura_normal.json` | OpenAPI example `factura_normal` (https://www.verifacti.com/en/docs) |
| `factura_con_igic.json` | OpenAPI example `factura_con_igic`; examples page "Factura IGIC" |
| `factura_con_ipsi.json` | OpenAPI example `factura_con_ipsi`; examples page "Factura IPSI" |
| `rectificativa_por_sustitucion.json` | OpenAPI example `rectificativa_por_sustitucin` (one step) |
| `rectificativa_por_sustitucion_dos_pasos.json` | Examples page, "Rectificativa por sustitución en dos pasos" |
| `rectificativa_por_diferencias.json` | OpenAPI example `rectificativa_por_diferencias` |
| `rectificativa_por_diferencias_impago.json` | Examples page, "Rectificativa por diferencias por impago" |
| `factura_simplificada.json` | Examples page, "Factura simplificada" (F2) |
| `factura_de_canje.json` | OpenAPI example `factura_de_canje` (F3) |

Deviations from the published text, all forced by the docs themselves:

- `fecha_expedicion` is `CURRENT_DATE` in the OpenAPI examples and is filled in with
  today's date by the examples page. Tests replace `CURRENT_DATE` with a fixed date.
- The examples page uses the placeholder `"tipo_factura": "Rx"` for every corrective
  invoice, which the API rejects. `R1` is used for the substitution example (as in the
  OpenAPI examples) and `R3` (art. 80.4 LIVA, uncollectable debts) for the
  non-payment example.
