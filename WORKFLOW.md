# Git Hooks and Workflow Setup

This document explains the git workflow and quality gates implemented in this package.

## 🔒 Branch Protection Strategy

### Main Branch Protection

-   ❌ **No direct pushes to main** - All changes must go through pull requests
-   ✅ **Require pull request reviews** - At least one approval required
-   ✅ **Require status checks** - All CI checks must pass before merge
-   ✅ **Require up-to-date branches** - Must be current with main before merge

### Git Hooks

Local git hooks are automatically installed via composer to ensure quality:

```bash
# Install hooks (automatic after composer install/update)
composer hooks:install

# Manually install if needed
composer hooks:install

# Uninstall hooks
composer hooks:uninstall
```

## 🎯 Automated Workflows

### 1. **Push to Feature Branches** (`dev`, `feature/*`, `bugfix/*`, `hotfix/*`)

**Triggers:** Any push to non-main branches
**Actions:**

-   ✅ **Code Style Auto-Fix**: Automatically runs Pint and commits fixes
-   ✅ **Basic Tests**: Runs test suite to ensure functionality
-   ✅ **Static Analysis**: PHPStan analysis for code quality

### 2. **Pull Request to Main**

**Triggers:** PR opened/updated targeting main branch
**Actions:**

-   🔍 **Security Scan**: Composer audit for vulnerabilities
-   🧪 **Comprehensive Testing**: Full test matrix (PHP 8.1, 8.2, 8.3 × Laravel 10.x)
-   📋 **Code Quality**: Pint style check + PHPStan analysis + Composer validation
-   ⚡ **Performance Tests**: Parallel test execution
-   🔗 **Integration Tests**: Feature tests + Package installation validation
-   📊 **Coverage Report**: Code coverage analysis

### 3. **Git Hooks (Local)**

#### Pre-Commit Hook

**Triggers:** Before each commit
**Actions:**

-   Checks staged PHP files with Pint
-   Prevents commits with style issues
-   Lightweight and fast

#### Pre-Push Hook

**Triggers:** Before each push
**Actions:**

-   Runs full Pint style check
-   **Blocks direct pushes to main branch**
-   Provides helpful guidance for proper workflow

## 💡 Developer Workflow

### Starting New Work

```bash
# Create feature branch
git checkout -b feature/your-feature-name

# Make changes and commit (pre-commit hook runs automatically)
git add .
git commit -m "feat: add new booking adapter"

# Push to feature branch (pre-push hook + CI runs)
git push origin feature/your-feature-name
```

### Code Quality Commands

```bash
# Fix code style issues
composer format

# Check style without fixing
composer format:check

# Run full quality suite (style + analysis + tests)
composer quality

# Fix style and run tests
composer quality:fix

# Run tests with coverage
composer test:coverage
```

### Creating Pull Request

1. **Push your feature branch**
2. **Open PR on GitHub** targeting `main`
3. **Comprehensive validation runs automatically**:
    - Security scan
    - Multi-PHP version testing
    - Code quality analysis
    - Performance tests
    - Integration tests
4. **Request review** once all checks pass ✅
5. **Merge** after approval

## 🚨 Quality Gates

### Commit Level

-   ✅ **Style Check**: Pre-commit hook ensures consistent formatting
-   ✅ **Syntax Check**: Pre-commit validates PHP syntax

### Push Level

-   ✅ **Full Style Validation**: Pre-push hook runs complete Pint check
-   ✅ **Main Branch Protection**: Prevents accidental direct pushes
-   ✅ **Auto Style Fix**: CI automatically fixes and commits style issues

### PR Level

-   ✅ **Multi-Environment Testing**: PHP 8.1, 8.2, 8.3 with different dependencies
-   ✅ **Security Validation**: Dependency vulnerability scanning
-   ✅ **Performance Validation**: Parallel test execution
-   ✅ **Integration Validation**: Real package installation testing
-   ✅ **Code Coverage**: Ensures adequate test coverage

## 🎛️ Configuration

### Git Hooks Configuration (`.simple-git-hooks.json`)

```json
{
    "pre-commit": ".githooks/pre-commit",
    "pre-push": ".githooks/pre-push"
}
```

### Pint Configuration (`pint.json`)

-   Laravel preset with PSR-12 standards
-   Consistent array syntax and spacing
-   Optimized for readability and maintainability

### GitHub Actions

-   **`tests.yml`**: Basic CI for feature branches
-   **`pr-validation.yml`**: Comprehensive PR validation
-   **`style-fix.yml`**: Automatic code style fixing

## 🔧 Troubleshooting

### Hook Issues

```bash
# Reinstall hooks if they're not working
composer hooks:install

# Check hook permissions
ls -la .githooks/
```

### Style Issues

```bash
# Fix all style issues automatically
composer format

# Check what would be fixed without applying
composer format:check
```

### CI Failures

-   Check the GitHub Actions tab for detailed error messages
-   Run `composer quality` locally to reproduce issues
-   Ensure all dependencies are up to date with `composer update`

This setup ensures **maximum code quality** while maintaining **developer productivity** through automation and clear feedback loops.
