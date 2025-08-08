# Contributing Guidelines

We welcome contributions to the Hubleto Framework! To ensure a smooth collaboration process, please follow these guidelines.

## Coding Standards

We adhere to the PSR-12 coding standard. Before submitting your changes, please ensure your code complies with these standards by running PHP-CS-Fixer:

```bash
./vendor/bin/php-cs-fixer fix src --rules=@PSR12
```

Additionally, we use PHPStan for static analysis. Please ensure your code passes PHPStan checks:

```bash
./vendor/bin/phpstan analyse src
```

## Pull Request Process

1.  **Create a new branch** for your feature or bug fix (e.g., `feature/your-feature-name` or `bugfix/your-bug-name`).
2.  **Fork the repository** and create your branch from `main`.
2.  **Make your changes** and ensure they adhere to the coding standards.
3.  **Write clear, concise commit messages** that explain the purpose of your changes, following the [Conventional Commits specification](https://www.conventionalcommits.org/en/v1.0.0/).

    **Examples:**
    *   `feat: Add user authentication module`
    *   `fix: Correct typo in README`
    *   `docs: Update installation instructions`
    *   `refactor: Improve performance of data processing function`
    *   `test: Add unit tests for new feature`
4.  **Ensure all tests pass** (if applicable, add new tests for new features or bug fixes).
5.  **Submit a pull request** to the `main` branch of the Hubleto Framework repository.

### Pull Request Checklist:

*   [ ] Your code follows the PSR-12 coding standard.
*   [ ] Your code passes PHPStan analysis.
*   [ ] All existing tests pass.
*   [ ] New tests are added for new features or bug fixes.
*   [ ] Your commit messages are clear and descriptive.
*   [ ] Your pull request has a clear title and description.
