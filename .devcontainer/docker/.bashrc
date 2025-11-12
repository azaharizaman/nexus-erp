# Bash configuration for Laravel ERP development container

# Color definitions
export CLICOLOR=1
export LSCOLORS=ExFxBxDxCxegedabagacad

# Aliases
alias ll='ls -lah'
alias la='ls -lA'
alias l='ls -CF'
alias artisan='php artisan'
alias composer='composer'
alias pest='./vendor/bin/pest'
alias pint='./vendor/bin/pint'

# Laravel Artisan completion (basic)
alias migrate='artisan migrate'
alias migrate:fresh='artisan migrate:fresh'
alias seed='artisan db:seed'
alias tinker='artisan tinker'
alias serve='artisan serve --host=0.0.0.0 --port=8000'
alias queue='artisan queue:work'
alias cache:clear='artisan cache:clear'
alias config:clear='artisan config:clear'
alias view:clear='artisan view:clear'

# Container information
echo "═══════════════════════════════════════════════════════════════"
echo "  Laravel ERP Development Environment"
echo "═══════════════════════════════════════════════════════════════"
echo ""
echo "📦 Services:"
echo "  • App:        http://localhost:8000"
echo "  • PostgreSQL: localhost:5432 (erp_user/erp_password)"
echo "  • Redis:      localhost:6379"
echo "  • Meilisearch: http://localhost:7700"
echo "  • PgAdmin:    http://localhost:5050 (admin@laravel-erp.local/admin)"
echo "  • MailHog:    http://localhost:8025"
echo "  • Redis CLI:  http://localhost:8081"
echo ""
echo "🚀 Quick Commands:"
echo "  • artisan <command>      - Run Artisan commands"
echo "  • composer install       - Install PHP dependencies"
echo "  • npm install           - Install Node dependencies"
echo "  • pest                  - Run tests with Pest"
echo "  • pint                  - Format code with Pint"
echo "  • migrate:fresh         - Reset database"
echo "  • seed                  - Seed database"
echo "  • tinker                - Interactive shell"
echo "  • serve                 - Start dev server"
echo ""
echo "📚 Documentation:"
echo "  • README.md             - Project overview"
echo "  • CODING_GUIDELINES.md  - Development standards"
echo "  • DOCKER_SETUP.md       - Docker configuration"
echo ""
echo "═══════════════════════════════════════════════════════════════"
echo ""

# Set up helpful environment variables
export APP_DIR=/workspace
export DB_HOST=postgres
export REDIS_HOST=redis
export MEILISEARCH_HOST=http://meilisearch:7700

# Change to app directory
cd /workspace
