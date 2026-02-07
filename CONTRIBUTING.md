# Contributing

Contributions are welcome! Please follow these guidelines:

## Bug Reports

If you discover a bug, please create an issue with a clear description and steps to reproduce.

## Pull Requests

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/my-feature`)
3. Write tests for your changes
4. Ensure all tests pass (`composer test`)
5. Run code formatting (`composer format`)
6. Commit your changes and open a pull request

## Development Setup

```bash
git clone https://github.com/iotron/laravel-state-machine.git
cd laravel-state-machine
composer install
composer test
```

## Code Style

This project uses [Laravel Pint](https://laravel.com/docs/pint) for code formatting. Run `composer format` before submitting a PR.

## Tests

All new features and bug fixes must include tests. Run the test suite with:

```bash
composer test
```
