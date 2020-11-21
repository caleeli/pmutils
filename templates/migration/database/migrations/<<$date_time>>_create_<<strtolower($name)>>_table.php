<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Create<<Str::camel($name)>>Table extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('<<$name>>', function (Blueprint $table) {
            <<<
            foreach($attributes as $a) {
                echo '$table->' , $a['type'] ?? 'string', '(';
                var_export($a['name']);
                if ($a['length'] && $a['length'] < 100000) {
                    echo ', ';
                    var_export($a['length']);
                }
                echo ')';
                $nullable = isset($a['not_null']) && !$a['not_null'];
                $default = $a['default'];
                $autoincrement = $a['autoincrement'];
                if ($nullable) {
                    echo '->nullable()';
                }
                if ($default) {
                    echo '->default(';
                    var_export($default);
                    echo ')';
                }
                if ($autoincrement) {
                    echo '->autoIncrement()';
                }
                echo ";\n";
            }>>>
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('<<$name>>');
    }
}
