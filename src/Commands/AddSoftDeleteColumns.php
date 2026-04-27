<?php

namespace Rabnawazak1\CustomSoftDelete\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;

class AddSoftDeleteColumns extends Command
{
    protected $signature   = 'softdelete:add {table : The table name}';
    protected $description = 'Generate a migration to add custom soft delete columns to a table';

    public function handle(): void
    {
        $table = $this->argument('table');
        $name  = 'add_soft_delete_to_' . $table . '_table';

        Artisan::call('make:migration', ['name' => $name]);

        // Load and patch the stub
        $path = database_path('migrations/' . $this->findLatestMigration($name));

        $stub = $this->buildMigrationContent($table);
        file_put_contents($path, $stub);

        $this->info("Migration created: {$path}");
    }

    protected function buildMigrationContent(string $table): string
    {
        return <<<PHP
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('{$table}', function (Blueprint \$table) {
            \$table->boolean('is_deleted')->default(0)->index()->after('id');
            \$table->timestamp('deleted_at')->nullable()->after('is_deleted');
            \$table->unsignedBigInteger('deleted_by')->nullable()->after('deleted_at');
        });
    }

    public function down(): void
    {
        Schema::table('{$table}', function (Blueprint \$table) {
            \$table->dropColumn(['is_deleted', 'deleted_at', 'deleted_by']);
        });
    }
};
PHP;
    }

    protected function findLatestMigration(string $name): string
    {
        $files = glob(database_path('migrations/*' . $name . '*'));
        return basename(end($files));
    }
}