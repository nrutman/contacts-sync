# ⚡ Command Namespace

Console commands registered via Symfony's `autoconfigure` and invoked through `bin/console`. User-facing documentation (options, arguments, examples) lives in the [project README](../../README.md#-usage).

## 📌 Behavioral Notes

- **`sync:run`** processes each configured list sequentially. For each list, removals are applied before additions. Source contacts are merged from Planning Center and in-memory contacts — on email collision, the Planning Center version is kept.
- **`sync:configure`** is a no-op when a valid token already exists unless `--force` is passed.
- **`planning-center:refresh`** validates the list name argument against the configured `$lists` array before making any API calls. It does not accept arbitrary list names.