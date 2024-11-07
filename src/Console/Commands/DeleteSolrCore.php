<?php

namespace HaiderJabbar\LaravelSolr\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
class DeleteSolrCore extends Command
{
    protected $signature = 'solr:delete-core {name}';
    protected $name = 'solr:delete-core {name}';
    protected $description = 'Delete  Solr core and a migration file';

    public function handle()
    {
        $coreName = $this->argument('name');

        // Generate migration file for Solr core
        $migrationName = date('Y_m_d_His')."_delete_{$coreName}_solr_core.php";
        $migrationPath = database_path("migrations/{$migrationName}");

        $stub = $this->getMigrationStub($coreName);

        File::put($migrationPath, $stub);

        $this->info("Migration file deleted: {$migrationPath}");

        return 0; // Indicate successful execution
    }

    public function getMigrationStub($coreName)
    {
        return <<<EOT
<?php

use Illuminate\\Database\\Migrations\\Migration;
use HaiderJabbar\\LaravelSolr\\Services\\CoreSolrService;

  return new class extends Migration
{
    protected \$coreSolrService;

    public function __construct()
    {
        \$this->coreSolrService = new CoreSolrService();
    }

    public function up()
    {
        // Logic to delete a Solr core
        \$this->coreSolrService->deleteCore('{$coreName}');
    }

    public function down()
    {
        // Logic to drop a Solr core
        \$this->coreSolrService->deleteCore('{$coreName}');
    }
};
EOT;
    }
}
