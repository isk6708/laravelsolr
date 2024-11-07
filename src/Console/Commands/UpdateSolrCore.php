<?php

namespace HaiderJabbar\LaravelSolr\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class UpdateSolrCore extends Command
{
    // Modify the signature to accept both the old core name and the new core name
    protected $signature = 'solr:update-core {oldName} {newName}';
    protected $description = 'Update a Solr core with a new name and create a migration file';

    public function handle()
    {
        // Get the old and new core names from the command arguments
        $oldCoreName = $this->argument('oldName');
        $newCoreName = $this->argument('newName');

        // Generate migration file for Solr core update
        $migrationName = date('Y_m_d_His')."_update_{$oldCoreName}_to_{$newCoreName}_solr_core.php";
        $migrationPath = database_path("migrations/{$migrationName}");

        // Get the stub with the old and new core names
        $stub = $this->getMigrationStub($oldCoreName, $newCoreName);

        // Delete the migration file
        File::put($migrationPath, $stub);

        // Output message
        $this->info("Migration file created for Solr core update: {$migrationPath}");
        return 0;
    }
////////////////////////////////////////////////////////////////////////////////////////////////
    public function getMigrationStub($oldCoreName,$newCoreName)
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
         \$this->coreSolrService->updateCore('{$oldCoreName}', '{$newCoreName}');
    }

    public function down()
    {
        // Logic to drop a Solr core
       \$this->coreSolrService->updateCore('{$newCoreName}', '{$oldCoreName}');
    }
};
EOT;
    }
////////////////////////////////////////////////////////////////////////////////////////////////
}
