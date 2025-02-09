<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class MakeService extends Command
{
  protected $signature = 'make:service {name}';
  protected $description = 'Tạo một service mới trong thư mục app/Services';

  public function handle()
  {
    $name = $this->argument('name');
    $path = app_path("Services/{$name}.php");

    if (File::exists($path)) {
      $this->error("Service {$name} đã tồn tại!");
      return;
    }

    if (!File::exists(app_path('Services'))) {
      File::makeDirectory(app_path('Services'), 0755, true);
    }

    File::put($path, $this->getStub($name));

    $this->info("Service {$name} đã được tạo thành công!");
  }

  protected function getStub($name)
  {
    return <<<EOT
<?php

namespace App\Services;

class {$name}
{
    public function exampleMethod()
    {
        // Thêm logic service tại đây
    }
}
EOT;
  }
}
