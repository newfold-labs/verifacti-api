# Changelog

All notable changes to this project will be documented in this file.

## [0.3.0] - 2026-10-09

### Added

- Code lists under `Bluehost\VerifactiApi\Enum` (`InvoiceType`, `RectificationType`, `TaxType`,
  `OperationQualification`, `ExemptionCause`, `RegimeKey`, `IdType`, `PreviousRejection`), each
  citing its Verifacti documentation source.
- Line field `impuesto` (`InvoiceLineBuilder::withTaxType()`) for IGIC (`03`) and IPSI (`02`) invoices.
- Line fields `tipo_recargo_equivalencia` / `cuota_recargo_equivalencia`
  (`InvoiceLineBuilder::withEquivalenceSurcharge()`) and `cuota_recargo_rectificada`.
- `Support\Amount` for exact cent arithmetic and 2-decimal formatting.
- Recipient getters on invoice requests (`getNif()`, `getOtherIdentifier()`, `getRecipientName()`,
  `getSpecialData()`, `getIncidentCode()`).
- `InvoiceOperationResponse::getVerificationUrl()`, `getHash()`, `isIdempotentReplay()`;
  `StatusResponse::getVerificationUrl()`, `getQrCodeBase64()`, `getErrorCode()`,
  `getErrorMessage()`, `isAccepted()` and status constants; `ApiException::getErrorCode()`.
- Bounded retries with exponential backoff (`Service\RetryPolicy`, config `max_retries`,
  default 2) for timeouts and HTTP 409/429/5xx, applied only to GET or idempotency-keyed requests.
- Optional `Idempotency-Key` for `modifyInvoice()` and `cancelInvoice()`.
- Validation rules for corrective invoices, F2/R5 recipient ban, recipient requirements, enum
  values, amount formats, line/total and quota/rate consistency, and the F2 3000 EUR limit.
- Tests built from the Verifacti documentation examples (`tests/fixtures/docs`) and a fake transport.

### Changed

- **BREAKING:** `ClientInterface::modifyInvoice()` and `cancelInvoice()` gained an optional
  `$idempotencyKey` argument; custom implementations must add it.
- **BREAKING:** stricter validation may reject payloads that 0.2.0 sent (and the API then rejected).
- Exception messages built from API responses are now redacted (bearer tokens, keys) and truncated.

## [0.2.0] - 2026-07-16

### Changed

- **BREAKING:** Minimum PHP version raised from 7.4 to 8.0.
- Modernized codebase with PHP 8.0 features: constructor property promotion, native `mixed` type, `match` expressions, `str_contains` / `str_starts_with`, and short array syntax.
- Added complete PHPDoc coverage across all public API surfaces.

### Security

- Explicit TLS verification (`CURLOPT_SSL_VERIFYPEER`, `CURLOPT_SSL_VERIFYHOST`) in cURL transport.
- Response body truncation in exception messages to reduce sensitive data exposure in logs.
- Query parameter validation in `RecordStatusLookupRequest`.
