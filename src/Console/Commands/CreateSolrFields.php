<?php

namespace HaiderJabbar\LaravelSolr\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use HaiderJabbar\LaravelSolr\Schema\SolrSchemaBuilder;
use Symfony\Component\Console\Output\ConsoleOutput;

class CreateSolrFields extends Command
{
    protected $signature = 'solr:create-fields {name}
                            {-- --parent= : Parent core name}';

    protected $description = 'Create a new Solr fields with optional parent and fields';

    public function handle()
    {
        $coreName = $this->argument('name');
        $parentCore = $this->option('parent');

        $schema = new SolrSchemaBuilder();

        $this->info("Define the fields for your Solr fields. Type 'done' when finished.");

        while (true) {
            $fieldName = $this->ask("Enter field name (or 'done' to finish)");

            if (strtolower($fieldName) === 'done') {
                break;
            }

            $fieldType = $this->ask("Enter field type for '$fieldName'");
            $required = $this->confirm("Is '$fieldName' required?", false);
            $indexed = $this->confirm("Should '$fieldName' be indexed?", true);
            $stored = $this->confirm("Should '$fieldName' be stored?", true);
            $multiValued = $this->confirm("Is '$fieldName' multi-valued?", false);

            $schema->name($fieldName)
                ->type($fieldType)
                ->required($required)
                ->indexed($indexed)
                ->stored($stored)
                ->multiValued($multiValued);

            $this->info("Field '$fieldName' added.");
        }

        // Generate migration file for Solr fields
        $migrationName = date('Y_m_d_His')."_create_{$coreName}_solr_core.php";
        $migrationPath = database_path("migrations/{$migrationName}");

        $stub = $this->getMigrationStub($coreName, $parentCore, $schema);

        File::put($migrationPath, $stub);

        $this->info("Migration file created: {$migrationPath}");
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
use Symfony\\Component\\Console\\Output\\ConsoleOutput;
return new class extends Migration
{
    protected \$FieldsSolrService;


    protected \$schema;

    public function __construct()
    {
        \$this->FieldsSolrService = new FieldsSolrService(new ConsoleOutput);

        \$this->schema = new SolrSchemaBuilder();;
    }

    public function up()
    {


        {$fields}

        \$this->FieldsSolrService->addFieldsToCore('{$coreName}', \$this->schema->getFields());
    }

    public function down()
    {

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
