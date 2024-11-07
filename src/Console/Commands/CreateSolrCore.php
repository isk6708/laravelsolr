<?php

namespace HaiderJabbar\LaravelSolr\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class CreateSolrCore extends Command
{
    protected $signature = 'solr:create-core {name}';
    protected $description = 'Create a new Solr core and a migration file';

    public function handle()
    {
        $coreName = $this->argument('name');

        // Generate migration file for Solr core
        $migrationName = date('Y_m_d_His')."_create_{$coreName}_solr_core.php";
        $migrationPath = database_path("migrations/{$migrationName}");

        $stub = $this->getMigrationStub($coreName);

        File::put($migrationPath, $stub);

        $output = "Migration file created: {$migrationPath}";
        $this->info($output);

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
        // Logic to create a Solr core
        \$this->coreSolrService->createCore('{$coreName}');
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
