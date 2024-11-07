<?php

namespace HaiderJabbar\LaravelSolr\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use HaiderJabbar\LaravelSolr\Schema\SolrSchemaBuilder;

class UpdateSolrFields extends Command
{
    protected $signature = 'solr:update-fields {name}
                            {--parent= : Parent core name}';

    protected $description = 'Update an existing Solr core\'s fields with optional parent core';

    public function handle()
    {
        $coreName = $this->argument('name');
        $parentCore = $this->option('parent');

        $schema = new SolrSchemaBuilder();

        $this->info("Define the fields to update for your Solr core. Type 'done' when finished.");

        while (true) {
            $fieldName = $this->ask("Enter field name to update (or 'done' to finish)");

            if (strtolower($fieldName) === 'done') {
                break;
            }

            // Ask for field properties to update
            $fieldType = $this->ask("Enter new field type for '$fieldName' (or leave empty to keep unchanged)");
            $required = $this->confirm("Is '$fieldName' required?", false);
            $indexed = $this->confirm("Should '$fieldName' be indexed?", true);
            $stored = $this->confirm("Should '$fieldName' be stored?", true);
            $multiValued = $this->confirm("Is '$fieldName' multi-valued?", false);

            // Add the field updates to schema
            $schema->name($fieldName)
                ->type($fieldType ?: 'string')  // Default to 'string' if not provided
                ->required($required)
                ->indexed($indexed)
                ->stored($stored)
                ->multiValued($multiValued);

            $this->info("Field '$fieldName' will be updated.");
        }

        // Generate migration file for Solr core
        $migrationName = date('Y_m_d_His')."_update_{$coreName}_solr_fields.php";
        $migrationPath = database_path("migrations/{$migrationName}");

        // Create migration stub
        $stub = $this->getMigrationStub($coreName, $parentCore, $schema);

        // Write the migration to the filesystem
        File::put($migrationPath, $stub);

        $this->info("Migration file for updating fields created: {$migrationPath}");
    }

    protected function getMigrationStub($coreName, $parentCore, $schema)
    {
        $fields = $this->getFieldsDefinition($schema);
        $parent = $parentCore ? "'$parentCore'" : 'null';

        return <<<EOT
<?php

use Illuminate\\Database\\Migrations\\Migration;
use HaiderJabbar\\LaravelSolr\\Services\\FieldsSolrService;
use HaiderJabbar\\LaravelSolr\\Schema\\SolrSchemaBuilder;
use HaiderJabbar\\LaravelSolr\\Services\\CoreSolrService;

return new class extends Migration
{
    protected \$FieldsSolrService;

    protected \$schema;

    public function __construct()
    {
        \$this->FieldsSolrService = new FieldsSolrService();
        \$this->schema = new SolrSchemaBuilder();
    }

    public function up()
    {
        {$fields}

        // Update the fields in the Solr core
        \$this->FieldsSolrService->updateFieldsInCore('{$coreName}', \$this->schema->getFields());
    }

    public function down()
    {
        // Optionally rollback the field updates if necessary
        \$this->FieldsSolrService->rollbackFields('{$coreName}', \$this->schema->getFields());
    }
};
EOT;
    }

    protected function getFieldsDefinition($schema)
    {
        $fieldsDefinition = '';
        foreach ($schema->getFields() as $field) {
            $fieldsDefinition .= "\$this->schema->name('{$field['name']}')->type('{$field['type']}')";
            if ($field['required']) $fieldsDefinition .= "->required()";
            if ($field['indexed']) $fieldsDefinition .= "->indexed()";
            if ($field['stored']) $fieldsDefinition .= "->stored()";
            if ($field['multiValued']) $fieldsDefinition .= "->multiValued()";
            $fieldsDefinition .= ";\n        ";
        }
        return $fieldsDefinition;
    }
}
