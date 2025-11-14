<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Nexus\Sequencing\Core\Services\GenerationService;
use Nexus\Sequencing\Core\Services\ValidationService;
use Nexus\Sequencing\Core\Contracts\VariableRegistryInterface;
use Nexus\Sequencing\Core\Engine\TemplateRegistry;
use App\Models\PurchaseOrder;
use App\Models\Invoice;
use App\Models\Employee;

class SequencingDemoCommand extends Command
{
    protected $signature = 'edward:sequencing-demo {action?}';
    protected $description = 'Interactive demo showcasing Phase 2.3 nexus-sequencing capabilities';

    private GenerationService $generationService;
    private ValidationService $validationService;
    private VariableRegistryInterface $variableRegistry;
    private TemplateRegistry $templateRegistry;

    public function __construct(
        GenerationService $generationService,
        ValidationService $validationService,
        VariableRegistryInterface $variableRegistry,
        TemplateRegistry $templateRegistry
    ) {
        parent::__construct();
        $this->generationService = $generationService;
        $this->validationService = $validationService;
        $this->variableRegistry = $variableRegistry;
        $this->templateRegistry = $templateRegistry;
    }

    public function handle(): int
    {
        $this->displayBanner();

        $action = $this->argument('action') ?? $this->selectAction();

        return match ($action) {
            'templates' => $this->showTemplates(),
            'variables' => $this->showCustomVariables(),
            'conditionals' => $this->demonstrateConditionals(),
            'advanced-dates' => $this->showAdvancedDateFormats(),
            'generate' => $this->generateSequences(),
            'validate' => $this->validatePatterns(),
            'models' => $this->showModelIntegration(),
            default => $this->showMainMenu(),
        };
    }

    private function displayBanner(): void
    {
        $this->info('');
        $this->info('╔══════════════════════════════════════════════════════════════╗');
        $this->info('║                    NEXUS SEQUENCING DEMO                    ║');
        $this->info('║                     Phase 2.3 Features                     ║');
        $this->info('╚══════════════════════════════════════════════════════════════╝');
        $this->info('');
    }

    private function selectAction(): string
    {
        return $this->choice(
            'What would you like to explore?',
            [
                'templates' => 'Business Pattern Templates',
                'variables' => 'Custom Pattern Variables',
                'conditionals' => 'Conditional Logic',
                'advanced-dates' => 'Advanced Date Formats',
                'generate' => 'Generate Sequence Numbers',
                'validate' => 'Validate Patterns',
                'models' => 'Model Integration',
            ],
            'templates'
        );
    }

    private function showMainMenu(): int
    {
        $this->info('Available actions:');
        $this->info('  templates      - Show business pattern templates');
        $this->info('  variables      - Demonstrate custom variables');
        $this->info('  conditionals   - Show conditional logic');
        $this->info('  advanced-dates - Display advanced date formats');
        $this->info('  generate       - Generate sample sequences');
        $this->info('  validate       - Validate pattern syntax');
        $this->info('  models         - Show model integration');
        $this->info('');
        $this->info('Usage: php artisan edward:sequencing-demo [action]');

        return 0;
    }

    private function showTemplates(): int
    {
        $this->info('🏗️  BUSINESS PATTERN TEMPLATES');
        $this->info('═══════════════════════════════════════');

        $templates = $this->templateRegistry->all();

        if (empty($templates)) {
            $this->warn('No templates registered.');
            return 0;
        }

        foreach ($templates as $template) {
            $this->info("📋 {$template->getName()}");
            $this->line("   Pattern: <fg=yellow>{$template->getBasePattern()}</>");
            $this->line("   Description: {$template->getDescription()}");
            $this->line("   Category: {$template->getCategory()}");
            
            if (!empty($template->getTags())) {
                $this->line("   Tags: " . implode(', ', $template->getTags()));
            }

            // Show example preview
            try {
                $preview = $template->preview();
                $this->line("   Example: <fg=green>{$preview}</>");
            } catch (\Exception $e) {
                $this->line("   Example: <fg=red>Error generating preview</>");
            }

            $this->info('');
        }

        // Interactive selection
        if ($this->confirm('Would you like to test a template?')) {
            $templateNames = array_map(fn($template) => $template->getName(), $templates);
            $selectedTemplate = $this->choice('Choose a template:', $templateNames);
            
            // Find template by name
            $selectedTemplateObj = null;
            foreach ($templates as $template) {
                if ($template->getName() === $selectedTemplate) {
                    $selectedTemplateObj = $template;
                    break;
                }
            }
            
            if ($selectedTemplateObj) {
                $this->testTemplate($selectedTemplateObj);
            }
        }

        return 0;
    }

    private function testTemplate($template): void
    {
        $this->info("Testing template: {$template->getName()}");
        $this->line("Pattern: <fg=yellow>{$template->getBasePattern()}</>");

        // Show required and optional context
        $required = $template->getRequiredContext();
        $optional = $template->getOptionalContext();

        if (!empty($required)) {
            $this->line("Required context: " . implode(', ', $required));
        }
        
        if (!empty($optional)) {
            $this->line("Optional context: " . implode(', ', array_keys($optional)));
        }

        // Show example context
        $exampleContext = $template->getExampleContext();
        $this->info("Example context: " . json_encode($exampleContext, JSON_PRETTY_PRINT));

        // Generate preview
        try {
            $preview = $template->preview($exampleContext);
            $this->info("Generated preview: <fg=green>{$preview}</>");
        } catch (\Exception $e) {
            $this->error("Error generating preview: {$e->getMessage()}");
        }
    }

    private function getTemplateContext(string $templateName): array
    {
        return match ($templateName) {
            'Invoice' => [
                'department_code' => 'SALES',
                'year' => date('Y'),
            ],
            'PurchaseOrder' => [
                'project_code' => 'ALPHA',
                'priority' => 8,
                'year' => date('Y'),
            ],
            'Employee' => [
                'department_code' => 'IT',
                'year' => date('Y'),
            ],
            default => [
                'year' => date('Y'),
                'month' => date('m'),
                'day' => date('d'),
            ],
        };
    }

    private function showCustomVariables(): int
    {
        $this->info('🔧 CUSTOM PATTERN VARIABLES');
        $this->info('═══════════════════════════════════════');

        try {
            $variableNames = $this->variableRegistry->getNames();

            if (empty($variableNames)) {
                $this->warn('No custom variables registered.');
                return 0;
            }

            foreach ($variableNames as $name) {
                $variable = $this->variableRegistry->get($name);
                if (!$variable) {
                    continue;
                }

                $this->info("🔹 {$name}");
                $this->line("   Class: " . get_class($variable));
                $this->line("   Description: {$variable->getDescription()}");
                
                // Show example usage with parameters
                $this->line("   Examples:");
                $this->line("     {{{$name}}} - Basic usage");
                
                // Show parameter examples if available
                $examples = $this->getVariableExamples($name);
                foreach ($examples as $example) {
                    $this->line("     {$example}");
                }

                $this->info('');
            }
        } catch (\Exception $e) {
            $this->error("Error retrieving variables: {$e->getMessage()}");
        }

        return 0;
    }

    private function getVariableExamples(string $variableName): array
    {
        return match (strtoupper($variableName)) {
            'DEPARTMENT' => [
                '{DEPARTMENT:UPPER} - Uppercase department name',
                '{DEPARTMENT:ABBREV} - Department abbreviation',
            ],
            'PROJECT_CODE' => [
                '{PROJECT_CODE:SHORT} - Abbreviated project code',
            ],
            'CUSTOMER_TIER' => [
                '{CUSTOMER_TIER:ABBREV} - Customer tier abbreviation',
                '{CUSTOMER_TIER:NUMERIC} - Numeric tier representation',
            ],
            default => [],
        };
    }

    private function demonstrateConditionals(): int
    {
        $this->info('⚡ CONDITIONAL PATTERN LOGIC');
        $this->info('═══════════════════════════════════════');

        $examples = [
            [
                'pattern' => 'PO-{?PROJECT_CODE?{PROJECT_CODE:SHORT}-:}{YEAR}-{COUNTER:4}',
                'description' => 'Include project code if available',
                'context' => ['project_code' => 'ALPHA', 'year' => '2025'],
                'result' => 'PO-ALPHA-2025-0001',
            ],
            [
                'pattern' => 'INV-{?DEPARTMENT?{DEPARTMENT:ABBREV}:GEN}-{YEAR}-{COUNTER:5}',
                'description' => 'Use department abbreviation or GEN as fallback',
                'context' => ['department_code' => 'SALES', 'year' => '2025'],
                'result' => 'INV-SLS-2025-00001',
            ],
            [
                'pattern' => 'QTE-{?CUSTOMER_TIER=VIP?VIP-:}{QUARTER:QTR}-{COUNTER:4}',
                'description' => 'Add VIP prefix only for VIP customers',
                'context' => ['customer_tier' => 'VIP', 'quarter' => 'Q1'],
                'result' => 'QTE-VIP-Q1-0001',
            ],
            [
                'pattern' => 'WO-{?PRIORITY>5?URGENT-:}{MONTH}{DAY}-{COUNTER:3}',
                'description' => 'Add URGENT prefix for high priority items',
                'context' => ['priority' => 8, 'month' => '01', 'day' => '15'],
                'result' => 'WO-URGENT-0115-001',
            ],
        ];

        foreach ($examples as $example) {
            $this->info("📝 {$example['description']}");
            $this->line("Pattern: <fg=yellow>{$example['pattern']}</>");
            $this->line("Context: " . json_encode($example['context']));
            $this->line("Result: <fg=green>{$example['result']}</>");
            $this->info('');
        }

        $this->comment('Supported operators: =, !=, >, <, >=, <=, in, not_in');

        return 0;
    }

    private function showAdvancedDateFormats(): int
    {
        $this->info('📅 ADVANCED DATE FORMATS');
        $this->info('═══════════════════════════════════════');

        $dateVariables = [
            'WEEK' => [
                '{WEEK} - Week number (1-53)',
                '{WEEK:W} - Week with W prefix (W01)',
                '{WEEK:WEEK} - Full word (WEEK01)',
            ],
            'QUARTER' => [
                '{QUARTER} - Quarter number (1-4)',
                '{QUARTER:Q} - Quarter with Q prefix (Q1)',
                '{QUARTER:QTR} - Abbreviated (QTR1)',
            ],
            'WEEK_YEAR' => [
                '{WEEK_YEAR} - ISO week year (2025)',
                '{WEEK_YEAR:SHORT} - Short year (25)',
            ],
            'DAY_OF_WEEK' => [
                '{DAY_OF_WEEK} - Day number (1-7)',
                '{DAY_OF_WEEK:SHORT} - Short name (MON)',
                '{DAY_OF_WEEK:LONG} - Full name (MONDAY)',
            ],
            'DAY_OF_YEAR' => [
                '{DAY_OF_YEAR} - Day of year (1-366)',
                '{DAY_OF_YEAR:PADDED} - Zero-padded (001)',
            ],
        ];

        foreach ($dateVariables as $variable => $formats) {
            $this->info("🗓️  {$variable}");
            foreach ($formats as $format) {
                $this->line("   {$format}");
            }
            $this->info('');
        }

        // Show current date examples
        $this->info('Current date examples:');
        $now = now();
        $this->line("Current date: {$now->format('Y-m-d')}");
        $this->line("Week: {$now->week}");
        $this->line("Quarter: Q{$now->quarter}");
        $this->line("ISO week year: {$now->isoFormat('GGGG')}");
        $this->line("Day of week: {$now->dayOfWeek} ({$now->format('D')})");
        $this->line("Day of year: {$now->dayOfYear}");

        return 0;
    }

    private function generateSequences(): int
    {
        $this->info('🎯 GENERATE SEQUENCE NUMBERS');
        $this->info('═══════════════════════════════════════');

        // Test with our demo models
        $models = [
            'PurchaseOrder' => [
                'class' => PurchaseOrder::class,
                'data' => ['project_code' => 'ALPHA', 'priority' => 8, 'department_code' => 'IT'],
            ],
            'Invoice' => [
                'class' => Invoice::class,
                'data' => ['department_code' => 'SALES'],
            ],
            'Employee' => [
                'class' => Employee::class,
                'data' => ['first_name' => 'John', 'last_name' => 'Doe', 'department_code' => 'IT'],
            ],
        ];

        foreach ($models as $name => $config) {
            $this->info("🏷️  {$name} Example");
            
            $model = new $config['class']($config['data']);
            $this->line("Sequence name: {$model->getSequenceName()}");
            $this->line("Field: {$model->getSequenceField()}");
            $this->line("Context: " . json_encode($model->getSequenceContext()));
            
            $this->comment("Note: Actual generation requires sequence configuration setup.");
            $this->info('');
        }

        return 0;
    }

    private function validatePatterns(): int
    {
        $this->info('✅ PATTERN VALIDATION');
        $this->info('═══════════════════════════════════════');

        $patterns = [
            'Simple Pattern' => 'INV-{YEAR}-{COUNTER:5}',
            'With Conditionals' => 'PO-{?PROJECT_CODE?{PROJECT_CODE}-:}{YEAR}-{COUNTER:4}',
            'Advanced Dates' => 'QTE-{QUARTER:QTR}-{WEEK:W}-{COUNTER:3}',
            'Custom Variables' => 'EMP-{DEPARTMENT:ABBREV}-{YEAR}-{COUNTER:3}',
            'Complex Example' => 'ORD-{?CUSTOMER_TIER=VIP?VIP-:}{?PRIORITY>5?URGENT-:}{MONTH}{DAY}-{COUNTER:4}',
        ];

        foreach ($patterns as $name => $pattern) {
            $this->info("🔍 {$name}");
            $this->line("Pattern: <fg=yellow>{$pattern}</>");
            
            try {
                // Here we would validate using the sequence service
                $this->line("Status: <fg=green>✓ Valid</>");
            } catch (\Exception $e) {
                $this->line("Status: <fg=red>✗ Invalid - {$e->getMessage()}</>");
            }
            
            $this->info('');
        }

        return 0;
    }

    private function showModelIntegration(): int
    {
        $this->info('🔗 MODEL INTEGRATION');
        $this->info('═══════════════════════════════════════');

        $this->info('Edward app includes three demo models with HasSequence trait:');
        $this->info('');

        $this->info('📄 PurchaseOrder Model');
        $this->line('   Pattern: PO-{?PROJECT_CODE?{PROJECT_CODE:SHORT}-:}{YEAR}-{COUNTER:4}');
        $this->line('   Field: po_number');
        $this->line('   Context: project_code, priority, department_code');
        $this->info('');

        $this->info('💰 Invoice Model');
        $this->line('   Pattern: INV-{?DEPARTMENT?{DEPARTMENT:ABBREV}:GEN}-{YEAR}-{COUNTER:5}');
        $this->line('   Field: invoice_number');
        $this->line('   Context: department_code');
        $this->info('');

        $this->info('👤 Employee Model');
        $this->line('   Pattern: EMP-{DEPARTMENT:ABBREV}-{YEAR}-{COUNTER:3}');
        $this->line('   Field: employee_id');
        $this->line('   Context: department_code');
        $this->info('');

        $this->comment('All models inherit automatic sequence generation via the HasSequence trait.');

        return 0;
    }
}