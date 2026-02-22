# File Namespace

The `App\File` namespace provides a thin abstraction over PHP's native filesystem functions, making file I/O easy to mock in tests.

## FileProvider

`FileProvider` exposes two methods:

| Method | Description |
|--------|-------------|
| `getContents(string $filePath): string` | Reads and returns the contents of a file. Throws `FileNotFoundException` if the file does not exist. |
| `saveContents(string $filePath, string $content): void` | Writes content to a file. Throws `RuntimeException` on failure. |

### Usage within the Application

`FileProvider` is primarily consumed by `GoogleClient` to persist and retrieve the OAuth token stored at `var/google-token.json`. By routing all file access through this class, tests can substitute a mock `FileProvider` to avoid touching the real filesystem.