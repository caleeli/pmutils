<?php
<<<if ($seedOrSeeder==='seeders'):>>>
namespace Database\Seeders;
<<<endif;>>>

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use ProcessMaker\Models\ProcessCategory;
use ProcessMaker\Models\ScreenCategory;
use ProcessMaker\Models\ScriptCategory;

class <<Str::camel($name)>>Seeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $f = fopen(__DIR__.'/<<$name>>.txt', 'r');
        $table = null;
        while(!feof($f)) {
            $line = json_decode(fgets($f), true);
            if (is_string($line)) {
                $table = $line;
            } elseif ($table && is_array($line)) {
                DB::table($table)->insert($line);
            }
        }
        fclose($f);
    }
}
