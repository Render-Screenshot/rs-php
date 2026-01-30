#!/bin/bash
# Custom secret detection script for RenderScreenshot PHP SDK
# This runs as a pre-commit hook to catch common secret patterns

set -e

RED='\033[0;31m'
GREEN='\033[0;32m'
NC='\033[0m' # No Color

FOUND_SECRETS=0

echo "Checking for potential secrets..."

# Files to check - exclude vendor directory
FILES=$(find . -type f \( -name '*.php' -o -name '*.json' -o -name '*.yml' -o -name '*.yaml' -o -name '*.md' -o -name '.env*' \) ! -path './vendor/*' 2>/dev/null || true)

if [ -z "$FILES" ]; then
    echo -e "${GREEN}No files to check.${NC}"
    exit 0
fi

# Check for RenderScreenshot live/secret keys (not placeholder xxxxx)
check_rs_keys() {
    # Match rs_live_ or rs_secret_ followed by 20+ alphanumeric chars (real keys)
    # But exclude rs_*_xxxxx patterns (placeholders)
    if grep -rE 'rs_(live|secret)_[a-zA-Z0-9]{20,}' --include='*.php' --include='*.json' --include='*.yml' --include='*.yaml' --include='*.md' . 2>/dev/null | grep -v 'vendor/' | grep -vE 'rs_(live|test|secret|pub)_x+' | grep -v '.secrets.baseline'; then
        echo -e "${RED}POTENTIAL SECRET: RenderScreenshot API key found${NC}"
        return 1
    fi
    return 0
}

# Check for AWS keys
check_aws_keys() {
    if grep -rE 'AKIA[0-9A-Z]{16}' --include='*.php' --include='*.json' --include='*.yml' --include='*.yaml' . 2>/dev/null | grep -v 'vendor/'; then
        echo -e "${RED}POTENTIAL SECRET: AWS Access Key ID found${NC}"
        return 1
    fi
    return 0
}

# Check for GitHub tokens
check_github_tokens() {
    if grep -rE 'gh[pousr]_[A-Za-z0-9_]{36,}' --include='*.php' --include='*.json' --include='*.yml' --include='*.yaml' . 2>/dev/null | grep -v 'vendor/'; then
        echo -e "${RED}POTENTIAL SECRET: GitHub token found${NC}"
        return 1
    fi
    return 0
}

# Check for OpenAI keys
check_openai_keys() {
    if grep -rE 'sk-[a-zA-Z0-9]{48}' --include='*.php' --include='*.json' --include='*.yml' --include='*.yaml' . 2>/dev/null | grep -v 'vendor/'; then
        echo -e "${RED}POTENTIAL SECRET: OpenAI API key found${NC}"
        return 1
    fi
    return 0
}

# Check for Stripe live keys
check_stripe_keys() {
    if grep -rE 'sk_live_[a-zA-Z0-9]{24,}' --include='*.php' --include='*.json' --include='*.yml' --include='*.yaml' . 2>/dev/null | grep -v 'vendor/'; then
        echo -e "${RED}POTENTIAL SECRET: Stripe secret key found${NC}"
        return 1
    fi
    return 0
}

# Check for private keys
check_private_keys() {
    if grep -rE '-----BEGIN (RSA |DSA |EC |OPENSSH |PGP )?PRIVATE KEY-----' --include='*.php' --include='*.json' --include='*.yml' --include='*.yaml' --include='*.pem' --include='*.key' . 2>/dev/null | grep -v 'vendor/'; then
        echo -e "${RED}POTENTIAL SECRET: Private key found${NC}"
        return 1
    fi
    return 0
}

# Check for generic high-entropy strings that look like secrets
check_password_assignments() {
    # Look for password = "actual_value" patterns (not variables)
    if grep -rE "password\s*[=:]\s*['\"][^'\"\$]{12,}['\"]" --include='*.php' --include='*.json' --include='*.yml' . 2>/dev/null | grep -v 'vendor/' | grep -v 'example' | grep -v 'placeholder' | grep -v 'your_' | grep -v 'YOUR_'; then
        echo -e "${RED}POTENTIAL SECRET: Hardcoded password found${NC}"
        return 1
    fi
    return 0
}

# Run all checks
ERRORS=0

check_rs_keys || ERRORS=1
check_aws_keys || ERRORS=1
check_github_tokens || ERRORS=1
check_openai_keys || ERRORS=1
check_stripe_keys || ERRORS=1
check_private_keys || ERRORS=1
check_password_assignments || ERRORS=1

if [ $ERRORS -eq 1 ]; then
    echo ""
    echo -e "${RED}Secret detection failed!${NC}"
    echo "Please review the output above and remove any secrets."
    echo "If these are false positives, update .hooks/check-secrets.sh"
    exit 1
else
    echo -e "${GREEN}No secrets detected.${NC}"
    exit 0
fi
