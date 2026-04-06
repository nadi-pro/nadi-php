# Security Policy

## Supported Versions

| Version | Supported          |
|---------|--------------------|
| 2.x     | Yes                |
| 1.x     | Security fixes only|
| < 1.0   | No                 |

## Reporting a Vulnerability

**Please do not open public GitHub issues for security vulnerabilities.**

To report a security vulnerability, please email [nasrulhazim.m@gmail.com](mailto:nasrulhazim.m@gmail.com) with:

- A description of the vulnerability
- Steps to reproduce the issue
- Any relevant logs or screenshots
- Your suggested fix (if any)

You can expect:

- **Acknowledgment** within 48-72 hours
- **Status update** within 7 days
- **Fix timeline** communicated once the issue is triaged

We follow a responsible disclosure process: fixes are developed and released before public disclosure.

## Security Considerations

### Data Sensitivity

Nadi captures and transmits application error data including exception messages, stack traces, SQL queries, HTTP request details, and custom content passed to `Entry::make()`. **This data may contain Personally Identifiable Information (PII).**

As the SDK consumer, you are responsible for:

- Sanitizing or redacting PII from `Entry` content before calling `store()`
- Filtering sensitive HTTP headers (e.g., `Authorization`, `Cookie`) from metric/entry data
- Ensuring compliance with your organization's data handling policies (GDPR, HIPAA, SOC2, etc.)

### Transport Security

- **HTTP Transporter**: Uses HTTPS (`https://nadi.pro/api`) by default. Credentials are sent via HTTP headers, never in URL parameters.
- **OpenTelemetry Transporter**: Defaults to `http://localhost:4318` for local development. **Always use HTTPS endpoints in production** to protect telemetry data in transit.
- **TCP Transporter**: Uses plain TCP without encryption. Suitable for internal/loopback networks only. Do not use over untrusted networks.
- **Log Transporter**: Writes unencrypted JSON files to disk. Ensure appropriate filesystem permissions are set.

### Credential Management

- Store `NADI_API_KEY` and `NADI_APP_KEY` in environment variables, never in source code
- Rotate API keys regularly
- Credentials are validated at configuration time and never logged

### Shell Execution

The SDK uses `shell_exec()` in `System` and `PeakLoadSampling` metrics for CPU core detection (`nproc`, `sysctl`). These commands are hardcoded and do not accept user input. If `shell_exec` is disabled in your PHP configuration (`disable_functions`), the SDK gracefully falls back to default values.

### Dependencies

This package depends on well-maintained, actively developed libraries:

- `guzzlehttp/guzzle` - HTTP client
- `ramsey/uuid` - UUID generation (RFC 4122)
- `open-telemetry/*` - OpenTelemetry SDK
- `illuminate/support` - Laravel support utilities

Run `composer audit` regularly to check for known vulnerabilities in dependencies.
