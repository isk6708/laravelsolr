<?php

namespace HaiderJabbar\LaravelSolr\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use HaiderJabbar\LaravelSolr\Schema\SolrSchemaBuilder;

class DeleteSolrFields extends Command
{
    protected $signature = 'solr:delete-fields {coreName}';
    protected $description = 'Delete fields from a Solr core';

    public function handle()
    {
        $coreName = $this->argument('coreName');

        $this->info("Define the fields you want to delete from the '$coreName' core. Type 'done' when finished.");

        $fieldsToDelete = [];

        while (true) {
            $fieldName = $this->ask("Enter field name to delete (or 'done' to finish)");

            if (strtolower($fieldName) === 'done') {
                break;
            }

            $fieldsToDelete[] = $fieldName;
            $this->info("Field '$fieldName' will be deleted.");
        }

        // Generate migration file for deleting fields from Solr core
        $migrationName = date('Y_m_d_His') . "_delete_fields_from_{$coreName}_solr_core.php";
        $migrationPath = database_path("migrations/{$migrationName}");

        $stub = $this->getMigrationStub($coreName, $fieldsToDelete);
        File::put($migrationPath, $stub);

        $this->info("Migration file created: {$migrationPath}");
    }

    protected function getMigrationStub($coreName, $fields)
    {
        // Prepare the fields list as a string for the migration file
        $fieldsList = implode("', '", $fields);

        return <<<EOT
<?php

use Illuminate\\Database\\Migrations\\Migration;
use HaiderJabbar\\LaravelSolr\\Services\\FieldsSolrService;

return new class extends Migration
{
    protected \$FieldsSolrService;

    public function __construct()
    {
        \$this->FieldsSolrService = new FieldsSolrService();
    }

    public function up()
    {
        try {
            // Corrected to use \$this->FieldsSolrService
            \$this->FieldsSolrService->deleteFieldsFromCore('{$coreName}', ['{$fieldsList}']);

        } catch (Exception \$e) {

            throw \$e; // Re-throw the exception to halt the migration
        }
    }

    public function down()
    {
        // Optionally implement a rollback to re-add fields if needed
        // You may want to store the field definitions before deleting them
    }
};
EOT;
    }

}
